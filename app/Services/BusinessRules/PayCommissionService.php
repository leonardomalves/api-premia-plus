<?php

declare(strict_types=1);

namespace App\Services\BusinessRules;

use App\Models\Commission;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayCommissionService
{
    /**
     * Process commissions for a specific order
     */
    public function processOrderCommissions(Order $order): array
    {
        Log::info("💰 Processing commissions for order: {$order->uuid}");

        if ($order->status !== 'approved') {
            Log::warning("⚠️ Order is not approved: {$order->status}");

            return [
                'success' => false,
                'message' => 'Order is not approved',
                'order' => $order,
                'commissions_created' => 0,
                'total_amount' => 0,
            ];
        }

        $user = $order->user;
        if (! $user) {
            Log::warning('⚠️ User not found in order');

            return [
                'success' => false,
                'message' => 'User not found in order',
                'order' => $order,
                'commissions_created' => 0,
                'total_amount' => 0,
            ];
        }

        // Find uplines using UpLinesService
        $upLinesService = new UpLinesService;
        $uplinesResult = $upLinesService->run($order);

        if (! $uplinesResult['success'] || empty($uplinesResult['uplines'])) {
            Log::info("ℹ️ No uplines found for order {$order->uuid}");

            return [
                'success' => true,
                'message' => 'No uplines found',
                'order' => $order,
                'commissions_created' => 0,
                'total_amount' => 0,
            ];
        }

        Log::info('📊 Found '.count($uplinesResult['uplines']).' uplines to process');

        // Process commissions in transaction
        return DB::transaction(function () use ($order, $uplinesResult) {
            $totalAmount = 0;
            $commissionsCreated = 0;
            $planMetadata = $order->plan_metadata;

            foreach ($uplinesResult['uplines'] as $uplineData) {
                $upline = User::find($uplineData['id']);
                $level = $uplineData['level'];

                // Create commission
                $result = $this->createCommission($order, $upline, $level, $planMetadata);

                if ($result['success']) {
                    $totalAmount += $result['amount'];
                    $commissionsCreated++;

                    $availableDate = $result['commission']->available_at->format('d/m/Y');
                    Log::info("✅ Commission created: {$upline->name} - Level {$level} - R$ ".
                             number_format($result['amount'], 2, ',', '.').
                             " - Available: {$availableDate}");
                } else {
                    Log::error("❌ Error creating commission: {$result['message']}");
                }
            }

            Log::info("💰 Processing completed: {$commissionsCreated} commissions, R$ ".number_format($totalAmount, 2, ',', '.'));

            return [
                'success' => true,
                'message' => 'Commissions processed successfully',
                'order' => $order,
                'commissions_created' => $commissionsCreated,
                'total_amount' => $totalAmount,
            ];
        });
    }

    /**
     * Pay commissions for a specific user
     */
    public function payUserCommissions(string $userUuid): array
    {
        Log::info("💰 Starting commission payment for user: {$userUuid}");

        $user = User::where('uuid', $userUuid)->first();

        if (! $user) {
            Log::warning("⚠️ User not found: {$userUuid}");

            return [
                'success' => false,
                'message' => 'User not found',
                'commissions_paid' => 0,
                'total_amount' => 0,
            ];
        }

        // Find available commissions for payment
        $availableCommissions = Commission::where('user_id', $user->id)
            ->where('available_at', '<=', now())
            ->where('paid', false)
            ->get();

        if ($availableCommissions->isEmpty()) {
            Log::info('ℹ️ No commissions available for payment');

            return [
                'success' => true,
                'message' => 'No commissions available for payment',
                'commissions_paid' => 0,
                'total_amount' => 0,
            ];
        }

        Log::info("📊 Found {$availableCommissions->count()} available commissions");

        // Process payments in transaction
        return DB::transaction(function () use ($availableCommissions, $user) {
            $totalAmount = 0;
            $commissionsPaid = 0;

            foreach ($availableCommissions as $commission) {
                $result = $this->processCommissionPayment($commission);

                if ($result['success']) {
                    $totalAmount += $commission->amount;
                    $commissionsPaid++;
                    Log::info('✅ Commission paid: R$ '.number_format($commission->amount, 2, ',', '.'));
                } else {
                    Log::error("❌ Error paying commission ID {$commission->id}: {$result['message']}");
                }
            }

            Log::info("💰 Payment completed: {$commissionsPaid} commissions, R$ ".number_format($totalAmount, 2, ',', '.'));

            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'commissions_paid' => $commissionsPaid,
                'total_amount' => $totalAmount,
                'user' => $user,
            ];
        });
    }

    /**
     * Pay all available commissions
     */
    public function payAllAvailableCommissions(): array
    {
        Log::info('💰 Starting payment of all available commissions...');

        // Find all available commissions
        $availableCommissions = Commission::where('available_at', '<=', now())
            ->where('paid', false)
            ->get();

        if ($availableCommissions->isEmpty()) {
            Log::info('ℹ️ No commissions available for payment');

            return [
                'success' => true,
                'message' => 'No commissions available for payment',
                'commissions_paid' => 0,
                'total_amount' => 0,
            ];
        }

        Log::info("📊 Found {$availableCommissions->count()} available commissions");

        // Group by user
        $commissionsByUser = $availableCommissions->groupBy('user_id');

        $totalCommissionsPaid = 0;
        $totalAmount = 0;
        $usersProcessed = [];

        foreach ($commissionsByUser as $userId => $userCommissions) {
            $user = User::find($userId);
            $result = $this->payUserCommissions($user->uuid);

            if ($result['success']) {
                $totalCommissionsPaid += $result['commissions_paid'];
                $totalAmount += $result['total_amount'];
                $usersProcessed[] = $user;
            }
        }

        Log::info("💰 Global payment completed: {$totalCommissionsPaid} commissions, R$ ".number_format($totalAmount, 2, ',', '.'));

        return [
            'success' => true,
            'message' => 'Global payment processed successfully',
            'commissions_paid' => $totalCommissionsPaid,
            'total_amount' => $totalAmount,
            'users_processed' => count($usersProcessed),
        ];
    }

    /**
     * Process payment for a specific commission
     */
    private function processCommissionPayment(Commission $commission): array
    {
        try {
            // Check if commission is already paid
            if ($commission->paid) {
                return [
                    'success' => false,
                    'message' => 'Commission already paid',
                ];
            }

            // Check if it's available for payment
            if ($commission->available_at > now()) {
                return [
                    'success' => false,
                    'message' => 'Commission not yet available for payment',
                ];
            }

            // Here you would implement the real payment logic
            // For example: payment gateway integration, bank transfer, etc.
            $this->executePayment($commission);

            // Mark as paid
            $commission->update(['paid' => true]);

            return [
                'success' => true,
                'message' => 'Commission paid successfully',
            ];

        } catch (\Exception $e) {
            Log::error("❌ Error processing payment for commission ID {$commission->id}: ".$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error processing payment: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Create a commission for a specific upline
     */
    private function createCommission(Order $order, User $upline, int $level, array $planMetadata): array
    {
        try {
            // Calculate commission rate based on level
            $commissionRate = $this->getCommissionRateFromMetadata($planMetadata, $level);

            if ($commissionRate <= 0) {
                return [
                    'success' => false,
                    'message' => "Zero commission rate for level {$level}",
                    'amount' => 0,
                ];
            }

            $planPrice = (float) $planMetadata['price'];
            $commissionAmount = $planPrice * ($commissionRate / 100);

            // Calculate available_at: first day of next month
            $availableAt = now()->addMonth()->startOfMonth();

            // Use updateOrCreate to avoid duplication
            $commission = Commission::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'user_id' => $upline->id,
                    'origin_user_id' => $order->user_id,
                ],
                [
                    'amount' => $commissionAmount,
                    'available_at' => $availableAt,
                    'paid' => false,
                ]
            );

            Log::info("📅 Commission will be available on: {$availableAt->format('d/m/Y')}");

            return [
                'success' => true,
                'message' => 'Commission created/updated successfully',
                'amount' => $commissionAmount,
                'commission' => $commission,
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error creating commission: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error creating commission: '.$e->getMessage(),
                'amount' => 0,
            ];
        }
    }

    /**
     * Get commission rate from plan metadata
     */
    private function getCommissionRateFromMetadata(array $planMetadata, int $level): float
    {
        return match ($level) {
            1 => (float) $planMetadata['commission_level_1'],
            2 => (float) $planMetadata['commission_level_2'],
            3 => (float) $planMetadata['commission_level_3'],
            default => 0.0
        };
    }

    /**
     * Execute the actual payment (implement as needed)
     */
    private function executePayment(Commission $commission): void
    {
        // TODO: Implement real payment logic
        // Examples:
        // - Payment gateway integration
        // - Bank transfer
        // - Add balance to user wallet
        // - Send to external payment system

        Log::info('💳 Executing payment of R$ '.number_format($commission->amount, 2, ',', '.')." for user ID {$commission->user_id}");

        // For now, just simulate payment
        // sleep(1); // Simulate processing
    }

    /**
     * Show commission statistics
     */
    public function showStatistics(): array
    {
        $totalCommissions = Commission::count();
        $totalAmount = Commission::sum('amount');
        $paidCommissions = Commission::where('paid', true)->count();
        $availableCommissions = Commission::where('available_at', '<=', now())->where('paid', false)->count();
        $pendingCommissions = Commission::where('available_at', '>', now())->where('paid', false)->count();

        Log::info('📊 Commission Statistics:');
        Log::info("   - Total commissions: {$totalCommissions}");
        Log::info('   - Total amount: R$ '.number_format($totalAmount, 2, ',', '.'));
        Log::info("   - Paid commissions: {$paidCommissions}");
        Log::info("   - Available commissions: {$availableCommissions}");
        Log::info("   - Pending commissions: {$pendingCommissions}");

        return [
            'total_commissions' => $totalCommissions,
            'total_amount' => $totalAmount,
            'paid_commissions' => $paidCommissions,
            'available_commissions' => $availableCommissions,
            'pending_commissions' => $pendingCommissions,
        ];
    }

    /**
     * Get user commissions
     */
    public function getUserCommissions(string $userUuid): array
    {
        $user = User::where('uuid', $userUuid)->first();

        if (! $user) {
            return [
                'success' => false,
                'message' => 'User not found',
            ];
        }

        $commissions = Commission::where('user_id', $user->id)
            ->with(['order', 'originUser'])
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'success' => true,
            'user' => $user,
            'commissions' => $commissions,
            'total_amount' => $commissions->sum('amount'),
            'available_amount' => $commissions->where('available_at', '<=', now())->where('paid', false)->sum('amount'),
        ];
    }
}

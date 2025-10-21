<?php

namespace App\Services\BusinessRules;

use App\Models\FinancialStatement;
use App\Models\Order;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Service responsible for managing user wallet operations
 * 
 * This service handles wallet balance updates based on financial statements
 * and order approvals, ensuring proper transaction handling and logging.
 */
class WalletService
{
    /**
     * Process wallet credit for an approved order
     * 
     * Finds the pending financial statement for the order and credits
     * the amount to the user's wallet balance.
     * 
     * @param Order $order The approved order to process
     * @return array Result of the wallet processing
     * @throws InvalidArgumentException If order is invalid
     */
    public function processWallet(Order $order): array
    {
        try {
            // Validate order status
            if ($order->status !== 'approved') {
                Log::warning('Attempted to process wallet for non-approved order', [
                    'order_id' => $order->id,
                    'order_uuid' => $order->uuid,
                    'status' => $order->status,
                ]);

                return [
                    'success' => false,
                    'message' => 'Order is not approved',
                    'order_id' => $order->id,
                ];
            }

            // Validate user exists
            if (!$order->user) {
                Log::error('Order has no associated user', [
                    'order_id' => $order->id,
                    'order_uuid' => $order->uuid,
                ]);

                return [
                    'success' => false,
                    'message' => 'Order has no associated user',
                    'order_id' => $order->id,
                ];
            }

            // Find pending financial statement
            $financialStatement = FinancialStatement::where('correlation_id', $order->uuid)
                ->where('type', 'credit')
                ->where('origin', 'plan')
                ->where('status', 'pending')
                ->first();

            if (!$financialStatement) {
                Log::warning('No pending financial statement found for order', [
                    'order_id' => $order->id,
                    'order_uuid' => $order->uuid,
                ]);

                return [
                    'success' => false,
                    'message' => 'No pending financial statement found',
                    'order_id' => $order->id,
                ];
            }

            // Validate amount
            if ($financialStatement->amount <= 0) {
                Log::error('Invalid financial statement amount', [
                    'order_id' => $order->id,
                    'amount' => $financialStatement->amount,
                ]);

                return [
                    'success' => false,
                    'message' => 'Invalid statement amount',
                    'order_id' => $order->id,
                ];
            }

            // Process wallet credit in transaction
            return DB::transaction(function () use ($order, $financialStatement) {
                // Get or create wallet for user
                $wallet = Wallet::where('user_id', $order->user->id)->first();
                
                if (!$wallet) {
                    $wallet = Wallet::create([
                        'uuid' => Str::uuid(),
                        'user_id' => $order->user->id,
                        'balance' => 0,
                    ]);
                }

                $previousBalance = $wallet->balance;
                
                // Increment wallet balance
                $wallet->increment('balance', $financialStatement->amount);
                
                // Update financial statement status
                $financialStatement->update(['status' => 'completed']);

                $newBalance = $wallet->fresh()->balance;

                Log::info('Wallet credited successfully', [
                    'user_id' => $order->user->id,
                    'user_email' => $order->user->email,
                    'order_uuid' => $order->uuid,
                    'amount' => $financialStatement->amount,
                    'previous_balance' => $previousBalance,
                    'new_balance' => $newBalance,
                    'statement_id' => $financialStatement->id,
                ]);

                return [
                    'success' => true,
                    'message' => 'Wallet credited successfully',
                    'order_id' => $order->id,
                    'amount' => $financialStatement->amount,
                    'previous_balance' => $previousBalance,
                    'new_balance' => $newBalance,
                ];
            });

        } catch (\Exception $e) {
            Log::error('Error processing wallet for order', [
                'order_id' => $order->id,
                'order_uuid' => $order->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error processing wallet: ' . $e->getMessage(),
                'order_id' => $order->id,
            ];
        }
    }

    /**
     * Get wallet balance for a user
     * 
     * @param int $userId
     * @return array
     */
    public function getBalance(int $userId): array
    {
        try {
            $wallet = Wallet::where('user_id', $userId)->first();

            if (!$wallet) {
                return [
                    'success' => true,
                    'balance' => 0,
                    'message' => 'No wallet found, balance is zero',
                ];
            }

            return [
                'success' => true,
                'balance' => $wallet->balance,
                'wallet_id' => $wallet->id,
            ];

        } catch (\Exception $e) {
            Log::error('Error getting wallet balance', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error getting balance: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if user has sufficient balance
     * 
     * @param int $userId
     * @param float $amount
     * @return bool
     */
    public function hasSufficientBalance(int $userId, float $amount): bool
    {
        $result = $this->getBalance($userId);
        
        if (!$result['success']) {
            return false;
        }

        return $result['balance'] >= $amount;
    }
}

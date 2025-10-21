<?php

namespace App\Services\BusinessRules;

use App\Models\FinancialStatement;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Service responsible for creating and managing financial statements
 * 
 * This service handles the creation of financial statement records
 * based on order transactions and other financial operations.
 */
class CreateStatementService
{
    /**
     * Process and create financial statement for a specific order
     * 
     * Creates a credit statement entry when an order is completed,
     * registering the payment in the user's financial history.
     * 
     * @param Order $order The order to process
     * @return FinancialStatement The created or updated financial statement
     * @throws InvalidArgumentException If order data is invalid
     */
    public function processFinancialStatementOrder(Order $order): FinancialStatement
    {
        // Validate order has required data
        $this->validateOrder($order);

        try {
            DB::beginTransaction();

            $statement = FinancialStatement::updateOrCreate(
                [
                    'correlation_id' => $order->uuid,
                    'type' => 'credit',
                    'origin' => 'plan',
                ],
                [
                    'uuid' => Str::uuid(),
                    'user_id' => $order->user_id,
                    'correlation_id' => $order->uuid,
                    'amount' => $this->getOrderAmount($order),
                    'type' => 'credit',
                    'description' => $this->generateDescription($order),
                    'status' => 'completed',
                    'origin' => 'plan',
                ]
            );

            DB::commit();

            Log::info('Financial statement created for order', [
                'order_uuid' => $order->uuid,
                'statement_uuid' => $statement->uuid,
                'amount' => $statement->amount,
                'user_id' => $order->user_id,
            ]);

            return $statement;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create financial statement for order', [
                'order_uuid' => $order->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Validate order has required data for statement creation
     * 
     * @param Order $order
     * @throws InvalidArgumentException
     */
    protected function validateOrder(Order $order): void
    {
        if (!$order->uuid) {
            throw new InvalidArgumentException('Order must have a UUID');
        }

        if (!$order->user_id) {
            throw new InvalidArgumentException('Order must have a user_id');
        }

        if (!isset($order->plan_metadata['price'])) {
            throw new InvalidArgumentException('Order plan_metadata must contain price');
        }

        $price = $order->plan_metadata['price'];
        if (!is_numeric($price) || $price <= 0) {
            throw new InvalidArgumentException('Order price must be a positive number');
        }
    }

    /**
     * Get the order amount from plan metadata
     * 
     * @param Order $order
     * @return float
     */
    protected function getOrderAmount(Order $order): float
    {
        return (float) $order->plan_metadata['price'];
    }

    /**
     * Generate description for the financial statement
     * 
     * @param Order $order
     * @return string
     */
    protected function generateDescription(Order $order): string
    {
        $planName = $order->plan_metadata['name'] ?? 'Plano';
        return sprintf(
            'Crédito referente ao pedido #%s - %s',
            $order->uuid,
            $planName
        );
    }
}
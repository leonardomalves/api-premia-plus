<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'user_metadata',
        'plan_id',
        'plan_metadata',
        'status',
        'amount',
        'currency',
        'payment_method',
        'payment_details',
        'paid_at',
    ];

    protected $casts = [
        'user_metadata' => 'array',
        'plan_metadata' => 'array',
        'payment_details' => 'array',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com Plan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Relacionamento com Cart
     */
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'order_id',
        'user_id',
        'origin_user_id',
        'amount',
        'paid',
        'available_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid' => 'boolean',
        'available_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    /**
     * Relacionamento com Order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relacionamento com User (quem recebe a comissão)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento com Origin User (quem fez a compra)
     */
    public function originUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'origin_user_id');
    }
}

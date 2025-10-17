<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'raffle_id',
        'ticket_level',
        'number',
        'price',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com Order (Raffle)
     */
    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'raffle_id');
    }


    /**
     * Scope para tickets ativos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope para tickets usados
     */
    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
    }

    /**
     * Scope para tickets expirados
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope para tickets reembolsados
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    /**
     * Verifica se o ticket está ativo
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verifica se o ticket foi usado
     */
    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    /**
     * Verifica se o ticket expirou
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Verifica se o ticket foi reembolsado
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Marca o ticket como usado
     */
    public function markAsUsed(): bool
    {
        return $this->update(['status' => 'used']);
    }

    /**
     * Marca o ticket como expirado
     */
    public function markAsExpired(): bool
    {
        return $this->update(['status' => 'expired']);
    }

    /**
     * Marca o ticket como reembolsado
     */
    public function markAsRefunded(): bool
    {
        return $this->update(['status' => 'refunded']);
    }
}

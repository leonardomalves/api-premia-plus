<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RaffleTicket extends Model
{
    use HasFactory, SoftDeletes;

    // Status constants
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_WINNER = 'winner';
    const STATUS_LOSER = 'loser';

    protected $fillable = [
        'uuid',
        'user_id',
        'raffle_id',
        'ticket_id',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot method to generate UUID
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid();
            }
        });
    }

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);  
    }

    /**
     * Relacionamento com Raffle
     */
    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Raffle::class);
    }

    /**
     * Relacionamento com Ticket
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeWinner($query)
    {
        return $query->where('status', self::STATUS_WINNER);
    }

    public function scopeLoser($query)
    {
        return $query->where('status', self::STATUS_LOSER);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByRaffle($query, $raffleId)
    {
        return $query->where('raffle_id', $raffleId);
    }

    /**
     * Status checkers
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isWinner(): bool
    {
        return $this->status === self::STATUS_WINNER;
    }

    public function isLoser(): bool
    {
        return $this->status === self::STATUS_LOSER;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Status setters
     */
    public function markAsPending(): bool
    {
        return $this->update(['status' => self::STATUS_PENDING]);
    }

    public function markAsConfirmed(): bool
    {
        return $this->update(['status' => self::STATUS_CONFIRMED]);
    }

    public function markAsWinner(): bool
    {
        return $this->update(['status' => self::STATUS_WINNER]);
    }

    public function markAsLoser(): bool
    {
        return $this->update(['status' => self::STATUS_LOSER]);
    }

    public function markAsRejected(): bool
    {
        return $this->update(['status' => self::STATUS_REJECTED]);
    }

    public function markAsCancelled(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELLED]);
    }
}

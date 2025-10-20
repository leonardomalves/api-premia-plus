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
     * Relacionamento com RaffleTickets (tickets aplicados em rifas)
     */
    public function raffleTickets()
    {
        return $this->hasMany(RaffleTicket::class);
    }

    /**
     * Rifas em que este ticket foi aplicado
     */
    public function raffles()
    {
        return $this->belongsToMany(Raffle::class, 'raffle_tickets')
            ->withPivot('user_id', 'status')
            ->withTimestamps();
    }

    /**
     * Scope para tickets ativos
     */

}


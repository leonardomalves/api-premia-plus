<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tickets são apenas números no pool global
     * Não têm relacionamento direto com users ou raffles
     * O relacionamento é feito através de raffle_tickets
     */
    protected $fillable = [
        'number',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

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
     * Verifica se o ticket está disponível (não aplicado em nenhuma rifa)
     */
    public function isAvailable(): bool
    {
        return $this->raffleTickets()->doesntExist();
    }

    /**
     * Verifica se o ticket está aplicado em alguma rifa específica
     */
    public function isAppliedInRaffle(int $raffleId): bool
    {
        return $this->raffleTickets()
            ->where('raffle_id', $raffleId)
            ->exists();
    }

    /**
     * Scope para tickets disponíveis (não aplicados em nenhuma rifa)
     */
    public function scopeAvailable($query)
    {
        return $query->whereDoesntHave('raffleTickets');
    }

    /**
     * Scope para tickets aplicados em alguma rifa
     */
    public function scopeApplied($query)
    {
        return $query->whereHas('raffleTickets');
    }
}

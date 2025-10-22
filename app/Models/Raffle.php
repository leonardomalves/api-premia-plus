<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Raffle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'prize_value',
        'operation_cost',
        'unit_ticket_value',
        'liquidity_ratio',
        'liquid_value',
        'min_tickets_required',
        'draw_date',
        'status',
        'created_by',
        'winner_id',
        'winner_ticket',
        'notes',
    ];

    protected $casts = [
        'prize_value' => 'decimal:2',
        'operation_cost' => 'decimal:2',
        'unit_ticket_value' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($raffle) {
            if (! $raffle->uuid) {
                $raffle->uuid = Str::uuid();
            }
        });
    }

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_ACTIVE = 'active';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_INACTIVE = 'inactive';

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function raffleTickets()
    {
        return $this->hasMany(RaffleTicket::class);
    }

    public function tickets()
    {
        return $this->hasMany(RaffleTicket::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'raffle_tickets')
            ->withPivot('ticket_id', 'status')
            ->withTimestamps();
    }

    // Helper methods
    
    /**
     * Conta quantos tickets estão disponíveis no pool
     * (tickets que ainda não foram distribuídos)
     */
    public function availableTicketsCount(): int
    {
        $totalTickets = $this->calculateTotalTickets();
        $distributedTickets = $this->raffleTickets()->count();
        
        return max(0, $totalTickets - $distributedTickets);
    }

    /**
     * Calcula o total de tickets que a rifa pode ter
     */
    public function calculateTotalTickets(): int
    {
        if ($this->unit_ticket_value <= 0) {
            return 0;
        }
        
        return (int) floor($this->liquid_value / $this->unit_ticket_value);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByCreator($query, $userId)
    {
        return $query->where('created_by', $userId);
    }
}

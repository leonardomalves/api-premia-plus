<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletTicket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'order_id',
        'plan_id',
        'ticket_level',
        'total_tickets',
        'total_tickets_used',
        'bonus_tickets',
        'expiration_date',
        'status',
    ];

    protected $casts = [
        'expiration_date' => 'date',
    ];

    protected $appends = ['available_tickets'];

    /**
     * Relacionamento com User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com Order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relacionamento com Plan
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function getAvailableTicketsAttribute()
    {
        return $this->total_tickets - $this->total_tickets_used + $this->bonus_tickets;
    }

    /**
     * Decrementa a quantidade especificada de tickets
     * Prioriza tickets normais, depois bônus
     *
     * @param  int  $quantity  Quantidade de tickets a decrementar
     * @return int Quantidade efetivamente decrementada
     */
    public function decrementIn(int $quantity): int
    {
        $decremented = 0;

        for ($i = 0; $i < $quantity; $i++) {
            $normalTickets = $this->total_tickets - $this->total_tickets_used;

            if ($normalTickets > 0) {
                $this->total_tickets_used += 1;
                $decremented++;
            } elseif ($this->bonus_tickets > 0) {
                $this->bonus_tickets -= 1;
                $decremented++;
            } else {
                // Não há mais tickets disponíveis
                break;
            }
        }

        if ($decremented > 0) {
            $this->save();
        }

        return $decremented;
    }
}

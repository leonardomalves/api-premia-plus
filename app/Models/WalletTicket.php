<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletTicket extends Model
{
    use SoftDeletes;

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
}

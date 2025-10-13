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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    //
}

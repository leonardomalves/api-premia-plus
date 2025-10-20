<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RaffleTicket extends Model
{
    use SoftDeletes;


    public function user()
    {
        return $this->belongsTo(User::class);  
    }


    public function raffle()
    {
        return $this->belongsTo(Raffle::class);
    }


    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

}

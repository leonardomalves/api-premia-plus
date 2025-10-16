<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'tickets_required',
        'min_ticket_level',
        'max_tickets_per_user',
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
        'unit_ticket_value' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($raffle) {
            if (!$raffle->uuid) {
                $raffle->uuid = Str::uuid();
            }
        });
    }

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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

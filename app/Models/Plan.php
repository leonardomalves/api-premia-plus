<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'price',
        'status',
        'commission_level_1',
        'commission_level_2',
        'commission_level_3',
        'is_promotional',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'is_promotional' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:2',
        'commission_level_1' => 'decimal:2',
        'commission_level_2' => 'decimal:2',
        'commission_level_3' => 'decimal:2',
    ];

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}

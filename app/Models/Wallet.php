<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'balance',
        'withdrawals',
        'blocked',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'withdrawals' => 'decimal:2',
        'blocked' => 'decimal:2',
    ];

    /**
     * Relacionamento com User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calcula o saldo disponível (balance - blocked)
     */
    public function getAvailableBalanceAttribute(): float
    {
        return $this->balance - $this->blocked;
    }

    /**
     * Debita um valor do saldo
     */
    public function debit(float $amount): bool
    {
        if ($this->getAvailableBalanceAttribute() < $amount) {
            return false;
        }

        $this->balance -= $amount;
        return $this->save();
    }

    /**
     * Credita um valor no saldo
     */
    public function credit(float $amount): bool
    {
        $this->balance += $amount;
        return $this->save();
    }

    /**
     * Bloqueia um valor
     */
    public function block(float $amount): bool
    {
        if ($this->getAvailableBalanceAttribute() < $amount) {
            return false;
        }

        $this->blocked += $amount;
        return $this->save();
    }

    /**
     * Desbloqueia um valor
     */
    public function unblock(float $amount): bool
    {
        if ($this->blocked < $amount) {
            return false;
        }

        $this->blocked -= $amount;
        return $this->save();
    }
}

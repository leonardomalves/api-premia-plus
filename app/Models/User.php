<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'role',
        'phone',
        'status',
        'sponsor_id',
        'username',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the sponsor of this user
     */
    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    /**
     * Get the users sponsored by this user
     */
    public function sponsored()
    {
        return $this->hasMany(User::class, 'sponsor_id');
    }

    /**
     * Relacionamento com Commission (comissões que o usuário recebe)
     */
    public function commissions()
    {
        return $this->hasMany(Commission::class, 'user_id');
    }

    /**
     * Relacionamento com Commission (comissões originadas pelas compras do usuário)
     */
    public function originCommissions()
    {
        return $this->hasMany(Commission::class, 'origin_user_id');
    }

    /**
     * Relacionamento com WalletTicket (carteira de tickets do usuário)
     */
    public function wallet()
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Relacionamento com RaffleTicket (tickets aplicados em rifas)
     */
    public function raffleTickets()
    {
        return $this->hasMany(RaffleTicket::class);
    }

    /**
     * Rifas em que o usuário participou
     */
    public function participatedRaffles()
    {
        return $this->belongsToMany(Raffle::class, 'raffle_tickets')
            ->withPivot('ticket_id', 'status')
            ->withTimestamps();
    }

    public function financialStatements()
    {
        return $this->hasMany(FinancialStatement::class);
    }
}

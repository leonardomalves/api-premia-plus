<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Subscriber extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriberFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'country',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'referrer_url',
        'tracking_data',
        'status',
        'subscription_date',
        'email_verified_at',
        'unsubscribed_at',
        'converted_user_id',
        'sponsor_id',
        'converted_at',
        'conversion_value',
        'ip_address',
        'user_agent',
        'device_info',
        'preferences',
    ];

    protected $casts = [
        'subscription_date' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'converted_at' => 'datetime',
        'conversion_value' => 'decimal:2',
        'tracking_data' => 'array',
        'device_info' => 'array',
        'preferences' => 'array',
    ];

    protected $hidden = [
        'id',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Subscriber $subscriber): void {
            if (empty($subscriber->uuid)) {
                $subscriber->uuid = (string) Str::uuid();
            }
        });
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeConverted($query)
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    public function scopeUnsubscribed($query)
    {
        return $query->where('status', self::STATUS_UNSUBSCRIBED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeUnverified($query)
    {
        return $query->whereNull('email_verified_at');
    }

    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getIsVerifiedAttribute(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function getIsUnsubscribedAttribute(): bool
    {
        return $this->status === self::STATUS_UNSUBSCRIBED;
    }

    // Methods
    public function markAsVerified(): void
    {
        $this->update([
            'email_verified_at' => now(),
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    public function unsubscribe(): void
    {
        $this->update([
            'status' => self::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now(),
        ]);
    }

    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'unsubscribed_at' => null,
        ]);
    }

    // Relationships
    public function convertedUser()
    {
        return $this->belongsTo(User::class, 'converted_user_id');
    }

    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    // Conversion methods
    public function convertToUser(User $user, ?User $sponsor = null, ?float $conversionValue = null): void
    {
        $this->update([
            'status' => self::STATUS_CONVERTED,
            'converted_user_id' => $user->id,
            'sponsor_id' => $sponsor?->id,
            'converted_at' => now(),
            'conversion_value' => $conversionValue,
        ]);
    }

    public function getIsConvertedAttribute(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    // UTM tracking methods
    public function getTrackingSourceAttribute(): string
    {
        return $this->utm_source ?? 'direct';
    }

    public function getFullTrackingInfoAttribute(): array
    {
        return [
            'source' => $this->utm_source,
            'medium' => $this->utm_medium,
            'campaign' => $this->utm_campaign,
            'term' => $this->utm_term,
            'content' => $this->utm_content,
            'referrer' => $this->referrer_url,
        ];
    }

    // Scopes for reporting
    public function scopeByUtmSource($query, string $source)
    {
        return $query->where('utm_source', $source);
    }

    public function scopeByUtmCampaign($query, string $campaign)
    {
        return $query->where('utm_campaign', $campaign);
    }

    public function scopeConvertedBetween($query, $startDate, $endDate)
    {
        return $query->where('status', self::STATUS_CONVERTED)
                    ->whereBetween('converted_at', [$startDate, $endDate]);
    }

    public function scopeWithSponsor($query)
    {
        return $query->whereNotNull('sponsor_id');
    }
}

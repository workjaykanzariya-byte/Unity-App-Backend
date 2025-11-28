<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasUuids;
    use Notifiable;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'username',
        'email',
        'phone',
        'is_phone_verified',
        'is_email_verified',
        'role',
        'status',
        'default_circle_id',
        'introduced_by',
        'coins_balance',
        'influencer_stars',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'is_phone_verified' => 'boolean',
            'is_email_verified' => 'boolean',
            'coins_balance' => 'integer',
            'influencer_stars' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (! $user->getKey()) {
                $user->setAttribute($user->getKeyName(), (string) Str::uuid());
            }
        });
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(self::class, 'introduced_by');
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }
}

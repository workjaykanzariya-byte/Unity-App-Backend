<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OtpCode extends Model
{
    use HasFactory;

    protected $table = 'otp_codes';

    protected $fillable = [
        'user_id',
        'identifier',
        'code',
        'channel',
        'purpose',
        'expires_at',
        'attempts',
        'used',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'used' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->getAttribute('id')) {
                $model->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

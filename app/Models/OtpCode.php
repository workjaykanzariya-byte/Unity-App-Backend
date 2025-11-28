<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpCode extends Model
{
    use HasFactory;
    use HasUuids;

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

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'used' => 'boolean',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

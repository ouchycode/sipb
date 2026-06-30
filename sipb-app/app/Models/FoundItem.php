<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',

    'category',
    'description',
    'location',
    'found_at',
    'photo_url',
    'photo_data',
    'photo_path',
    'status',
    'published_at',
    'claimed_at',
    'rejected_at',
    'expired_at',
    'finder_name',
    'finder_nim',
    'claimant_name',
    'claimant_nim',
    'storage_location',
    'admin_notes',
    'validation_notes',
    'managed_by',
    'pickup_checklist',
])]
class FoundItem extends Model
{
    public const STATUS_AVAILABLE = 'tersedia';
    public const STATUS_CLAIMED = 'sudah_diambil';
    public const STATUS_REVISION = 'perlu_revisi';
    public const STATUS_REJECTED = 'ditolak';

    public const EXPIRATION_DAYS = 30;

    private const TRACKING_PREFIX = 'SIPB-UYM';
    private const TRACKING_MULTIPLIER = 7919;
    private const TRACKING_SALT = 104729;

    protected function casts(): array
    {
        return [
            'found_at' => 'datetime',
            'published_at' => 'datetime',
            'claimed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'expired_at' => 'datetime',
            'pickup_checklist' => 'array',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(StatusAudit::class);
    }

    public function scopeVisibleToPublic(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_AVAILABLE)
                ->whereNotNull('published_at')
                ->where('published_at', '>=', now()->subDays(self::EXPIRATION_DAYS));
        })->orWhere(function ($q) {
            $q->where('status', self::STATUS_CLAIMED)
                ->whereNotNull('claimed_at')
                ->where('claimed_at', '>=', now()->subDay());
        });
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->published_at !== null && $this->published_at->lt(now()->subDays(self::EXPIRATION_DAYS));
    }

    public function trackingCode(): string
    {
        $encoded = strtoupper(base_convert((string) (($this->id * self::TRACKING_MULTIPLIER) + self::TRACKING_SALT), 10, 36));

        return self::TRACKING_PREFIX.'-'.$encoded.'-'.self::trackingChecksum($encoded, (int) $this->id);
    }

    public static function idFromTrackingCode(string $code): ?int
    {
        $normalized = strtoupper(trim($code));

        if (! preg_match('/^SIPB-UYM-([A-Z0-9]+)-([A-Z0-9]{2})$/', $normalized, $matches)) {
            return null;
        }

        $encoded = $matches[1];
        $number = (int) base_convert($encoded, 36, 10);
        $decoded = $number - self::TRACKING_SALT;

        if ($decoded <= 0 || $decoded % self::TRACKING_MULTIPLIER !== 0) {
            return null;
        }

        $id = intdiv($decoded, self::TRACKING_MULTIPLIER);

        if ($matches[2] !== self::trackingChecksum($encoded, $id)) {
            return null;
        }

        return $id;
    }

    private static function trackingChecksum(string $encoded, int $id): string
    {
        $hash = strtoupper(base_convert(sprintf('%u', crc32($encoded.'|'.$id.'|'.self::TRACKING_PREFIX)), 10, 36));

        return str_pad(substr($hash, -2), 2, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
    public const STATUS_EXPIRED = 'kadaluarsa';

    public const EXPIRATION_DAYS = 30;

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

    public function getPhotoUrlAttribute(?string $value): string
    {
        if ($this->photo_path) {
            return Storage::url($this->photo_path);
        }

        return $this->photo_data ?: (string) $value;
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

}

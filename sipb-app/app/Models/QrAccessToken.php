<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'token_hash',
    'purpose',
    'found_item_id',
    'created_by',
    'used_found_item_id',
    'expires_at',
    'used_at',
    'metadata',
])]
class QrAccessToken extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public static function mint(string $purpose, int $minutes, ?int $userId = null, ?int $itemId = null, array $metadata = []): array
    {
        $plain = Str::random(48);

        $token = static::create([
            'token_hash' => hash('sha256', $plain),
            'purpose' => $purpose,
            'found_item_id' => $itemId,
            'created_by' => $userId,
            'expires_at' => now()->addMinutes($minutes),
            'metadata' => $metadata ?: null,
        ]);

        return [$token, $plain];
    }

    public static function findUsable(string $plain, string $purpose): ?self
    {
        return static::query()
            ->where('token_hash', hash('sha256', $plain))
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query
            ->whereNull('used_at')
            ->where('expires_at', '>', now());
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(FoundItem::class, 'found_item_id');
    }

    public function usedItem(): BelongsTo
    {
        return $this->belongsTo(FoundItem::class, 'used_found_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

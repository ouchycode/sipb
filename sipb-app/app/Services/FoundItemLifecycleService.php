<?php

namespace App\Services;

use App\Models\FoundItem;

class FoundItemLifecycleService
{
    public function syncExpiredItems(): array
    {
        $normalized = FoundItem::query()
            ->where('status', 'dalam_proses_klaim')
            ->update([
                'status' => FoundItem::STATUS_AVAILABLE,
                'updated_at' => now(),
            ]);

        FoundItem::query()
            ->where('status', 'kadaluarsa')
            ->update([
                'status' => FoundItem::STATUS_CLAIMED,
                'expired_at' => now(),
                'updated_at' => now(),
            ]);

        $expired = FoundItem::query()
            ->where('status', FoundItem::STATUS_AVAILABLE)
            ->whereNotNull('published_at')
            ->where('published_at', '<', now()->subDays(FoundItem::EXPIRATION_DAYS))
            ->update([
                'status' => FoundItem::STATUS_CLAIMED,
                'expired_at' => now(),
                'updated_at' => now(),
            ]);

        return [
            'normalized' => $normalized,
            'expired' => $expired,
        ];
    }

    public function isPubliclyVisible(FoundItem $item): bool
    {
        return $item->status === FoundItem::STATUS_AVAILABLE
            && $item->published_at !== null
            && $item->published_at->greaterThanOrEqualTo(now()->subDays(FoundItem::EXPIRATION_DAYS));
    }
}

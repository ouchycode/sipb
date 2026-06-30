<?php

namespace App\Services;

use App\Models\FoundItem;

class FoundItemLifecycleService
{
    public function syncExpiredItems(): array
    {
        FoundItem::query()
            ->where('status', FoundItem::STATUS_EXPIRED)
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

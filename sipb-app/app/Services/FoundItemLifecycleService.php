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
                'status' => 'tersedia',
                'updated_at' => now(),
            ]);

        FoundItem::query()
            ->where('status', 'kadaluarsa')
            ->update([
                'status' => 'sudah_diambil',
                'expired_at' => now(),
                'updated_at' => now(),
            ]);

        $expired = FoundItem::query()
            ->where('status', 'tersedia')
            ->whereNotNull('published_at')
            ->where('published_at', '<', now()->subDays(30))
            ->update([
                'status' => 'sudah_diambil',
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
        return $item->status === 'tersedia'
            && $item->published_at !== null
            && $item->published_at->greaterThanOrEqualTo(now()->subDays(30));
    }
}

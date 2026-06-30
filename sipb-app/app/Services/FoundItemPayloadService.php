<?php

namespace App\Services;

use App\Models\FoundItem;
use App\Models\StatusAudit;

class FoundItemPayloadService
{
    public function public(FoundItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,

            'category' => $item->category,
            'description' => $item->description,
            'location' => $item->location,
            'found_at' => $item->found_at?->toISOString(),
            'published_at' => $item->published_at?->toISOString(),
            'photo_url' => $item->photo_url,
            'status' => $item->status,
            'claimer_name' => $item->claimant_name,
            'claimed_at' => $item->claimed_at?->toISOString(),
            'finder_name' => $item->finder_name,

        ];
    }

    public function admin(FoundItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'category' => $item->category,
            'description' => $item->description,
            'location' => $item->location,
            'found_at' => $item->found_at?->toISOString(),
            'photo_url' => $item->photo_url,
            'status' => $item->status,
            'published_at' => $item->published_at?->toISOString(),
            'claimed_at' => $item->claimed_at?->toISOString(),
            'rejected_at' => $item->rejected_at?->toISOString(),
            'expired_at' => $item->expired_at?->toISOString(),
            'finder_name' => $item->finder_name,
            'finder_nim' => $item->finder_nim,
            'claimant_name' => $item->claimant_name,
            'claimant_nim' => $item->claimant_nim,
            'storage_location' => $item->storage_location,
            'admin_notes' => $item->admin_notes,
            'validation_notes' => $item->validation_notes,
            'pickup_checklist' => $item->pickup_checklist,
            'manager' => $item->manager?->only('name', 'email'),
            'is_expired' => $item->is_expired,
            'audits' => $item->relationLoaded('audits')
                ? $item->audits
                    ->sortByDesc('created_at')
                    ->values()
                    ->map(fn (StatusAudit $audit) => [
                        'id' => $audit->id,
                        'from_status' => $audit->from_status,
                        'to_status' => $audit->to_status,
                        'action' => $audit->action,
                        'notes' => $audit->notes,
                        'created_at' => $audit->created_at?->toISOString(),
                        'user' => $audit->user?->only('name', 'email'),
                    ])
                : [],
        ];
    }
}

<?php

namespace App\Services;

use App\Models\FoundItem;
use App\Models\StatusAudit;
use Illuminate\Support\Str;

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
            'photo_url' => $item->photo_data ?: $item->photo_url,
            'status' => $item->status,
            'finder_name' => $item->finder_name,

        ];
    }

    public function tracking(FoundItem $item): array
    {
        return [
            'id' => $item->id,
            'code' => $item->trackingCode(),
            'name' => $item->name,

            'category' => $item->category,
            'location' => $item->location,
            'found_at' => $item->found_at?->toISOString(),
            'published_at' => $item->published_at?->toISOString(),
            'claimed_at' => $item->claimed_at?->toISOString(),
            'rejected_at' => $item->rejected_at?->toISOString(),
            'expired_at' => $item->expired_at?->toISOString(),
            'status' => $item->status,
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
            'photo_url' => $item->photo_data ?: $item->photo_url,
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
            'duplicate_candidates' => $item->status === 'draft'
                ? $this->duplicateCandidates($item)
                : [],
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

    private function duplicateCandidates(FoundItem $item): array
    {
        $firstWord = Str::of($item->name)->explode(' ')->first();

        return FoundItem::query()
            ->whereKeyNot($item->id)
            ->where('category', $item->category)
            ->where(function ($query) use ($item, $firstWord): void {
                $query
                    ->where('location', $item->location)
                    ->orWhere('name', 'like', '%'.$firstWord.'%');
            })
            ->latest()
            ->limit(3)
            ->get(['id', 'name', 'location', 'status', 'created_at'])
            ->map(fn (FoundItem $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'location' => $candidate->location,
                'status' => $candidate->status,
                'created_at' => $candidate->created_at?->toISOString(),
            ])
            ->all();
    }
}

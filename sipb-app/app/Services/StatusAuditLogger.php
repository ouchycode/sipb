<?php

namespace App\Services;

use App\Models\FoundItem;
use App\Models\StatusAudit;

class StatusAuditLogger
{
    public function log(FoundItem $item, ?string $from, string $to, string $action, int $userId, ?string $notes = null): void
    {
        StatusAudit::create([
            'found_item_id' => $item->id,
            'user_id' => $userId,
            'from_status' => $from,
            'to_status' => $to,
            'action' => $action,
            'notes' => $notes,
            'ip_address' => request()->ip(),
        ]);
    }
}

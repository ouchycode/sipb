<?php

namespace App\Services;

use App\Models\FoundItem;
use App\Models\StatusAudit;

class StatusAuditLogger
{
    private function anonymizeIp(?string $ip): ?string
    {
        if ($ip === null) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[count($parts) - 1] = 'xxx';
            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $count = count($parts);
            for ($i = intdiv($count, 2); $i < $count; $i++) {
                $parts[$i] = 'xxxx';
            }
            return implode(':', $parts);
        }

        return $ip;
    }

    public function log(FoundItem $item, ?string $from, string $to, string $action, int $userId, ?string $notes = null): void
    {
        StatusAudit::create([
            'found_item_id' => $item->id,
            'user_id' => $userId,
            'from_status' => $from,
            'to_status' => $to,
            'action' => $action,
            'notes' => $notes,
            'ip_address' => $this->anonymizeIp(request()->ip()),
        ]);
    }
}

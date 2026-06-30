<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\FoundItem;
use App\Models\StatusAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\FoundItemLifecycleService;
use App\Services\FoundItemPayloadService;

class ActivityController extends Controller
{
    protected FoundItemLifecycleService $lifecycleService;
    protected FoundItemPayloadService $payloadService;

    public function __construct(
        FoundItemLifecycleService $lifecycleService,
        FoundItemPayloadService $payloadService
    ) {
        $this->lifecycleService = $lifecycleService;
        $this->payloadService = $payloadService;
    }

    public function activity(Request $request): Response
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $filters = $request->only(['q', 'date', 'action', 'per_page']);
        $perPage = (int) ($filters['per_page'] ?? 10);
        $perPage = in_array($perPage, [10, 20, 50], true) ? $perPage : 10;
        $filters['per_page'] = $perPage;

        $baseQuery = StatusAudit::query()
            ->with(['foundItem', 'user']);

        $audits = (clone $baseQuery)
            ->when($filters['q'] ?? null, function ($query, string $keyword): void {
                $query->where(function ($query) use ($keyword): void {
                    $query->where('action', 'like', "%{$keyword}%")
                        ->orWhere('notes', 'like', "%{$keyword}%")
                        ->orWhere('ip_address', 'like', "%{$keyword}%")
                        ->orWhereHas('foundItem', fn ($query) => $query->where('name', 'like', "%{$keyword}%"))
                        ->orWhereHas('user', fn ($query) => $query
                            ->where('name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['date'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', $date))
            ->when($filters['action'] ?? null, fn ($query, string $action) => $query->where('action', $action))
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (StatusAudit $audit) => [
                'id' => $audit->id,
                'item' => $audit->foundItem ? [
                    'id' => $audit->foundItem->id,
                    'name' => $audit->foundItem->name,
                ] : null,
                'user' => $audit->user?->only('name', 'email'),
                'from_status' => $audit->from_status,
                'to_status' => $audit->to_status,
                'action' => $audit->action,
                'notes' => $audit->notes,
                'ip_address' => $audit->ip_address,
                'created_at' => $audit->created_at?->toISOString(),
            ]);

        $dailyStats = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'total' => (int) $row->total,
            ]);

        return Inertia::render('Admin/Activity', [
            'audits' => $audits,
            'filters' => $filters,
            'actions' => StatusAudit::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'dailyStats' => $dailyStats,
            'storageStats' => [
                'total_items' => FoundItem::count(),
                'items_with_photo' => FoundItem::whereNotNull('photo_data')->orWhereNotNull('photo_path')->count(),
                'total_photo_size_bytes' => (int) FoundItem::whereNotNull('photo_data')->sum(DB::raw('LENGTH(photo_data)')),
            ],
        ]);
    }

    public function history(Request $request): Response
    {
        $this->lifecycleService->syncExpiredItems();

        $filters = $request->only(['q', 'category', 'location', 'per_page']);
        $perPage = (int) ($filters['per_page'] ?? 10);
        $perPage = in_array($perPage, [10, 20, 50], true) ? $perPage : 10;
        $filters['per_page'] = $perPage;

        $items = FoundItem::query()
            ->with('manager')
            ->where('status', FoundItem::STATUS_CLAIMED)
            ->when($filters['q'] ?? null, function ($query, string $keyword): void {
                $query->where(function ($nested) use ($keyword): void {
                    $nested
                        ->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('finder_name', 'like', "%{$keyword}%")
                        ->orWhere('finder_nim', 'like', "%{$keyword}%")
                        ->orWhere('claimant_name', 'like', "%{$keyword}%")
                        ->orWhere('claimant_nim', 'like', "%{$keyword}%");
                });
            })
            ->when($filters['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
            ->when($filters['location'] ?? null, fn ($query, string $location) => $query->where('location', $location))
            ->latest('claimed_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (FoundItem $item) => $this->payloadService->admin($item));

        return Inertia::render('Admin/History', [
            'items' => $items,
            'filters' => $filters,
            'categories' => config('sipb.categories'),
            'locations' => config('sipb.locations'),
        ]);
    }
}

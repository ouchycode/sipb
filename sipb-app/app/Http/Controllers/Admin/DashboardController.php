<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\FoundItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\FoundItemLifecycleService;
use App\Services\FoundItemPayloadService;

class DashboardController extends Controller
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

    public function dashboard(): Response
    {
        $this->lifecycleService->syncExpiredItems();

        $latest = FoundItem::query()
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (FoundItem $item) => $this->payloadService->admin($item));

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total' => FoundItem::count(),
                'available' => FoundItem::where('status', FoundItem::STATUS_AVAILABLE)->count(),
                'claimed' => FoundItem::where('status', FoundItem::STATUS_CLAIMED)->count(),
                'expired' => FoundItem::whereNotNull('expired_at')->count(),
            ],
            'latest' => $latest,
            'insights' => $this->buildInsights(),
        ]);
    }

    public function insights(Request $request): JsonResponse
    {
        return response()->json($this->buildInsights($request->integer('period')));
    }

    private function buildInsights(?int $periodDays = null): array
    {
        $filteredQuery = fn () => tap(FoundItem::query(), function ($q) use ($periodDays): void {
            if ($periodDays) {
                $q->where('created_at', '>=', now()->subDays($periodDays));
            }
        });

        $unfilteredQuery = fn () => FoundItem::query();

        return [
            'categories' => $filteredQuery()
                ->select('category', DB::raw('count(*) as total'))
                ->groupBy('category')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
            'locations' => $filteredQuery()
                ->select('location', DB::raw('count(*) as total'))
                ->groupBy('location')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
            'months' => $unfilteredQuery()
                ->latest()
                ->limit(240)
                ->get(['created_at'])
                ->groupBy(fn (FoundItem $item) => $item->created_at?->format('Y-m') ?? '-')
                ->map(fn ($items, string $month) => ['month' => $month, 'total' => $items->count()])
                ->values()
                ->take(6),
        ];
    }
}

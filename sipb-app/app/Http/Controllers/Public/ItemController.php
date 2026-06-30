<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

use App\Models\FoundItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use App\Services\FoundItemPayloadService;

class ItemController extends Controller
{
    protected FoundItemPayloadService $payloadService;

    public function __construct(FoundItemPayloadService $payloadService)
    {
        $this->payloadService = $payloadService;
    }

    public function home(): Response
    {
        return Inertia::render('Public/Index', [
            'stats' => [
                'posted' => FoundItem::whereNotNull('published_at')->count(),
                'recovered' => FoundItem::where('status', FoundItem::STATUS_CLAIMED)->count(),
                'active' => FoundItem::visibleToPublic()->count(),
            ],
        ]);
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'status', 'category', 'location', 'date', 'sort', 'per_page']);
        $sort = $filters['sort'] ?? 'newest';
        $perPage = min((int) ($filters['per_page'] ?? 9), 50);

        $items = FoundItem::query()
            ->visibleToPublic()

            ->when($filters['q'] ?? null, function ($query, string $keyword): void {
                $query->where(function ($nested) use ($keyword): void {
                    $nested
                        ->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('location', 'like', "%{$keyword}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
            ->when($filters['location'] ?? null, fn ($query, string $location) => $query->where('location', $location))
            ->when($filters['date'] ?? null, fn ($query, string $date) => $query->whereDate('found_at', $date))
            ->when($sort === 'oldest', fn ($query) => $query->oldest('published_at'))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name'))
            ->when($sort === 'category', fn ($query) => $query->orderBy('category')->latest('published_at'))
            ->when($sort === 'location', fn ($query) => $query->orderBy('location')->latest('published_at'))
            ->when(! in_array($sort, ['oldest', 'name', 'category', 'location'], true), fn ($query) => $query->latest('published_at'))
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (FoundItem $item) => $this->payloadService->public($item));

        return Inertia::render('Public/Search', [
            'items' => $items,
            'filters' => $filters,
            'categories' => config('sipb.categories'),
            'locations' => config('sipb.locations'),
        ]);
    }

    public function help(): Response
    {
        return Inertia::render('Public/Help');
    }



    public function show(FoundItem $item): Response
    {
        abort_unless(
            ($item->status === FoundItem::STATUS_AVAILABLE
                && $item->published_at !== null
                && $item->published_at->greaterThanOrEqualTo(now()->subDays(FoundItem::EXPIRATION_DAYS)))
            || ($item->status === FoundItem::STATUS_CLAIMED
                && $item->claimed_at !== null
                && $item->claimed_at->greaterThanOrEqualTo(now()->subDay())),
            404
        );

        return Inertia::render('Public/Show', [
            'item' => $this->payloadService->public($item),
        ]);
    }
}

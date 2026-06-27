<?php

namespace App\Http\Controllers;

use App\Models\FoundItem;
use App\Models\StatusAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

use App\Services\FoundItemLifecycleService;
use App\Services\FoundItemPayloadService;
use App\Services\StatusAuditLogger;

class AdminItemController extends Controller
{
    protected FoundItemLifecycleService $lifecycleService;
    protected FoundItemPayloadService $payloadService;
    protected StatusAuditLogger $auditLogger;

    public function __construct(
        FoundItemLifecycleService $lifecycleService,
        FoundItemPayloadService $payloadService,
        StatusAuditLogger $auditLogger
    ) {
        $this->lifecycleService = $lifecycleService;
        $this->payloadService = $payloadService;
        $this->auditLogger = $auditLogger;
    }

    public function dashboard(): Response
    {
        $this->lifecycleService->syncExpiredItems();

        $latest = FoundItem::query()
            ->whereNotIn('status', ['perlu_revisi', 'ditolak'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (FoundItem $item) => $this->payloadService->admin($item));

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total' => FoundItem::count(),
                'available' => FoundItem::where('status', 'tersedia')->count(),
                'claimed' => FoundItem::where('status', 'sudah_diambil')->count(),
                'expired' => FoundItem::where('status', 'kadaluarsa')->count(),
            ],
            'latest' => $latest,
            'insights' => [
                'categories' => FoundItem::query()
                    ->select('category', DB::raw('count(*) as total'))
                    ->groupBy('category')
                    ->orderByDesc('total')
                    ->limit(5)
                    ->get(),
                'locations' => FoundItem::query()
                    ->select('location', DB::raw('count(*) as total'))
                    ->groupBy('location')
                    ->orderByDesc('total')
                    ->limit(5)
                    ->get(),
                'months' => FoundItem::query()
                    ->latest()
                    ->limit(240)
                    ->get(['created_at'])
                    ->groupBy(fn (FoundItem $item) => $item->created_at?->format('Y-m') ?? '-')
                    ->map(fn ($items, string $month) => ['month' => $month, 'total' => $items->count()])
                    ->values()
                    ->take(6),
            ],
        ]);
    }



    public function notifications(): JsonResponse
    {
        return response()->json([
            'pending_reports' => FoundItem::where('status', 'draft')->count(),
            'latest_reports' => FoundItem::query()
                ->where('status', 'draft')
                ->latest()
                ->limit(5)
                ->get(['id', 'name', 'category', 'location', 'status', 'created_at'])
                ->map(fn (FoundItem $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category,
                    'location' => $item->location,
                    'status' => $item->status,
                    'created_at' => $item->created_at?->toISOString(),
                ]),
        ]);
    }

    public function index(Request $request): Response
    {
        $this->lifecycleService->syncExpiredItems();

        $filters = $request->only(['q', 'status', 'category', 'location', 'per_page']);
        $allowedStatuses = ['tersedia', 'kadaluarsa'];
        $perPage = (int) ($filters['per_page'] ?? 10);
        $perPage = in_array($perPage, [10, 20, 50], true) ? $perPage : 10;
        $filters['per_page'] = $perPage;

        if (($filters['status'] ?? null) && ! in_array($filters['status'], $allowedStatuses, true)) {
            $filters['status'] = '';
        }

        $items = FoundItem::query()
            ->with(['manager', 'audits.user'])
            ->when($filters['q'] ?? null, function ($query, string $keyword): void {
                $query->where(function ($nested) use ($keyword): void {
                    $nested
                        ->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('finder_nim', 'like', "%{$keyword}%")
                        ->orWhere('claimant_name', 'like', "%{$keyword}%")
                        ->orWhere('claimant_nim', 'like', "%{$keyword}%")
                        ->orWhere('validation_notes', 'like', "%{$keyword}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when(! ($filters['status'] ?? null), fn ($query) => $query->whereIn('status', $allowedStatuses))
            ->when($filters['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
            ->when($filters['location'] ?? null, fn ($query, string $location) => $query->where('location', $location))
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (FoundItem $item) => $this->payloadService->admin($item));

        return Inertia::render('Admin/Items', [
            'items' => $items,
            'filters' => $filters,
            'categories' => config('sipb.categories'),
            'locations' => config('sipb.locations'),
        ]);
    }

    public function activity(Request $request): Response
    {
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
                'total_items' => \App\Models\FoundItem::count(),
                'items_with_photo' => \App\Models\FoundItem::whereNotNull('photo_data')->count(),
                'total_photo_size_bytes' => (int) \App\Models\FoundItem::whereNotNull('photo_data')->sum(\Illuminate\Support\Facades\DB::raw('LENGTH(photo_data)')),
            ],
        ]);
    }

    public function printReceipt(FoundItem $item): Response
    {
        return Inertia::render('Admin/PrintReceipt', [
            'item' => $this->payloadService->admin($item),
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
            ->where('status', 'sudah_diambil')
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

    public function export(): StreamedResponse
    {
        $this->lifecycleService->syncExpiredItems();

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID',
                'Jenis laporan',
                'Nama barang',
                'Kategori',
                'Lokasi',
                'Tanggal laporan',
                'Status',
                'Pelapor',
                'NIM pelapor',
                'Pengambil',
                'NIM pengambil',
                'Dipublikasi',
                'Diambil',
                'Kadaluarsa',
                'Catatan admin',
                'Catatan validasi',
            ]);

            FoundItem::query()
                ->latest()
                ->chunk(200, function ($items) use ($handle): void {
                    foreach ($items as $item) {
                        fputcsv($handle, [
                            $item->id,
                            'Temuan',
                            $item->name,
                            $item->category,
                            $item->location,
                            $item->found_at?->format('Y-m-d H:i:s'),
                            $item->status,
                            $item->finder_name,
                            $item->finder_nim,
                            $item->claimant_name,
                            $item->claimant_nim,
                            $item->published_at?->format('Y-m-d H:i:s'),
                            $item->claimed_at?->format('Y-m-d H:i:s'),
                            $item->expired_at?->format('Y-m-d H:i:s'),
                            $item->admin_notes,
                            $item->validation_notes,
                        ]);
                    }
                });

            fclose($handle);
        }, 'laporan-sipb.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportExcel(): StreamedResponse
    {
        $this->lifecycleService->syncExpiredItems();

        return response()->streamDownload(function (): void {
            echo '<table border="1">';
            echo '<tr><th>ID</th><th>Nama barang</th><th>Kategori</th><th>Lokasi</th><th>Tanggal laporan</th><th>Status</th><th>Pelapor</th><th>NIM pelapor</th><th>Pengambil</th><th>NIM pengambil</th><th>Dipublikasi</th><th>Diambil</th><th>Catatan admin</th><th>Catatan validasi</th></tr>';

            FoundItem::query()
                ->latest()
                ->chunk(200, function ($items): void {
                    foreach ($items as $item) {
                        $cells = [
                            $item->id,
                            $item->name,
                            $item->category,
                            $item->location,
                            $item->found_at?->format('Y-m-d H:i:s'),
                            $item->status,
                            $item->finder_name,
                            $item->finder_nim,
                            $item->claimant_name,
                            $item->claimant_nim,
                            $item->published_at?->format('Y-m-d H:i:s'),
                            $item->claimed_at?->format('Y-m-d H:i:s'),
                            $item->admin_notes,
                            $item->validation_notes,
                        ];

                        echo '<tr>';

                        foreach ($cells as $cell) {
                            echo '<td>'.e((string) $cell).'</td>';
                        }

                        echo '</tr>';
                    }
                });

            echo '</table>';
        }, 'laporan-sipb.xls', [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedCreate($request);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $data['photo_data'] = sprintf(
                'data:%s;base64,%s',
                $file->getMimeType(),
                base64_encode(file_get_contents($file->getRealPath()))
            );
            $data['photo_url'] = 'database:image';
        }

        unset($data['photo']);

        $statusData = $request->validate([
            'status' => ['nullable', 'in:tersedia'],
        ]);

        $data['status'] = $statusData['status'] ?? 'tersedia';
        $data['published_at'] = now();
        $data['managed_by'] = $request->user()->id;

        $item = FoundItem::create($data);
        $this->auditLogger->log($item, null, $item->status, 'admin_create', $request->user()->id);

        $message = 'Input barang berhasil dan langsung tampil di halaman cari.';

        return back()->with('success', $message);
    }

    public function update(Request $request, FoundItem $item): RedirectResponse
    {


        $data = $this->validatedUpdate($request, $item);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $data['photo_data'] = sprintf(
                'data:%s;base64,%s',
                $file->getMimeType(),
                base64_encode($file->getContent()),
            );
            $data['photo_url'] = 'database:image';
        }

        unset($data['photo']);

        $item->fill($data);
        $item->managed_by = $request->user()->id;
        $item->save();

        $this->auditLogger->log($item, $item->status, $item->status, 'admin_update', $request->user()->id, 'Data barang diperbarui admin.');

        return back()->with('success', 'Data barang berhasil diperbarui.');
    }

    public function updateStatus(Request $request, FoundItem $item): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:tersedia,sudah_diambil'],
            'claimant_name' => ['required_if:status,sudah_diambil', 'nullable', 'string', 'max:255'],
            'claimant_nim' => ['nullable', 'string', 'max:100'],
            'validation_notes' => ['nullable', 'string', 'max:1000'],
            'pickup_checklist' => ['nullable', 'array'],
            'pickup_checklist.identity_checked' => ['nullable', 'boolean'],
            'pickup_checklist.ownership_checked' => ['nullable', 'boolean'],
            'pickup_checklist.condition_checked' => ['nullable', 'boolean'],
        ]);

        $from = $item->status;

        $item->status = $data['status'];
        $item->managed_by = $request->user()->id;
        $item->validation_notes = $data['validation_notes'] ?? $item->validation_notes;

        if ($item->status === 'tersedia' && ($item->published_at === null || $from === 'kadaluarsa')) {
            $item->published_at = now();
        }

        if ($item->status === 'draft') {
            $item->published_at = null;
        }

        if ($item->status === 'sudah_diambil') {
            $checklist = $data['pickup_checklist'] ?? [];

            if (! ($checklist['identity_checked'] ?? false) || ! ($checklist['ownership_checked'] ?? false) || ! ($checklist['condition_checked'] ?? false)) {
                return back()->with('error', 'Checklist validasi pengambilan wajib lengkap sebelum barang ditandai sudah diambil.');
            }

            $item->claimed_at = now();
            $item->claimant_name = $data['claimant_name'] ?? null;
            $item->claimant_nim = $data['claimant_nim'] ?? null;
            $item->pickup_checklist = $checklist;
        }

        if ($item->status !== 'sudah_diambil') {
            $item->claimed_at = null;
            $item->claimant_name = null;
            $item->claimant_nim = null;
            $item->pickup_checklist = null;
        }

        $item->rejected_at = null;

        $item->expired_at = null;

        $item->save();
        $this->auditLogger->log($item, $from, $item->status, 'status_update', $request->user()->id, $data['validation_notes'] ?? null);

        return back()->with('success', 'Status barang berhasil diperbarui.');
    }

    public function destroy(Request $request, FoundItem $item): RedirectResponse
    {
        $this->auditLogger->log($item, $item->status, $item->status, 'admin_delete', $request->user()->id, 'Data barang dihapus oleh admin.');
        $item->delete();

        return back()->with('success', 'Data barang berhasil dihapus.');
    }

    private function validatedCreate(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:1000'],
            'location' => ['required', 'string', 'max:120'],
            'found_at' => ['required', 'date'],
            'photo' => ['required', 'image', 'max:4096'],
            'photo_url' => ['required_without:photo', 'nullable', 'url', 'max:500'],
            'finder_name' => ['required', 'string', 'max:120'],
            'finder_nim' => ['nullable', 'string', 'max:40'],
            'storage_location' => ['nullable', 'string', 'max:120'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function validatedUpdate(Request $request, FoundItem $item): array
    {
        return $request->validate([
            'report_type' => ['nullable', 'in:found'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:2000'],
            'location' => ['required', 'string', 'max:255'],
            'found_at' => ['required', 'date'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'finder_name' => ['nullable', 'string', 'max:255'],
            'finder_nim' => ['nullable', 'string', 'max:100'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function adminPayload(FoundItem $item): array
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

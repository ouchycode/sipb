<?php

namespace App\Http\Controllers;

use App\Models\FoundItem;
use App\Models\StatusAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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



    public function index(Request $request): Response
    {
        $this->lifecycleService->syncExpiredItems();

        $filters = $request->only(['q', 'status', 'category', 'location', 'per_page']);
        $allowedStatuses = [FoundItem::STATUS_AVAILABLE];
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
                // IP address anonymized before storage — see StatusAuditLogger
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
                'items_with_photo' => \App\Models\FoundItem::whereNotNull('photo_data')->orWhereNotNull('photo_path')->count(),
                'total_photo_size_bytes' => (int) \App\Models\FoundItem::whereNotNull('photo_data')->sum(\Illuminate\Support\Facades\DB::raw('LENGTH(photo_data)')),
            ],
        ]);
    }

    public function printReceipt(Request $request, FoundItem $item): Response|RedirectResponse
    {
        if ($item->status !== FoundItem::STATUS_CLAIMED) {
            return redirect()->back()->with('error', 'Barang belum diambil, tidak bisa mencetak tanda terima.');
        }

        $this->auditLogger->log($item, $item->status, $item->status, 'print_receipt', $request->user()->id, 'Tanda terima dicetak.');

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

    private function sanitizeCsvField(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }

    private function maskNim(?string $nim): string
    {
        if ($nim === null || $nim === '') {
            return '';
        }

        $visible = substr($nim, -3);
        $masked = str_repeat('*', max(0, strlen($nim) - 3));

        return $masked . $visible;
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
                            $this->sanitizeCsvField($item->name),
                            $item->category,
                            $item->location,
                            $item->found_at?->format('Y-m-d H:i:s'),
                            $item->status,
                            $this->sanitizeCsvField($item->finder_name),
                            $this->maskNim($item->finder_nim),
                            $this->sanitizeCsvField($item->claimant_name),
                            $this->maskNim($item->claimant_nim),
                            $item->published_at?->format('Y-m-d H:i:s'),
                            $item->claimed_at?->format('Y-m-d H:i:s'),
                            $item->expired_at?->format('Y-m-d H:i:s'),
                            $this->sanitizeCsvField($item->admin_notes),
                            $this->sanitizeCsvField($item->validation_notes),
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
                            $this->maskNim($item->finder_nim),
                            $item->claimant_name,
                            $this->maskNim($item->claimant_nim),
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

        $uploaded = \App\Models\UploadedPhoto::find($request->integer('uploaded_photo_id'));
        if ($uploaded) {
            if ($uploaded->photo_path) {
                $data['photo_path'] = $uploaded->photo_path;
                $data['photo_url'] = \Illuminate\Support\Facades\Storage::url($uploaded->photo_path);
            } else {
                $data['photo_data'] = $uploaded->photo_data;
                $data['photo_url'] = 'database:image';
            }
            $uploaded->update(['used_at' => now()]);
        }

        unset($data['uploaded_photo_id']);

        $statusData = $request->validate([
            'status' => ['nullable', 'in:' . FoundItem::STATUS_AVAILABLE],
        ]);

        $data['status'] = $statusData['status'] ?? FoundItem::STATUS_AVAILABLE;
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
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            $mime = $file->getMimeType();
            if (!in_array($mime, $allowedMimes, true)) {
                return back()->with('error', 'Foto harus berupa JPG, PNG, atau WEBP.');
            }
            $path = $file->store('photos', 'public');
            $data['photo_path'] = $path;
            $data['photo_data'] = null;
            $data['photo_url'] = \Illuminate\Support\Facades\Storage::url($path);
        } elseif ($request->filled('uploaded_photo_id')) {
            $uploaded = \App\Models\UploadedPhoto::find($request->integer('uploaded_photo_id'));
            if ($uploaded) {
                if ($uploaded->photo_path) {
                    $data['photo_path'] = $uploaded->photo_path;
                    $data['photo_data'] = null;
                    $data['photo_url'] = \Illuminate\Support\Facades\Storage::url($uploaded->photo_path);
                } else {
                    $data['photo_data'] = $uploaded->photo_data;
                    $data['photo_url'] = 'database:image';
                }
                $uploaded->update(['used_at' => now()]);
            }
        }

        unset($data['photo']);
        unset($data['uploaded_photo_id']);

        $item->fill($data);
        $item->managed_by = $request->user()->id;
        $item->save();

        $this->auditLogger->log($item, $item->status, $item->status, 'admin_update', $request->user()->id, 'Data barang diperbarui admin.');

        return back()->with('success', 'Data barang berhasil diperbarui.');
    }

    public function updateStatus(Request $request, FoundItem $item): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:' . FoundItem::STATUS_AVAILABLE . ',' . FoundItem::STATUS_CLAIMED],
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

        if ($item->status === FoundItem::STATUS_AVAILABLE && $item->published_at === null) {
            $item->published_at = now();
        }



        if ($item->status === FoundItem::STATUS_CLAIMED) {
            $checklist = $data['pickup_checklist'] ?? [];

            if (! ($checklist['identity_checked'] ?? false) || ! ($checklist['ownership_checked'] ?? false) || ! ($checklist['condition_checked'] ?? false)) {
                return back()->with('error', 'Checklist validasi pengambilan wajib lengkap sebelum barang ditandai sudah diambil.');
            }

            $item->claimed_at = now();
            $item->claimant_name = $data['claimant_name'] ?? null;
            $item->claimant_nim = $data['claimant_nim'] ?? null;
            $item->pickup_checklist = $checklist;
        }

        if ($item->status !== FoundItem::STATUS_CLAIMED) {
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
        if (!$request->user()->isSuperAdmin()) {
            return back()->with('error', 'Hanya super admin yang dapat menghapus data barang.');
        }

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
            'uploaded_photo_id' => ['required', 'integer', 'exists:uploaded_photos,id'],
            'finder_name' => ['nullable', 'string', 'max:120'],
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
            'uploaded_photo_id' => ['nullable', 'integer', 'exists:uploaded_photos,id'],
            'finder_name' => ['nullable', 'string', 'max:255'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

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

class ItemController extends Controller
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

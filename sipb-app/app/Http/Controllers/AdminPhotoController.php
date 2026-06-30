<?php

namespace App\Http\Controllers;

use App\Models\UploadedPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminPhotoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Photos', [
            'photos' => $this->getPhotos(),
        ]);
    }

    public function listJson(Request $request): JsonResponse
    {
        $query = $this->baseQuery();

        if ($request->boolean('unused')) {
            $query->whereNull('used_at');
        }

        return response()->json(
            $query->latest()->limit(50)->get()->map(fn (UploadedPhoto $p) => [
                'id' => $p->id,
                'photo_url' => $p->photo_url,
                'created_at' => $p->created_at->toISOString(),
            ])
        );
    }

    private function getPhotos(): array
    {
        return $this->baseQuery()
            ->whereNull('used_at')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (UploadedPhoto $p) => [
                'id' => $p->id,
                'photo_url' => $p->photo_url,
                'created_at' => $p->created_at->toISOString(),
                'used_at' => $p->used_at?->toISOString(),
            ])
            ->toArray();
    }

    private function baseQuery()
    {
        return UploadedPhoto::query()->where('user_id', auth()->id());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'max:4096'],
        ]);

        $file = $request->file('photo');
        $path = $file->store('photos', 'public');

        $photo = UploadedPhoto::create([
            'user_id' => $request->user()->id,
            'photo_data' => null,
            'photo_path' => $path,
        ]);

        return response()->json([
            'id' => $photo->id,
            'photo_url' => Storage::url($path),
            'created_at' => $photo->created_at->toISOString(),
        ]);
    }

    public function destroy(Request $request, UploadedPhoto $photo): RedirectResponse
    {
        if ($photo->user_id !== null && $photo->user_id !== $request->user()->id) {
            return back()->with('error', 'Anda tidak berhak menghapus foto ini.');
        }

        if ($photo->used_at) {
            return back()->with('error', 'Foto ini sudah digunakan di laporan barang dan tidak bisa dihapus.');
        }

        $photo->delete();

        return back()->with('success', 'Foto berhasil dihapus.');
    }
}

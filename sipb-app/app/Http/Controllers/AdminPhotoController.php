<?php

namespace App\Http\Controllers;

use App\Models\UploadedPhoto;
use Illuminate\Http\JsonResponse;
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
            $query->latest()->limit(50)->get(['id', 'photo_data', 'created_at'])->toArray()
        );
    }

    private function getPhotos(): array
    {
        return $this->baseQuery()
            ->whereNull('used_at')
            ->latest()
            ->limit(50)
            ->get(['id', 'photo_data', 'created_at', 'used_at'])
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
        $mime = $file->getMimeType();

        $photoData = sprintf(
            'data:%s;base64,%s',
            $mime,
            base64_encode($file->getContent()),
        );

        $photo = UploadedPhoto::create([
            'user_id' => $request->user()->id,
            'photo_data' => $photoData,
        ]);

        return response()->json([
            'id' => $photo->id,
            'photo_data' => $photoData,
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

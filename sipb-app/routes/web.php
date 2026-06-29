<?php

use App\Http\Controllers\AdminItemController;
use App\Http\Controllers\AdminPhotoController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicItemController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicItemController::class, 'home'])->name('public.home');
Route::get('/cari', [PublicItemController::class, 'index'])->name('public.index');
Route::get('/bantuan', [PublicItemController::class, 'help'])->name('public.help');

Route::get('/barang/{item}', [PublicItemController::class, 'show'])->name('public.show');
Route::post('/ai/chat', [AiAssistantController::class, 'chat'])->middleware('throttle:20,1')->name('ai.chat');
Route::get('/swagger', [\App\Http\Controllers\SwaggerController::class, 'index'])->name('swagger');

Route::get('/sitemap.xml', function () {
    $items = \App\Models\FoundItem::where('status', 'tersedia')
        ->whereNotNull('published_at')
        ->where('published_at', '>=', now()->subDays(30))
        ->get();

    return response()->view('sitemap', ['items' => $items])->header('Content-Type', 'application/xml');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:10,1')->name('admin.login.store');
    Route::get('/admin/login', fn () => redirect('/login'))->name('admin.login');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [AdminItemController::class, 'dashboard'])->name('dashboard');
    Route::get('/barang', [AdminItemController::class, 'index'])->name('items.index');
    Route::get('/history', [AdminItemController::class, 'history'])->name('items.history');
    Route::get('/aktivitas', [AdminItemController::class, 'activity'])->name('activity');
    Route::get('/profile', [AdminUserController::class, 'profile'])->name('profile');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::get('/notifications', [AdminItemController::class, 'notifications'])->name('notifications');
    Route::get('/foto', [AdminPhotoController::class, 'index'])->name('photos.index');
    Route::post('/upload-photo', [AdminPhotoController::class, 'store'])->name('photos.store');
    Route::delete('/uploaded-photos/{photo}', [AdminPhotoController::class, 'destroy'])->name('photos.destroy');
    Route::get('/uploaded-photos', [AdminPhotoController::class, 'listJson'])->name('photos.list');

    Route::get('/export', [AdminItemController::class, 'export'])->name('items.export');
    Route::get('/export-excel', [AdminItemController::class, 'exportExcel'])->name('items.export-excel');
    Route::get('/barang/{item}/tanda-terima', [AdminItemController::class, 'printReceipt'])->name('items.receipt');
    Route::post('/barang', [AdminItemController::class, 'store'])->name('items.store');
    Route::post('/barang/{item}', [AdminItemController::class, 'update'])->name('items.update');
    Route::delete('/barang/{item}', [AdminItemController::class, 'destroy'])->name('items.destroy');
    Route::patch('/barang/{item}/status', [AdminItemController::class, 'updateStatus'])->name('items.status');

    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});

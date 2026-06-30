<?php

namespace App\Console\Commands;

use App\Models\FoundItem;
use App\Models\UploadedPhoto;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('app:migrate-photos-to-storage')]
#[Description('Migrate existing Base64 photo data to file storage')]
class MigratePhotosToStorage extends Command
{
    public function handle(): void
    {
        $migrated = 0;
        $disk = 'public';

        foreach (FoundItem::whereNotNull('photo_data')->whereNull('photo_path')->cursor() as $item) {
            $path = $this->base64ToFile($item->photo_data, $disk);
            if ($path) {
                $item->photo_path = $path;
                $item->photo_url = Storage::disk($disk)->url($path);
                $item->save();
                $migrated++;
            }
        }

        $this->info("Migrated {$migrated} FoundItem photos to storage.");

        $uploadedMigrated = 0;
        foreach (UploadedPhoto::whereNotNull('photo_data')->whereNull('photo_path')->cursor() as $photo) {
            $path = $this->base64ToFile($photo->photo_data, $disk);
            if ($path) {
                $photo->photo_path = $path;
                $photo->save();
                $uploadedMigrated++;
            }
        }

        $this->info("Migrated {$uploadedMigrated} UploadedPhoto photos to storage.");
        $this->info('Done. Run "php artisan storage:link" if you have not already.');
    }

    private function base64ToFile(?string $dataUri, string $disk): ?string
    {
        if (!$dataUri || !str_starts_with($dataUri, 'data:')) {
            return null;
        }

        $parts = explode(',', $dataUri, 2);
        if (count($parts) !== 2) {
            return null;
        }

        $decoded = base64_decode($parts[1], true);
        if ($decoded === false) {
            return null;
        }

        $ext = 'jpg';
        if (preg_match('/image\/(\w+)/', $parts[0], $m)) {
            $ext = match ($m[1]) {
                'jpeg', 'pjpeg' => 'jpg',
                'png' => 'png',
                'webp' => 'webp',
                'gif' => 'gif',
                default => 'jpg',
            };
        }

        $filename = 'photos/' . \Illuminate\Support\Str::random(40) . '.' . $ext;
        Storage::disk($disk)->put($filename, $decoded);

        return $filename;
    }
}

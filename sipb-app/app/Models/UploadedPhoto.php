<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UploadedPhoto extends Model
{
    protected $fillable = ['user_id', 'photo_data', 'photo_path', 'used_at'];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo_path) {
            return Storage::url($this->photo_path);
        }

        return $this->photo_data;
    }
}

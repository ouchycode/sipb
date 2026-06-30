<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadedPhoto extends Model
{
    protected $fillable = ['user_id', 'photo_data', 'photo_path', 'used_at'];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }
}

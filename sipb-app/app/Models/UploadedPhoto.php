<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadedPhoto extends Model
{
    protected $fillable = ['user_id', 'photo_data', 'used_at'];
}

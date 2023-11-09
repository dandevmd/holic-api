<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageManipulation extends Model
{
    use HasFactory;

    const TYPE_RESIZE = 'resize';
    const UPDATED_AT = null;
    protected $fillable = [
        'name',
        'type',
        'data',
        'path',
        'output_path',
        'user_id',
        'album_id',
    ];
}
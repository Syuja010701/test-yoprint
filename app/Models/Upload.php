<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $fillable = [
        'filename',
        'file_path',
        'file_hash',
        'status',
        'message',
    ];
}

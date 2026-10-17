<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hadith extends Model
{
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'hints' => 'array',
        'words_meanings' => 'array',
    ];
}

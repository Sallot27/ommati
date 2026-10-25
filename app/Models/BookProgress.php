<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookProgress extends Model
{
    protected $table = 'book_progress';

    protected $guarded = [];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}

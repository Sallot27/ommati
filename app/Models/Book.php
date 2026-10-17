<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Book extends Model
{
    protected $guarded = [];

    protected $casts = [
        'last_opened_at' => 'datetime',
        'size' => 'integer',
    ];

    public function isPdf(): bool
    {
        return $this->mime === 'application/pdf' || str_ends_with(strtolower($this->path), '.pdf');
    }

    public function humanSize(): string
    {
        $mb = $this->size / 1048576;

        return $mb >= 1 ? number_format($mb, 1).' م.ب' : max(1, (int) round($this->size / 1024)).' ك.ب';
    }

    public function extension(): string
    {
        return strtoupper(pathinfo($this->path, PATHINFO_EXTENSION) ?: 'FILE');
    }

    public function deleteFile(): void
    {
        Storage::disk($this->disk)->delete($this->path);
    }
}

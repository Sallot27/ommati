<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Library
{
    public const EXTENSIONS = ['pdf', 'epub', 'doc', 'docx', 'txt', 'mp3', 'm4a'];

    public static function disk(): string
    {
        $disk = config('filesystems.books_disk');
        if ($disk) {
            return $disk;
        }
        $default = config('filesystems.default');

        return $default === 'local' ? 'public' : $default;
    }

    /** @param resource|string $contents */
    public function storeContents($contents, string $originalName, array $attributes = []): Book
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) ?: 'pdf';
        $path = 'books/'.now()->format('Y/m').'/'.Str::uuid().'.'.$ext;
        $disk = self::disk();
        Storage::disk($disk)->put($path, $contents);

        return Book::query()->create(array_merge([
            'title' => self::titleFromFilename($originalName),
            'disk' => $disk,
            'path' => $path,
            'original_name' => $originalName,
            'mime' => self::mimeFor($ext),
            'size' => Storage::disk($disk)->size($path),
        ], array_filter($attributes, fn ($v) => $v !== null && $v !== '')));
    }

    public function storeUpload(UploadedFile $file, array $attributes = []): Book
    {
        $stream = fopen($file->getRealPath(), 'r');
        try {
            return $this->storeContents($stream, $file->getClientOriginalName(), $attributes);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public static function titleFromFilename(string $name): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = preg_replace('/[_\-.]+/u', ' ', $base);
        $base = preg_replace('/@\w+/u', '', $base); // drop channel handles
        $base = trim(preg_replace('/\s+/u', ' ', $base));

        return $base !== '' ? Str::limit($base, 180, '') : 'كتاب بدون عنوان';
    }

    public static function mimeFor(string $ext): string
    {
        return match ($ext) {
            'pdf' => 'application/pdf',
            'epub' => 'application/epub+zip',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain; charset=utf-8',
            'mp3' => 'audio/mpeg',
            'm4a' => 'audio/mp4',
            default => 'application/octet-stream',
        };
    }
}

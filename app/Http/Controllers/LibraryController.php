<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LibraryController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $category = $request->query('c');

        $books = Book::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('title', 'like', "%$q%")->orWhere('author', 'like', "%$q%")))
            ->when($category, fn ($query) => $query->where('category', $category))
            ->orderByRaw('last_opened_at is null')->orderByDesc('last_opened_at')->latest()
            ->get();

        return view('library.index', [
            'books' => $books,
            'categories' => Book::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'q' => $q,
            'category' => $category,
        ]);
    }

    public function show(Book $book)
    {
        $book->update(['last_opened_at' => now()]);

        return view('library.show', ['book' => $book]);
    }

    public function file(Request $request, Book $book)
    {
        $disk = Storage::disk($book->disk);
        $download = $request->boolean('download');
        $filename = $book->title.'.'.strtolower($book->extension());

        // Object storage: hand the browser a short-lived direct link.
        try {
            if (config("filesystems.disks.{$book->disk}.driver") === 's3') {
                return redirect()->away($disk->temporaryUrl($book->path, now()->addHours(2), [
                    'ResponseContentType' => $book->mime,
                    'ResponseContentDisposition' => ($download ? 'attachment' : 'inline').'; filename*=UTF-8\'\''.rawurlencode($filename),
                ]));
            }
        } catch (Throwable) {
            // fall through to streaming
        }

        abort_unless($disk->exists($book->path), 404);

        return $disk->response($book->path, $filename, ['Content-Type' => $book->mime], $download ? 'attachment' : 'inline');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookBookmark;
use App\Models\BookProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class LibraryController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $category = $request->query('c');
        $user = $request->user();

        $books = Book::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('title', 'like', "%$q%")->orWhere('author', 'like', "%$q%")))
            ->when($category, fn ($query) => $query->where('category', $category))
            ->latest()
            ->get();

        $progress = BookProgress::query()->where('user_id', $user->id)->get()->keyBy('book_id');

        return view('library.index', [
            'books' => $books,
            'progress' => $progress,
            'reading' => $q === '' && ! $category
                ? $books->filter(fn ($b) => isset($progress[$b->id]))->sortByDesc(fn ($b) => $progress[$b->id]->updated_at)->take(3)->values()
                : collect(),
            'categories' => Book::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'q' => $q,
            'category' => $category,
        ]);
    }

    public function show(Request $request, Book $book)
    {
        $book->update(['last_opened_at' => now()]);
        $user = $request->user();

        return view('library.show', [
            'book' => $book,
            'progress' => $book->progressFor($user),
            'bookmarks' => BookBookmark::query()->where('user_id', $user->id)->where('book_id', $book->id)->orderBy('page')->get(),
        ]);
    }

    public function read(Request $request, Book $book)
    {
        abort_unless($book->isPdf(), 404);
        $user = $request->user();
        $progress = $book->progressFor($user);
        $page = (int) $request->query('page', $progress?->page ?? 1);

        return view('library.read', [
            'book' => $book,
            'page' => max(1, $page),
            'bookmarks' => BookBookmark::query()->where('user_id', $user->id)->where('book_id', $book->id)->orderBy('page')->get(['id', 'page', 'note']),
        ]);
    }

    /** Remember the page the reader is on (called while reading). */
    public function progress(Request $request, Book $book)
    {
        $data = $request->validate([
            'page' => 'required|integer|min:1|max:100000',
            'pages' => 'nullable|integer|min:1|max:100000',
            'note' => 'nullable|string|max:300',
        ]);

        BookProgress::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'book_id' => $book->id],
            array_filter(['page' => $data['page'], 'note' => $data['note'] ?? null], fn ($v) => $v !== null),
        );
        if (! empty($data['pages']) && $book->pages !== (int) $data['pages']) {
            $book->update(['pages' => (int) $data['pages']]);
        }

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return back()->with('status', 'تم حفظ مكان التوقف.');
    }

    public function addBookmark(Request $request, Book $book)
    {
        $data = $request->validate(['page' => 'required|integer|min:1|max:100000', 'note' => 'nullable|string|max:300']);
        $bookmark = BookBookmark::query()->create(['user_id' => $request->user()->id, 'book_id' => $book->id] + $data);

        if ($request->expectsJson()) {
            return response()->json($bookmark->only(['id', 'page', 'note']));
        }

        return back()->with('status', 'تمت إضافة العلامة.');
    }

    public function deleteBookmark(Request $request, Book $book, BookBookmark $bookmark)
    {
        abort_unless($bookmark->user_id === $request->user()->id && $bookmark->book_id === $book->id, 404);
        $bookmark->delete();

        return $request->expectsJson() ? response()->noContent() : back();
    }

    /**
     * Save a cover image rendered in the browser from the PDF's first page.
     * Only accepted while the book has no cover, so it can't overwrite one set by the admin.
     */
    public function storeCover(Request $request, Book $book)
    {
        if ($book->cover_path) {
            return response()->noContent();
        }
        $request->validate(['cover' => 'required|file|mimes:jpg,jpeg,png,webp|max:600']);
        $path = 'covers/'.$book->id.'-'.Str::random(8).'.jpg';
        Storage::disk($book->disk)->put($path, file_get_contents($request->file('cover')->getRealPath()));
        $book->update(['cover_path' => $path]);

        return response()->noContent();
    }

    public function cover(Book $book)
    {
        abort_unless($book->cover_path, 404);
        $disk = Storage::disk($book->disk);
        abort_unless($disk->exists($book->cover_path), 404);

        return $disk->response($book->cover_path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }

    /** Same-origin stream of the book file (used by the in-app PDF reader). */
    public function stream(Book $book): StreamedResponse
    {
        $disk = Storage::disk($book->disk);
        abort_unless($disk->exists($book->path), 404);
        $size = $disk->size($book->path);

        return response()->stream(function () use ($disk, $book) {
            $in = $disk->readStream($book->path);
            fpassthru($in);
            if (is_resource($in)) {
                fclose($in);
            }
        }, 200, [
            'Content-Type' => $book->mime ?: 'application/pdf',
            'Content-Length' => (string) $size,
            'Cache-Control' => 'private, max-age=86400',
        ]);
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

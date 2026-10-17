<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\Library;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index()
    {
        return view('admin.books.index', ['books' => Book::query()->latest()->paginate(30)]);
    }

    public function create()
    {
        return view('admin.books.form', ['book' => new Book, 'categories' => $this->categories()]);
    }

    public function store(Request $request, Library $library)
    {
        $data = $request->validate([
            'files' => 'required|array|min:1|max:20',
            'files.*' => 'file|max:204800|extensions:'.implode(',', Library::EXTENSIONS),
            'title' => 'nullable|string|max:200',
            'author' => 'nullable|string|max:120',
            'category' => 'nullable|string|max:80',
            'description' => 'nullable|string|max:2000',
        ]);

        $files = $request->file('files');
        foreach ($files as $file) {
            $library->storeUpload($file, [
                // A typed title only applies when uploading a single file.
                'title' => count($files) === 1 ? ($data['title'] ?? null) : null,
                'author' => $data['author'] ?? null,
                'category' => $data['category'] ?? null,
                'description' => $data['description'] ?? null,
            ]);
        }

        return redirect()->route('admin.books.index')->with('status', count($files) === 1 ? 'تمت إضافة الكتاب.' : 'تمت إضافة '.count($files).' كتب.');
    }

    public function edit(Book $book)
    {
        return view('admin.books.form', ['book' => $book, 'categories' => $this->categories()]);
    }

    public function update(Request $request, Book $book)
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'author' => 'nullable|string|max:120',
            'category' => 'nullable|string|max:80',
            'description' => 'nullable|string|max:2000',
        ]);
        $book->update($data);

        return redirect()->route('admin.books.index')->with('status', 'تم حفظ التعديلات.');
    }

    public function destroy(Book $book)
    {
        $book->deleteFile();
        $book->delete();

        return redirect()->route('admin.books.index')->with('status', 'تم حذف الكتاب.');
    }

    private function categories()
    {
        return Book::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')
            ->merge(['تفسير', 'حديث', 'عقيدة', 'فقه', 'سيرة', 'رقائق', 'أذكار وأدعية', 'تجويد'])->unique()->values();
    }
}

@extends('layouts.admin')
@section('title', 'الكتب')
@section('content')
<div class="mb-4 flex items-center justify-between">
    <h1 class="text-xl font-semibold">الكتب</h1>
    <a href="{{ route('admin.books.create') }}" class="btn btn-primary">رفع كتب</a>
</div>
<div class="card divide-y divide-line">
    @forelse ($books as $book)
        <div class="flex flex-wrap items-center gap-3 px-4 py-3">
            <span class="rounded bg-surface-2 px-2 py-0.5 text-xs">{{ $book->extension() }}</span>
            <div class="min-w-0 flex-1">
                <p class="font-medium">{{ $book->title }}</p>
                <p class="text-sm text-muted">{{ collect([$book->author, $book->category, $book->humanSize(), $book->source === 'telegram' ? 'من تيلغرام' : null])->filter()->implode(' · ') }}</p>
            </div>
            <a href="{{ route('admin.books.edit', $book) }}" class="text-lapis-ink">تعديل</a>
            <form method="POST" action="{{ route('admin.books.destroy', $book) }}" onsubmit="return confirm('حذف الكتاب نهائياً؟')">
                @csrf @method('DELETE')
                <button class="text-rose">حذف</button>
            </form>
        </div>
    @empty
        <p class="px-4 py-6 text-muted">لا توجد كتب بعد. ارفع كتاباً، أو أرسله لبوت تيلغرام.</p>
    @endforelse
</div>
<div class="mt-4">{{ $books->links() }}</div>
@endsection

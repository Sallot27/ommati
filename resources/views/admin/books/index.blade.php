@extends('layouts.admin')
@section('title', 'الكتب')
@section('content')
<div class="mb-4 flex items-center justify-between">
    <h1 class="text-xl font-semibold">الكتب</h1>
    <a href="{{ route('admin.books.create') }}" class="btn btn-primary">رفع كتب</a>
</div>
@php $missing = $books->filter(fn ($b) => $b->isPdf() && ! $b->hasCover()); @endphp
@if ($missing->isNotEmpty())
    <p class="mb-3 rounded-xl bg-saffron-soft px-4 py-2 text-sm" data-needs-pdf id="cover-status">جاري تجهيز أغلفة {{ $missing->count() }} كتب من صفحتها الأولى… اتركي الصفحة مفتوحة.</p>
    <script>
        document.addEventListener('pdf-ready', async () => {
            await window.generateMissingCovers(@js($missing->map(fn ($b) => ['streamUrl' => route('library.stream', $b), 'coverUrl' => route('library.cover.store', $b)])->values()));
            document.getElementById('cover-status').textContent = 'تم تجهيز الأغلفة ✓ حدّثي الصفحة لرؤيتها.';
        });
    </script>
@endif
<div class="card divide-y divide-line">
    @forelse ($books as $book)
        <div class="flex flex-wrap items-center gap-3 px-4 py-3">
            <x-book-cover :book="$book" size="sm" />
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

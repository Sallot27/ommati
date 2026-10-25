@extends('layouts.app')
@section('title', 'المكتبة')
@section('back', route('more'))

@section('content')
@php $D = fn ($n) => \App\Services\Quran::digits($n); @endphp

@if ($reading->isNotEmpty())
    <section class="mb-6">
        <h2 class="section-title mb-2">أكملي القراءة</h2>
        <ul class="space-y-2">
            @foreach ($reading as $book)
                @php $p = $progress[$book->id]; @endphp
                <li>
                    <a href="{{ $book->isPdf() ? route('library.read', $book) : route('library.show', $book) }}" class="card flex items-center gap-4 px-4 py-3 hover:bg-surface-2">
                        <x-book-cover :book="$book" size="sm" />
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium">{{ $book->title }}</span>
                            <span class="text-sm text-muted">توقفتِ عند صفحة {{ $D($p->page) }}{{ $book->pages ? ' من '.$D($book->pages) : '' }}</span>
                            @if ($book->pages)
                                <span class="mt-1.5 block h-1.5 overflow-hidden rounded-full bg-surface-2"><span class="block h-full bg-teal" style="width: {{ min(100, $p->page / $book->pages * 100) }}%"></span></span>
                            @endif
                        </span>
                        <x-icon name="forward" :size="18" class="text-muted" />
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif

<form method="GET" class="mb-3">
    <label class="relative block">
        <span class="sr-only">ابحثي في الكتب</span>
        <x-icon name="search" :size="20" class="pointer-events-none absolute start-4 top-1/2 -translate-y-1/2 text-muted" />
        <input name="q" value="{{ $q }}" type="search" placeholder="ابحثي باسم الكتاب أو المؤلف" class="w-full rounded-2xl border border-line bg-surface py-3.5 ps-12 pe-4 text-lg outline-none focus:border-lapis">
    </label>
</form>

@if ($categories->isNotEmpty())
    <div class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1">
        <a href="{{ route('library.index') }}" @class(['chip shrink-0 px-4 py-1.5', 'bg-lapis-ink text-white dark:text-[#0f1823]' => ! $category, 'bg-surface-2' => $category])>الكل</a>
        @foreach ($categories as $c)
            <a href="{{ route('library.index', ['c' => $c]) }}" @class(['chip shrink-0 px-4 py-1.5', 'bg-lapis-ink text-white dark:text-[#0f1823]' => $category === $c, 'bg-surface-2' => $category !== $c])>{{ $c }}</a>
        @endforeach
    </div>
@endif

@if ($books->isEmpty())
    <div class="card px-5 py-8 text-center">
        <x-icon name="library" :size="40" class="mx-auto text-muted" />
        <p class="mt-3 text-lg">{{ $q || $category ? 'لا توجد كتب مطابقة.' : 'المكتبة فارغة حالياً.' }}</p>
        <p class="mt-1 text-muted">تُضاف الكتب من لوحة التحكم أو من بوت تيلغرام.</p>
    </div>
@else
    <ul class="grid grid-cols-3 gap-x-3 gap-y-5 sm:grid-cols-4">
        @foreach ($books as $book)
            @php $p = $progress[$book->id] ?? null; @endphp
            <li>
                <a href="{{ route('library.show', $book) }}" class="block">
                    <x-book-cover :book="$book" size="md" class="!h-auto !w-full aspect-[3/4]" />
                    <span class="naskh mt-1.5 line-clamp-2 block text-[0.95rem] leading-snug">{{ $book->title }}</span>
                    @if ($p)
                        <span class="text-xs text-teal">صفحة {{ $D($p->page) }}</span>
                    @elseif ($book->author)
                        <span class="block truncate text-xs text-muted">{{ $book->author }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
@endif
@endsection

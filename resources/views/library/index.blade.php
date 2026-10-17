@extends('layouts.app')
@section('title', 'المكتبة')
@section('back', route('more'))

@section('content')
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

@forelse ($books as $book)
    <a href="{{ route('library.show', $book) }}" class="card mb-2 flex items-center gap-4 px-4 py-3.5 hover:bg-surface-2">
        <span class="grid h-16 w-12 shrink-0 place-items-center rounded-lg bg-lapis-ink text-[0.65rem] font-semibold text-white/90 dark:bg-surface-2">{{ $book->extension() }}</span>
        <span class="min-w-0 flex-1">
            <span class="block text-lg font-medium leading-snug">{{ $book->title }}</span>
            <span class="text-sm text-muted">{{ collect([$book->author, $book->category])->filter()->implode(' — ') ?: $book->humanSize() }}</span>
        </span>
    </a>
@empty
    <div class="card px-5 py-8 text-center">
        <x-icon name="library" :size="40" class="mx-auto text-muted" />
        <p class="mt-3 text-lg">{{ $q || $category ? 'لا توجد كتب مطابقة.' : 'المكتبة فارغة حالياً.' }}</p>
        <p class="mt-1 text-muted">تُضاف الكتب من لوحة التحكم أو من بوت تيلغرام.</p>
    </div>
@endforelse
@endsection

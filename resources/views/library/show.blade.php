@extends('layouts.app')
@section('title', $book->title)
@section('back', route('library.index'))

@section('content')
@php $D = fn ($n) => \App\Services\Quran::digits($n); $isAudio = str_starts_with((string) $book->mime, 'audio/'); @endphp
<div class="space-y-4">
    <section class="card px-5 py-5">
        <div class="flex gap-4">
            <x-book-cover :book="$book" size="lg" class="!h-40 !w-28" />
            <div class="min-w-0 flex-1">
                <h2 class="naskh text-2xl font-semibold leading-snug">{{ $book->title }}</h2>
                @if ($book->author)<p class="mt-1 text-lg text-muted">{{ $book->author }}</p>@endif
                <p class="mt-2 text-sm text-muted">{{ $book->extension() }} · {{ $book->humanSize() }}{{ $book->pages ? ' · '.$D($book->pages).' صفحة' : '' }}{{ $book->category ? ' · '.$book->category : '' }}</p>
            </div>
        </div>
        @if ($book->description)<p class="mt-4 whitespace-pre-line leading-relaxed">{{ $book->description }}</p>@endif

        <div class="mt-5 grid gap-2">
            @if ($isAudio)
                <audio controls preload="none" class="w-full" src="{{ route('library.file', $book) }}"></audio>
            @elseif ($book->isPdf())
                <a href="{{ route('library.read', $book) }}" class="btn btn-primary text-lg">
                    <x-icon name="book" />
                    {{ $progress ? 'أكملي من صفحة '.$D($progress->page) : 'ابدئي القراءة' }}
                </a>
                @if ($progress)
                    <a href="{{ route('library.read', ['book' => $book, 'page' => 1]) }}" class="btn btn-soft">من البداية</a>
                @endif
            @else
                <a href="{{ route('library.file', $book) }}" target="_blank" class="btn btn-primary text-lg"><x-icon name="book" /> افتحي الكتاب</a>
            @endif
            <a href="{{ route('library.file', ['book' => $book, 'download' => 1]) }}" class="btn btn-soft"><x-icon name="download" :size="20" /> حمّلي نسخة على الجهاز</a>
        </div>
    </section>

    @unless ($book->isPdf() || $isAudio)
        {{-- Non-PDF books open outside the app, so the reader records where she stopped by hand. --}}
        <form method="POST" action="{{ route('library.progress', $book) }}" class="card space-y-3 px-5 py-5">
            @csrf
            <h3 class="section-title">مكان التوقف</h3>
            <div class="flex gap-2">
                <label class="flex-1">
                    <span class="text-sm text-muted">الصفحة</span>
                    <input type="number" name="page" min="1" required value="{{ $progress?->page }}" class="mt-1 w-full rounded-xl border border-line bg-surface-2 px-3 py-3 text-lg">
                </label>
            </div>
            <label class="block">
                <span class="text-sm text-muted">ملاحظة (اختياري)</span>
                <input name="note" maxlength="300" value="{{ $progress?->note }}" placeholder="مثال: باب الصبر" class="mt-1 w-full rounded-xl border border-line bg-surface-2 px-3 py-3">
            </label>
            <button class="btn btn-primary w-full">حفظ مكان التوقف</button>
        </form>
    @endunless

    @if ($bookmarks->isNotEmpty())
        <section class="card px-5 py-4">
            <h3 class="section-title">علاماتك في هذا الكتاب</h3>
            <ul class="mt-2 divide-y divide-line">
                @foreach ($bookmarks as $b)
                    <li class="flex items-center gap-3 py-2.5">
                        <x-icon name="star" :size="18" class="text-saffron" />
                        <a href="{{ route('library.read', ['book' => $book, 'page' => $b->page]) }}" class="flex-1">
                            <span class="font-medium">صفحة {{ $D($b->page) }}</span>
                            @if ($b->note)<span class="block text-sm text-muted">{{ $b->note }}</span>@endif
                        </a>
                        <form method="POST" action="{{ route('library.bookmarks.destroy', [$book, $b]) }}">@csrf @method('DELETE')
                            <button class="grid size-9 place-items-center rounded-full text-muted hover:bg-surface-2" aria-label="حذف العلامة"><x-icon name="x" :size="18" /></button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
@endsection

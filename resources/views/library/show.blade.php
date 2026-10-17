@extends('layouts.app')
@section('title', $book->title)
@section('back', route('library.index'))

@section('content')
<div class="space-y-4">
    <section class="card px-5 py-5">
        <h2 class="text-2xl font-semibold leading-snug">{{ $book->title }}</h2>
        @if ($book->author)<p class="mt-1 text-lg text-muted">{{ $book->author }}</p>@endif
        <p class="mt-2 text-sm text-muted">{{ $book->extension() }} · {{ $book->humanSize() }}{{ $book->category ? ' · '.$book->category : '' }}</p>
        @if ($book->description)<p class="mt-3 whitespace-pre-line leading-relaxed">{{ $book->description }}</p>@endif

        <div class="mt-5 grid gap-2">
            @if (str_starts_with((string) $book->mime, 'audio/'))
                <audio controls preload="none" class="w-full" src="{{ route('library.file', $book) }}"></audio>
            @else
                <a href="{{ route('library.file', $book) }}" target="_blank" class="btn btn-primary text-lg"><x-icon name="book" /> افتحي الكتاب</a>
            @endif
            <a href="{{ route('library.file', ['book' => $book, 'download' => 1]) }}" class="btn btn-soft"><x-icon name="download" :size="20" /> حمّلي نسخة على الجهاز</a>
        </div>
    </section>

    @if ($book->isPdf())
        <div class="hidden overflow-hidden rounded-2xl border border-line md:block">
            <iframe src="{{ route('library.file', $book) }}" title="{{ $book->title }}" class="h-[75vh] w-full bg-white"></iframe>
        </div>
        <p class="text-sm text-muted md:hidden">يفتح الكتاب في عارض الجوال؛ ارجعي للتطبيق من زر الرجوع.</p>
    @endif
</div>
@endsection

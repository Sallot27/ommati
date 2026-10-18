@extends('layouts.app')
@section('title', 'من كلام الأئمة')
@section('back', route('more'))

@section('content')
@php $D = fn ($x) => \App\Services\Quran::digits($x); @endphp
@if (! $who && $page === 1)
    <x-quote :q="$today" :imams="$imams" :surah-name="$surahName" label="قول اليوم" />
@endif

<div class="-mx-4 mt-5 flex gap-2 overflow-x-auto px-4 pb-1">
    <a href="{{ route('quotes') }}" @class(['chip shrink-0 px-4 py-1.5', 'bg-lapis-ink text-white dark:text-[#0f1823]' => ! $who, 'bg-surface-2' => $who])>الكل</a>
    @foreach ($imams as $key => $imam)
        @if (($counts[$key] ?? 0) > 0)
            <a href="{{ route('quotes', ['who' => $key]) }}" @class(['chip shrink-0 px-4 py-1.5', 'bg-lapis-ink text-white dark:text-[#0f1823]' => $who === $key, 'bg-surface-2' => $who !== $key])>{{ $key }}</a>
        @endif
    @endforeach
</div>

@if ($who)
    <p class="mt-3 text-muted">{{ $imams[$who]['name'] }} رحمه الله — توفي سنة {{ $D($imams[$who]['died']) }} هـ</p>
@endif

<ul class="mt-4 space-y-3">
    @foreach ($quotes as $q)
        <li><x-quote :q="$q" :imams="$imams" :surah-name="$surahName" /></li>
    @endforeach
</ul>

<nav class="mt-5 flex gap-3">
    @if ($page > 1)<a href="{{ route('quotes', array_filter(['who' => $who, 'page' => $page - 1])) }}" class="btn btn-soft flex-1"><x-icon name="back" :size="18" /> السابق</a>@endif
    @if ($more)<a href="{{ route('quotes', array_filter(['who' => $who, 'page' => $page + 1])) }}" class="btn btn-soft flex-1">المزيد <x-icon name="forward" :size="18" /></a>@endif
</nav>
<p class="mt-5 text-sm leading-relaxed text-muted">المصدر: {{ $source }}. الرقم بعد اسم الإمام هو الجزء والصفحة في كتابه.</p>
@endsection

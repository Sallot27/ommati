@extends('layouts.app')
@section('title', 'المصحف')
@section('header-actions')
    <a href="{{ route('listen') }}" class="btn btn-soft min-h-10 px-3 text-sm"><x-icon name="headphones" :size="20" /> استماع</a>
@endsection

@section('content')
<div x-data="{ q: '' }">
    <a href="{{ route('mushaf') }}" class="mb-3 flex items-center gap-4 rounded-2xl border border-saffron/40 bg-saffron-soft px-5 py-4">
        <x-icon name="book" />
        <span class="flex-1">
            <span class="block text-lg font-semibold">المصحف بصفحاته</span>
            <span class="text-sm text-muted">مصحف المدينة صفحة صفحة مع التلاوة والتفسير{{ $mushafPage ? ' — أكملي من صفحة '.\App\Services\Quran::digits($mushafPage) : '' }}</span>
        </span>
        <x-icon name="forward" :size="20" />
    </a>
    @if ($lastRead)
        <a href="{{ route('quran.show', $lastRead['s']) }}?a={{ $lastRead['a'] }}" class="mb-4 flex items-center gap-3 rounded-2xl bg-lapis-ink px-5 py-4 text-white dark:text-[#0f1823]">
            <x-icon name="book" />
            <span class="flex-1">أكملي من سورة {{ $lastRead['name'] }} — آية {{ \App\Services\Quran::digits($lastRead['a']) }}</span>
            <x-icon name="forward" :size="20" />
        </a>
    @endif

    <label class="relative mb-4 block">
        <span class="sr-only">ابحثي عن سورة</span>
        <x-icon name="search" :size="20" class="pointer-events-none absolute start-4 top-1/2 -translate-y-1/2 text-muted" />
        <input x-model="q" type="search" placeholder="ابحثي عن سورة أو رقمها" class="w-full rounded-2xl border border-line bg-surface py-3.5 ps-12 pe-4 text-lg outline-none focus:border-lapis">
    </label>

    <ul class="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
        @foreach ($surahs as $s)
            <li x-show="!q || @js($s['name']).includes(q.replace('سورة','').trim()) || '{{ $s['n'] }}' === q.trim()">
                <a href="{{ route('quran.show', $s['n']) }}" class="flex items-center gap-4 px-4 py-3.5 hover:bg-surface-2">
                    <span class="star-badge"><span>{{ \App\Services\Quran::digits($s['n']) }}</span></span>
                    <span class="flex-1">
                        <span class="block text-lg font-medium">سورة {{ $s['name'] }}</span>
                        <span class="text-sm text-muted">{{ $s['type'] }} · {{ \App\Services\Quran::digits($s['count']) }} آية</span>
                    </span>
                    <span class="text-sm text-muted">الجزء {{ \App\Services\Quran::digits($s['juz']) }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
@endsection

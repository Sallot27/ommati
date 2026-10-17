@extends('layouts.app')
@section('title', 'الأذكار')
@section('header-actions')
    <a href="{{ route('tasbih') }}" class="btn btn-soft min-h-10 px-3 text-sm"><x-icon name="beads" :size="20" /> السبحة</a>
@endsection

@section('content')
@php
    $icons = ['morning' => 'sun', 'evening' => 'moon', 'sleep' => 'moon', 'waking' => 'sun', 'after-prayer' => 'star'];
@endphp
<ul class="grid grid-cols-2 gap-3">
    @foreach ($featured as $i => $c)
        <li @class(['col-span-2' => $i < 2])>
            <a href="{{ route('adhkar.show', $c['slug']) }}" @class([
                'flex h-full items-center gap-3 rounded-2xl px-4 py-4',
                'bg-teal text-white dark:text-[#0f1823]' => $i < 2,
                'card hover:bg-surface-2' => $i >= 2,
            ])>
                <x-icon :name="$icons[$c['slug']] ?? 'beads'" :size="$i < 2 ? 30 : 22" @class(['text-teal' => $i >= 2]) />
                <span class="flex-1">
                    <span @class(['block font-semibold', 'text-xl' => $i < 2])>{{ $c['title'] }}</span>
                    <span @class(['text-sm', 'opacity-85' => $i < 2, 'text-muted' => $i >= 2])>{{ \App\Services\Quran::digits(count($c['items'])) }} ذكر</span>
                </span>
            </a>
        </li>
    @endforeach
</ul>

<section class="mt-7" x-data="{ q: '' }">
    <h2 class="font-semibold">حصن المسلم كاملاً</h2>
    <p class="text-sm text-muted">أدعية وأذكار لكل المناسبات</p>
    <input x-model="q" type="search" placeholder="ابحثي: السفر، المطر، الكرب…" class="my-3 w-full rounded-2xl border border-line bg-surface px-4 py-3 text-lg outline-none focus:border-lapis">
    <ul class="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
        @foreach ($others as $c)
            <li x-show="!q || @js($c['title']).includes(q.trim())">
                <a href="{{ route('adhkar.show', $c['slug']) }}" class="flex items-center gap-3 px-4 py-3.5 hover:bg-surface-2">
                    <span class="flex-1">{{ $c['title'] }}</span>
                    <x-icon name="forward" :size="18" class="text-muted" />
                </a>
            </li>
        @endforeach
    </ul>
    <p class="mt-3 text-sm text-muted">المصدر: كتاب حصن المسلم — سعيد بن علي بن وهف القحطاني.</p>
</section>
@endsection

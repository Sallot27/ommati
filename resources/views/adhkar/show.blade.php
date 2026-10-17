@extends('layouts.app')
@section('title', $category['title'])
@section('back', route('adhkar.index'))

@section('content')
@php $D = fn ($n) => \App\Services\Quran::digits($n); @endphp
<div x-data="adhkar(@js($category['slug']), @js(array_map(fn ($i) => ['count' => $i['count']], $category['items'])))">
    <div class="sticky top-16 z-20 -mx-4 mb-4 bg-bg/95 px-4 py-2 backdrop-blur">
        <div class="flex items-center justify-between text-sm text-muted">
            <span>أنهيتِ <span x-text="ar(doneCount)"></span> من {{ $D(count($category['items'])) }}</span>
            <button @click="reset()" class="flex items-center gap-1 hover:text-ink"><x-icon name="refresh" :size="16" /> من البداية</button>
        </div>
        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-surface-2">
            <div class="h-full rounded-full bg-teal transition-all" :style="`width: ${progress * 100}%`"></div>
        </div>
    </div>

    <ol class="space-y-4">
        @foreach ($category['items'] as $i => $z)
            <li id="zikr-{{ $i }}" class="scroll-mt-32">
                <button type="button" @click="tap({{ $i }})"
                        class="card block w-full px-5 pb-4 pt-5 text-start transition-opacity"
                        :class="items[{{ $i }}].left === 0 ? 'opacity-55' : ''"
                        :aria-label="items[{{ $i }}].left === 0 ? 'تم' : 'المتبقي ' + items[{{ $i }}].left">
                    <p class="naskh whitespace-pre-line text-[1.22rem]">{{ $z['text'] }}</p>
                    @if ($z['fadl'])
                        <p class="mt-3 rounded-xl bg-saffron-soft px-3 py-2 text-[0.95rem] leading-relaxed">{{ $z['fadl'] }}</p>
                    @endif
                    <span class="mt-4 flex items-center justify-between gap-3">
                        <span class="text-sm text-muted">{{ $z['ref'] }}</span>
                        <span class="flex items-center gap-2">
                            <span class="text-sm text-muted" x-show="items[{{ $i }}].left > 0">{{ $z['count'] > 1 ? 'التكرار '.$D($z['count']) : '' }}</span>
                            <span class="grid h-12 min-w-12 place-items-center rounded-full px-3 text-lg font-semibold"
                                  :class="items[{{ $i }}].left === 0 ? 'bg-teal text-white dark:text-[#0f1823]' : 'bg-teal-soft text-teal'">
                                <span x-show="items[{{ $i }}].left > 0" x-text="ar(items[{{ $i }}].left)"></span>
                                <x-icon name="check" x-show="items[{{ $i }}].left === 0" x-cloak />
                            </span>
                        </span>
                    </span>
                </button>
            </li>
        @endforeach
    </ol>

    <div x-show="finished" x-cloak x-transition class="mt-6 rounded-2xl bg-teal px-5 py-6 text-center text-white dark:text-[#0f1823]">
        <p class="text-2xl font-semibold">تقبّل الله منكِ</p>
        <p class="mt-1 opacity-90">أتممتِ {{ $category['title'] }}</p>
        <a href="{{ route('home') }}" class="btn mt-4 bg-white/20">العودة للرئيسية</a>
    </div>
</div>
@endsection

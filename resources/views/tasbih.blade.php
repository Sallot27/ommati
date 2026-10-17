@extends('layouts.app')
@section('title', 'السبحة')
@section('back', route('adhkar.index'))

@section('content')
<div x-data="tasbih" class="select-none">
    <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-2">
        <template x-for="(p, i) in presets" :key="i">
            <button @click="choose(i)" class="chip naskh shrink-0 px-4 py-1.5" :class="index === i ? 'bg-teal text-white dark:text-[#0f1823]' : 'bg-surface-2'" x-text="p.text"></button>
        </template>
    </div>

    <button @click="tap()" class="relative mx-auto mt-6 block size-72 rounded-full focus-visible:outline-offset-4" aria-label="تسبيحة">
        <svg viewBox="0 0 260 260" class="absolute inset-0 size-full -rotate-90" aria-hidden="true">
            <circle cx="130" cy="130" r="120" fill="var(--surface)" class="ring-track" stroke-width="14"/>
            <circle cx="130" cy="130" r="120" fill="none" class="ring-fill" stroke-width="14" stroke-linecap="round"
                    :stroke-dasharray="2 * Math.PI * 120" :stroke-dashoffset="dash"/>
        </svg>
        <span class="relative flex flex-col items-center">
            <span class="naskh text-2xl" x-text="current.text"></span>
            <span class="mt-1 text-6xl font-semibold tabular-nums" x-text="ar(count)"></span>
            <span class="text-muted">من <span x-text="ar(current.target)"></span></span>
        </span>
    </button>

    <p class="mt-5 text-center text-lg" x-show="count >= current.target" x-cloak>اكتمل العدد، بارك الله فيكِ ✨</p>

    <div class="mt-6 flex items-center justify-between rounded-2xl bg-surface-2 px-5 py-3">
        <span class="text-muted">اليوم: <strong class="text-ink" x-text="ar(today.n)"></strong> · المجموع: <strong class="text-ink" x-text="ar(total)"></strong></span>
        <button @click="reset()" class="flex items-center gap-1 text-muted hover:text-ink"><x-icon name="refresh" :size="18" /> تصفير</button>
    </div>
    <p class="mt-3 text-center text-sm text-muted">اضغطي على الدائرة في أي مكان للتسبيح</p>
</div>
@endsection

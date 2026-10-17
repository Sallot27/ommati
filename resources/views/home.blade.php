@extends('layouts.app')

@php
    $D = fn ($n) => \App\Services\Quran::digits($n);
    $doneCount = collect($tasks)->where('done', true)->count();
@endphp

@section('header')
<header class="khatam-band text-white" style="padding-top: env(safe-area-inset-top)">
    <div class="mx-auto max-w-2xl px-5 pb-16 pt-6"
         x-data="prayerTimes({ lat: {{ (float) $user->pref('location_lat') }}, lng: {{ (float) $user->pref('location_lng') }}, place: @js($user->pref('location_name')) })">
        <div class="flex items-start justify-between gap-3">
            <div class="rise">
                <p class="text-white/75" x-data="hijri" x-text="text"></p>
                <h1 class="mt-1 text-[1.8rem] font-semibold leading-snug">{{ $greeting }} يا {{ $user->name }}</h1>
                @if (session('status'))<p class="mt-1 text-white/85">{{ session('status') }}</p>@endif
            </div>
            <a href="{{ route('settings') }}" class="grid size-11 shrink-0 place-items-center rounded-full bg-white/10 hover:bg-white/20" aria-label="الإعدادات">
                <x-icon name="sliders" />
            </a>
        </div>

        <a href="{{ route('prayer') }}" class="rise mt-5 flex items-center gap-4 rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/15 hover:bg-white/15" style="animation-delay: .1s">
            <x-icon name="clock" :size="28" class="text-white/80" />
            <div class="min-w-0 flex-1" x-show="next" x-cloak>
                <p class="text-sm text-white/75">الصلاة القادمة — <span x-text="place"></span></p>
                <p class="text-xl font-semibold"><span x-text="next?.name"></span> <span class="font-normal" x-text="next ? fmt(next.time) : ''"></span></p>
            </div>
            <p class="flex-1 text-white/75" x-show="!next && loading">جاري تحميل المواقيت…</p>
            <p class="flex-1 text-white/75" x-show="!next && !loading" x-cloak>اضغطي لتحديد مدينتك</p>
            <span class="text-sm text-white/85" x-text="countdown" x-show="next" x-cloak></span>
        </a>
    </div>
</header>
@endsection

@section('content')
<div class="-mt-14 space-y-5">

    {{-- Today's journey --}}
    <section class="card overflow-hidden shadow-lg shadow-black/5">
        <div class="flex items-center justify-between px-5 pb-2 pt-4">
            <h2 class="section-title">وِرد اليوم</h2>
            <span class="text-sm text-muted">{{ $D($doneCount) }} من {{ $D(count($tasks)) }}</span>
        </div>
        <div class="mx-5 h-1.5 overflow-hidden rounded-full bg-surface-2">
            <div class="h-full rounded-full bg-teal transition-all" style="width: {{ count($tasks) ? $doneCount / count($tasks) * 100 : 0 }}%"></div>
        </div>
        <ul class="mt-2">
            @foreach ($tasks as $t)
                <li>
                    <a href="{{ $t['href'] }}" @class(['flex items-center gap-4 px-5 py-3.5 hover:bg-surface-2', 'task-done' => $t['done']])>
                        <span class="task-check"><x-icon name="check" :size="18" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="task-title block text-lg font-medium">{{ $t['title'] }}</span>
                            <span class="block truncate text-sm text-muted">{{ $t['sub'] }}</span>
                        </span>
                        <x-icon :name="$t['icon']" :size="22" class="text-teal" />
                    </a>
                </li>
            @endforeach
        </ul>
        @if ($doneCount === count($tasks))
            <p class="bg-teal-soft px-5 py-3 text-center text-teal">ما شاء الله، أتممتِ ورد اليوم كاملاً 🌿</p>
        @endif
    </section>

    @if ($message)
        <section class="rounded-2xl border-s-4 border-saffron bg-saffron-soft px-5 py-4">
            <p class="text-sm text-muted">رسالة {{ $message->from_name ? 'من '.$message->from_name : 'لكِ' }} 💌</p>
            <p class="mt-1 whitespace-pre-line text-lg leading-relaxed">{{ $message->body }}</p>
        </section>
    @endif

    {{-- Ayah of the day --}}
    <a href="{{ route('ayah.today') }}" class="card block px-5 pb-4 pt-5 hover:bg-surface-2">
        <p class="text-center text-sm text-muted">آية اليوم — سورة {{ $ayah['surah'] }}</p>
        <p class="quran mt-1 text-center">{{ $ayah['text'] }}<span class="ayah-no">{{ $D($ayah['from']) }}</span>@if ($ayah['to'] > $ayah['from']) … @endif</p>
        <p class="mt-1 flex items-center justify-center gap-1 text-lapis-ink">التفسير وكيف أعمل بها <x-icon name="forward" :size="18" /></p>
    </a>

    {{-- Progress --}}
    <section class="grid grid-cols-2 gap-3">
        <a href="{{ route('hifz.index') }}" class="card px-4 py-4 hover:bg-surface-2">
            <p class="text-sm text-muted">آيات حفظتِها</p>
            <p class="mt-0.5 text-3xl font-semibold">{{ $memorized ? $D($memorized) : '—' }}</p>
        </a>
        <a href="{{ route('hifz.index') }}" class="card px-4 py-4 hover:bg-surface-2">
            <p class="text-sm text-muted">أيام متتالية</p>
            <p class="mt-0.5 text-3xl font-semibold">{{ $streak ? $D($streak) : '—' }}</p>
        </a>
    </section>

    @if ($lastRead)
        <a href="{{ route('quran.show', $lastRead['s']) }}?a={{ $lastRead['a'] }}" class="flex items-center gap-4 rounded-2xl bg-lapis-ink px-5 py-4 text-white dark:text-[#0f1823]">
            <x-icon name="book" :size="26" />
            <div class="flex-1">
                <p class="font-semibold">أكملي القراءة</p>
                <p class="opacity-80">سورة {{ $lastRead['name'] }} — الآية {{ $D($lastRead['a']) }}</p>
            </div>
            <x-icon name="forward" :size="20" />
        </a>
    @endif

    <nav aria-label="أقسام أخرى">
        <h2 class="section-title mb-2">المزيد لكِ</h2>
        <div class="grid grid-cols-4 gap-2 text-center text-[0.9rem]">
            @foreach ([[route('tasbih'), 'beads', 'السبحة'], [route('listen'), 'headphones', 'التلاوات'], [route('prayer'), 'compass', 'القبلة'], [route('library.index'), 'library', 'المكتبة']] as [$href, $icon, $label])
                <a href="{{ $href }}" class="flex flex-col items-center gap-1.5 rounded-2xl py-3 hover:bg-surface-2">
                    <span class="grid size-14 place-items-center rounded-2xl bg-teal-soft text-teal"><x-icon :name="$icon" :size="26" /></span>
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </nav>

    @if ($latestBook)
        <a href="{{ route('library.show', $latestBook) }}" class="card flex items-center gap-4 px-4 py-3.5 hover:bg-surface-2">
            <span class="grid h-14 w-11 shrink-0 place-items-center rounded-lg bg-lapis-ink text-[0.6rem] font-semibold text-white/90 dark:bg-surface-2">{{ $latestBook->extension() }}</span>
            <span class="min-w-0 flex-1">
                <span class="block text-sm text-muted">أحدث كتاب في المكتبة</span>
                <span class="block truncate font-medium">{{ $latestBook->title }}</span>
            </span>
        </a>
    @endif
</div>
@endsection

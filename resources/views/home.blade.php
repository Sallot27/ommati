@extends('layouts.app')

@section('header')
<header class="khatam-band text-white" style="padding-top: env(safe-area-inset-top)">
    <div class="mx-auto max-w-2xl px-5 pb-7 pt-6"
         x-data="prayerTimes({ lat: {{ (float) \App\Models\Setting::get('location_lat') }}, lng: {{ (float) \App\Models\Setting::get('location_lng') }}, place: @js(\App\Models\Setting::get('location_name')) })">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-white/75" x-data="hijri" x-text="text"></p>
                <h1 class="mt-1 text-[1.75rem] font-semibold leading-snug">{{ $greeting }} يا {{ $auntName }}</h1>
            </div>
            <a href="{{ route('settings') }}" class="grid size-11 shrink-0 place-items-center rounded-full bg-white/10 hover:bg-white/20" aria-label="الإعدادات">
                <x-icon name="sliders" />
            </a>
        </div>

        <a href="{{ route('prayer') }}" class="mt-5 flex items-center gap-4 rounded-2xl bg-white/10 px-4 py-3 hover:bg-white/15">
            <x-icon name="clock" :size="28" class="text-white/80" />
            <div class="min-w-0 flex-1" x-show="next" x-cloak>
                <p class="text-sm text-white/75">الصلاة القادمة <span x-text="place"></span></p>
                <p class="text-xl font-semibold"><span x-text="next?.name"></span> <span class="font-normal" x-text="next ? fmt(next.time) : ''"></span></p>
            </div>
            <p class="text-sm text-white/75" x-show="!next && loading">جاري تحميل المواقيت…</p>
            <p class="text-sm text-white/75" x-show="!next && !loading" x-cloak>اضغطي لتحديد مدينتك</p>
            <span class="text-sm text-white/85" x-text="countdown" x-show="next" x-cloak></span>
        </a>
    </div>
</header>
@endsection

@section('content')
<div class="space-y-5">

    @if ($message)
        <section class="rounded-2xl border-s-4 border-saffron bg-saffron-soft px-5 py-4">
            <p class="text-sm text-muted">رسالة {{ $message->from_name ? 'من '.$message->from_name : 'لكِ' }} 💌</p>
            <p class="mt-1 whitespace-pre-line text-lg leading-relaxed">{{ $message->body }}</p>
        </section>
    @endif

    {{-- Adhkar of the moment --}}
    <a href="{{ route('adhkar.show', $adhkarSlug) }}" class="flex items-center gap-4 rounded-2xl bg-teal px-5 py-4 text-white dark:text-[#0f1823]">
        <x-icon :name="$adhkarSlug === 'morning' ? 'sun' : 'moon'" :size="34" />
        <div class="flex-1">
            <p class="text-xl font-semibold">{{ $adhkarSlug === 'morning' ? 'أذكار الصباح' : 'أذكار المساء' }}</p>
            <p class="opacity-85">اضغطي على كل ذكر لتعدّيه</p>
        </div>
        <x-icon name="forward" />
    </a>

    {{-- Ayah of the day --}}
    <section class="card overflow-hidden">
        <div class="flex items-center justify-between px-5 pt-4">
            <h2 class="font-semibold text-lapis-ink">آية اليوم</h2>
            <span class="text-sm text-muted">سورة {{ $ayah['surah'] }}، {{ $ayah['to'] > $ayah['from'] ? 'الآيات '.\App\Services\Quran::digits($ayah['from']).' إلى '.\App\Services\Quran::digits($ayah['to']) : 'الآية '.\App\Services\Quran::digits($ayah['from']) }}</span>
        </div>
        <p class="quran px-5 pb-2 pt-1 text-center">{{ $ayah['text'] }}@if ($ayah['to'] > $ayah['from']) … @endif</p>
        <a href="{{ route('ayah.today') }}" class="flex items-center justify-between border-t border-line px-5 py-3.5 text-lapis-ink hover:bg-surface-2">
            <span class="font-medium">التفسير وكيف أعمل بها</span>
            <x-icon name="forward" :size="20" />
        </a>
    </section>

    {{-- Hadith of the day --}}
    <a href="{{ route('hadith.today') }}" class="card flex items-start gap-4 px-5 py-4 hover:bg-surface-2">
        <span class="mt-1 grid size-11 shrink-0 place-items-center rounded-full bg-saffron-soft text-saffron"><x-icon name="quote" /></span>
        <div class="min-w-0 flex-1">
            <h2 class="font-semibold text-lapis-ink">حديث اليوم</h2>
            <p class="mt-0.5 text-lg leading-relaxed">{{ $hadith?->title ?? 'حديث نبوي مع شرحه وفوائده العملية' }}</p>
        </div>
    </a>

    {{-- Memorization --}}
    <section class="card px-5 py-4">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-lapis-ink">حفظ القرآن</h2>
            @if ($streak > 1)
                <span class="chip bg-teal-soft px-3 py-0.5 text-sm text-teal">{{ \App\Services\Quran::digits($streak) }} أيام متتالية</span>
            @endif
        </div>
        <dl class="mt-3 grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-surface-2 px-4 py-3">
                <dt class="text-sm text-muted">آيات محفوظة</dt>
                <dd class="text-2xl font-semibold">{{ \App\Services\Quran::digits($memorized) }}</dd>
            </div>
            <div class="rounded-xl bg-surface-2 px-4 py-3">
                <dt class="text-sm text-muted">للمراجعة اليوم</dt>
                <dd class="text-2xl font-semibold">{{ \App\Services\Quran::digits($dueReview) }}</dd>
            </div>
        </dl>
        <div class="mt-3 flex gap-2">
            <a href="{{ route('hifz.index') }}" class="btn btn-primary flex-1">ورد الحفظ</a>
            @if ($dueReview > 0)
                <a href="{{ route('hifz.review') }}" class="btn btn-soft flex-1">المراجعة</a>
            @endif
        </div>
    </section>

    @if ($lastRead)
        <a href="{{ route('quran.show', $lastRead['s']) }}?a={{ $lastRead['a'] }}" class="card flex items-center gap-4 px-5 py-4 hover:bg-surface-2">
            <span class="grid size-11 place-items-center rounded-full bg-surface-2 text-lapis-ink"><x-icon name="book" /></span>
            <div class="flex-1">
                <p class="font-semibold">أكملي القراءة</p>
                <p class="text-muted">سورة {{ $lastRead['name'] }} — الآية {{ \App\Services\Quran::digits($lastRead['a']) }}</p>
            </div>
            <x-icon name="forward" :size="20" class="text-muted" />
        </a>
    @endif

    <nav class="grid grid-cols-2 gap-3" aria-label="أقسام أخرى">
        <a href="{{ route('tasbih') }}" class="card flex items-center gap-3 px-4 py-4 hover:bg-surface-2"><x-icon name="beads" class="text-teal" /> السبحة</a>
        <a href="{{ route('listen') }}" class="card flex items-center gap-3 px-4 py-4 hover:bg-surface-2"><x-icon name="headphones" class="text-teal" /> التلاوات</a>
        <a href="{{ route('prayer') }}" class="card flex items-center gap-3 px-4 py-4 hover:bg-surface-2"><x-icon name="compass" class="text-teal" /> القبلة والمواقيت</a>
        <a href="{{ route('library.index') }}" class="card flex items-center gap-3 px-4 py-4 hover:bg-surface-2"><x-icon name="library" class="text-teal" /> المكتبة</a>
    </nav>

    @if ($latestBook)
        <p class="text-center text-sm text-muted">أحدث كتاب في المكتبة: <a class="text-lapis-ink underline" href="{{ route('library.show', $latestBook) }}">{{ $latestBook->title }}</a></p>
    @endif
</div>
@endsection

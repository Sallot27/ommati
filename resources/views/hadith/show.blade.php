@extends('layouts.app')
@section('title', $isToday ? 'حديث اليوم' : 'حديث')
@section('back', $isToday ? route('home') : route('hadith.saved'))
@section('header-actions')
    <a href="{{ route('hadith.saved') }}" class="btn btn-soft min-h-10 px-3 text-sm">المحفوظة</a>
@endsection

@section('content')
@if (! $hadith)
    <div class="card px-5 py-8 text-center">
        <p class="text-lg">تعذّر تحميل حديث اليوم الآن.</p>
        <p class="mt-2 text-muted">تأكدي من الاتصال بالإنترنت ثم أعيدي المحاولة.</p>
        <a href="{{ url()->current() }}" class="btn btn-primary mt-5"><x-icon name="refresh" :size="20" /> إعادة المحاولة</a>
    </div>
@else
<article class="space-y-5">
    <header>
        <h2 class="text-2xl font-semibold leading-snug">{{ $hadith->title }}</h2>
        <div class="mt-2 flex flex-wrap gap-2 text-sm">
            @if ($hadith->grade)<span class="chip bg-teal-soft px-3 py-0.5 text-teal">{{ $hadith->grade }}</span>@endif
            @if ($hadith->attribution)<span class="chip bg-surface-2 px-3 py-0.5">{{ $hadith->attribution }}</span>@endif
        </div>
    </header>

    <section class="card px-5 py-5">
        <p class="naskh text-[1.25rem]">{{ $hadith->text }}</p>
        <div class="mt-4 flex justify-end" x-data="hadithSave({{ $hadith->id }}, {{ $saved ? 'true' : 'false' }})">
            <button @click="toggle()" class="btn btn-soft min-h-10 text-sm" :aria-pressed="saved">
                <x-icon name="heart-fill" :size="20" class="text-rose" x-show="saved" x-cloak />
                <x-icon name="heart" :size="20" x-show="!saved" />
                <span x-text="saved ? 'محفوظ' : 'احفظيه'"></span>
            </button>
        </div>
    </section>

    @if ($hadith->hints)
        <section class="rounded-2xl bg-teal-soft px-5 py-5">
            <h3 class="text-xl font-semibold text-teal">كيف أعمل به؟ — من فوائد الحديث</h3>
            <ul class="mt-3 space-y-3">
                @foreach ($hadith->hints as $hint)
                    <li class="flex gap-3">
                        <x-icon name="check" class="mt-1.5 text-teal" :size="22" />
                        <span class="naskh text-[1.1rem]">{{ $hint }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($hadith->explanation)
        <section class="card px-5 py-5">
            <h3 class="text-xl font-semibold text-lapis-ink">الشرح</h3>
            <div class="naskh prose-ar mt-2 whitespace-pre-line text-[1.1rem]">{{ $hadith->explanation }}</div>
        </section>
    @endif

    @if ($hadith->words_meanings)
        <details class="card group px-5 py-4">
            <summary class="flex cursor-pointer list-none items-center justify-between text-lg font-semibold text-lapis-ink">
                معاني الكلمات <x-icon name="plus" class="transition group-open:rotate-45" />
            </summary>
            <dl class="mt-3 space-y-2">
                @foreach ($hadith->words_meanings as $w)
                    <div><dt class="inline font-semibold">{{ $w['word'] ?? '' }}:</dt> <dd class="naskh inline">{{ $w['meaning'] ?? '' }}</dd></div>
                @endforeach
            </dl>
        </details>
    @endif

    @if ($hadith->reference)
        <p class="whitespace-pre-line text-sm text-muted">المصادر: {{ $hadith->reference }}</p>
    @endif
    <p class="text-sm text-muted">الحديث وشرحه من <a href="https://hadeethenc.com/ar/browse/hadith/{{ $hadith->id }}" class="underline" target="_blank" rel="noopener">موسوعة الأحاديث النبوية</a>.</p>
</article>
@endif
@endsection

@extends('layouts.app')
@section('title', 'الأيام الفاضلة')
@section('back', route('more'))

@section('content')
@php $D = fn ($x) => \App\Services\Quran::digits($x); @endphp
<div class="space-y-5">
    <section class="khatam-band rounded-2xl px-5 py-5 text-white">
        <p class="text-white/75">اليوم</p>
        <p class="text-2xl font-semibold">{{ now()->locale('ar')->translatedFormat('l') }}، {{ $hijri }}</p>
    </section>

    @forelse ($today as $o)
        <article class="card overflow-hidden">
            <div class="flex items-center gap-3 px-5 pt-4">
                <span class="grid size-11 place-items-center rounded-full bg-teal-soft text-teal"><x-icon :name="$o['icon']" /></span>
                <div class="flex-1">
                    <h2 class="text-xl font-semibold">{{ $o['title'] }}</h2>
                    <p class="text-muted">{{ $o['sub'] }}</p>
                </div>
            </div>
            @if ($o['h'])
                <div class="px-5 pb-2 pt-3">
                    <p class="naskh text-[1.1rem]">{{ $o['h']->text }}</p>
                    <p class="mt-1 text-sm text-muted">{{ $o['h']->attribution }}{{ $o['h']->grade ? ' — '.$o['h']->grade : '' }}</p>
                </div>
                <a href="{{ route('hadith.show', $o['h']->id) }}" class="flex items-center justify-between border-t border-line px-5 py-3 text-lapis-ink hover:bg-surface-2">الشرح والفوائد <x-icon name="forward" :size="18" /></a>
            @endif
            @if (! empty($o['link']))
                <a href="{{ $o['link'] }}" class="flex items-center justify-between border-t border-line px-5 py-3 text-lapis-ink hover:bg-surface-2">اقرئي سورة الكهف <x-icon name="forward" :size="18" /></a>
            @endif
        </article>
    @empty
        <p class="card px-5 py-4 text-muted">لا توجد مناسبة خاصة اليوم — وكل يوم فرصة للطاعة.</p>
    @endforelse

    @if ($upcoming)
        <section>
            <h2 class="section-title mb-2">قادم بإذن الله</h2>
            <ul class="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
                @foreach ($upcoming as $u)
                    <li class="flex items-center gap-3 px-4 py-3">
                        <x-icon :name="$u['icon']" :size="22" class="text-teal" />
                        <span class="flex-1">
                            <span class="block font-medium">{{ $u['title'] }}</span>
                            <span class="text-sm text-muted">{{ $u['hijri'] }}</span>
                        </span>
                        <span class="text-sm text-muted">{{ match (true) { $u['in'] === 1 => 'غداً', $u['in'] === 2 => 'بعد يومين', $u['in'] <= 10 => 'بعد '.$D($u['in']).' أيام', default => 'بعد '.$D($u['in']).' يوماً' } }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section>
        <h2 class="section-title mb-2">سنن تتكرر</h2>
        <ul class="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
            @foreach ($weekly as $w)
                <li><a href="{{ route('hadith.show', $w['hadith']) }}" class="flex items-center justify-between px-4 py-3.5 hover:bg-surface-2">{{ $w['title'] }} <x-icon name="forward" :size="18" class="text-muted" /></a></li>
            @endforeach
        </ul>
    </section>
    <p class="text-sm text-muted">التواريخ حسب تقويم أم القرى. الأحاديث وشروحها من موسوعة الأحاديث النبوية.</p>
</div>
@endsection

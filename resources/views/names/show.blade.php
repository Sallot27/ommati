@extends('layouts.app')
@section('title', $n['name'])
@section('back', route('names'))

@section('content')
@php $D = fn ($x) => \App\Services\Quran::digits($x); @endphp
<div class="space-y-5">
    <section class="khatam-band rounded-2xl px-5 py-8 text-center text-white">
        <p class="quran text-[2.8rem] leading-tight text-white">{{ $n['name'] }}</p>
        <p class="mt-1 text-white/75">{{ $D($i + 1) }} من {{ $D($count) }}</p>
    </section>

    @if (! $fromQuran)
        <section class="card px-5 py-5">
            <p class="text-sm font-semibold text-teal">ورد في السنة النبوية</p>
            @if (! empty($n['hadith']))
                @if ($hadith)
                    <p class="naskh mt-2 text-[1.15rem]">{{ $hadith->text }}</p>
                    <p class="mt-1 text-sm text-muted">{{ $hadith->attribution }}{{ $hadith->grade ? ' — '.$hadith->grade : '' }}</p>
                    @if ($hadith->hints)
                        <ul class="mt-3 space-y-2">
                            @foreach (array_slice($hadith->hints, 0, 3) as $hint)
                                <li class="flex gap-2"><x-icon name="check" :size="18" class="mt-1.5 text-teal" /><span class="naskh">{{ $hint }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                    <a href="{{ route('hadith.show', $hadith->id) }}" class="mt-3 inline-flex items-center gap-1 text-lapis-ink">الشرح كاملاً <x-icon name="forward" :size="16" /></a>
                @else
                    <p class="mt-2 text-muted">تعذّر تحميل الحديث الآن، تأكدي من الاتصال بالإنترنت.</p>
                @endif
            @else
                <p class="naskh mt-2 text-[1.2rem]">«{{ $n['evidence'] }}»</p>
                <p class="mt-1 text-sm text-muted">{{ $n['source'] }}</p>
                <a href="https://dorar.net/aqeeda/{{ $n['dorar'] }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1 text-lapis-ink">الشرح في الموسوعة العقدية — الدرر السنية <x-icon name="forward" :size="16" /></a>
            @endif
        </section>
    @else
    <section class="card px-5 py-5">
        <p class="text-center text-sm text-muted">ورد في سورة {{ $surah }} — الآية {{ $D($n['ayah']) }}</p>
        <p class="quran mt-2 text-center">{{ $ayah }}<span class="ayah-no">{{ $D($n['ayah']) }}</span></p>
    </section>

    <section class="card px-5 py-5">
        <h2 class="section-title text-lapis-ink">تفسير الآية</h2>
        @if ($muyassar)
            <p class="naskh mt-2 whitespace-pre-line text-[1.1rem]">{{ $muyassar }}</p>
            <p class="mt-2 text-sm text-muted">التفسير الميسّر — مجمع الملك فهد</p>
        @else
            <p class="mt-2 text-muted">تعذّر تحميل التفسير الآن، تأكدي من الاتصال بالإنترنت.</p>
        @endif
    </section>

    @if ($saadi)
        <details class="card group px-5 py-4">
            <summary class="flex cursor-pointer list-none items-center justify-between text-lg font-semibold text-lapis-ink">تفسير السعدي <x-icon name="plus" class="transition group-open:rotate-45" /></summary>
            <p class="naskh mt-3 whitespace-pre-line text-[1.08rem]">{{ $saadi }}</p>
        </details>
    @endif
    @endif

    <nav class="flex gap-3">
        @if ($prev !== null)<a href="{{ route('names.show', $prev) }}" class="btn btn-soft flex-1"><x-icon name="back" :size="18" /> السابق</a>@endif
        @if ($next !== null)<a href="{{ route('names.show', $next) }}" class="btn btn-soft flex-1">التالي <x-icon name="forward" :size="18" /></a>@endif
    </nav>
</div>
@endsection

@extends('layouts.app')
@section('title', 'أدعية من القرآن')
@section('back', route('more'))

@section('content')
@php $D = fn ($x) => \App\Services\Quran::digits($x); @endphp
<p class="text-muted">أدعية الأنبياء والصالحين كما وردت في المصحف. اضغطي على المرجع لقراءة التفسير.</p>
<ul class="mt-4 space-y-3">
    @foreach ($duas as $d)
        <li class="card px-5 pb-3 pt-4">
            <p class="text-sm font-semibold text-teal">{{ $d['who'] }}</p>
            <p class="quran mt-1">
                @foreach ($d['ayahs'] as $a){{ $a['text'] }}<span class="ayah-no">{{ $D($a['n']) }}</span> @endforeach
            </p>
            <a href="{{ route('quran.show', $d['s']) }}?a={{ $d['from'] }}" class="mt-1 inline-flex items-center gap-1 text-sm text-lapis-ink">سورة {{ $d['surah'] }} — {{ $D($d['from']) }} <x-icon name="forward" :size="14" /></a>
        </li>
    @endforeach
</ul>
@endsection

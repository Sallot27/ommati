@extends('layouts.app')
@section('title', 'ورد الختمة')
@section('back', route('khatma'))

@section('content')
@php $D = fn ($n) => \App\Services\Quran::digits($n); $lastPage = null; @endphp
<p class="text-center text-muted">الصفحات {{ $D($portion['from']) }} إلى {{ $D($portion['to']) }}</p>

@foreach ($groups as $g)
    @php $s = $g['surah']; @endphp
    @if ($g['ayahs'][0]['n'] === 1)
        <div class="mt-6 rounded-2xl border border-saffron/40 bg-saffron-soft py-3 text-center">
            <p class="text-lg font-semibold">سورة {{ $s['name'] }}</p>
        </div>
        @if (! in_array($s['n'], [1, 9]))
            <p class="quran mt-3 text-center">بِسْمِ ٱللَّهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ</p>
        @endif
    @endif
    <article class="quran mt-2 text-justify" style="text-align-last: center">
        @foreach ($g['ayahs'] as $a)
            @if ($lastPage !== null && $a['page'] !== $lastPage)
                <span class="my-3 flex items-center gap-3 font-sans text-sm text-muted" style="line-height:1"><span class="h-px flex-1 bg-line"></span>صفحة {{ $D($a['page']) }}<span class="h-px flex-1 bg-line"></span></span>
            @endif
            @php $lastPage = $a['page']; @endphp
            <span>{{ $a['text'] }}<span class="ayah-no">{{ $D($a['n']) }}</span></span>
        @endforeach
    </article>
@endforeach

<form method="POST" action="{{ route('khatma.done') }}" class="mt-8">
    @csrf
    <button class="btn btn-teal w-full text-lg"><x-icon name="check" /> أتممتُ الورد</button>
</form>
@endsection

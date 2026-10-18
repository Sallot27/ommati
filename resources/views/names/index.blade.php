@extends('layouts.app')
@section('title', 'أسماء الله الحسنى')
@section('back', route('more'))

@section('content')
<a href="{{ route('names.show', $today['index']) }}" class="khatam-band block rounded-2xl px-5 py-6 text-center text-white">
    <p class="text-white/75">اسم اليوم</p>
    <p class="quran mt-1 text-[2.4rem] leading-tight text-white">{{ $today['name'] }}</p>
    <p class="mt-2 text-white/85">تأمّلي معناه ←</p>
</a>

<p class="mt-5 text-muted">كل اسم مع آية ورد فيها من المصحف، ومعناه من التفسير الميسّر وتفسير السعدي.</p>
<ul class="mt-3 grid grid-cols-3 gap-2">
    @foreach ($names as $i => $n)
        <li>
            <a href="{{ route('names.show', $i) }}" @class(['card flex h-full items-center justify-center px-2 py-4 text-center hover:bg-surface-2', 'border-saffron bg-saffron-soft' => $i === $today['index']])>
                <span class="naskh text-lg">{{ $n['name'] }}</span>
            </a>
        </li>
    @endforeach
</ul>
<p class="mt-5 text-sm leading-relaxed text-muted">قال النبي ﷺ: «إن لله تسعة وتسعين اسماً…» — اقرئي الحديث وشرحه في <a class="underline" href="{{ route('hadith.show', 10416) }}">موسوعة الأحاديث</a>. وأسماء الله لا تنحصر في عدد، وهذه أسماء وردت في القرآن الكريم.</p>
@endsection

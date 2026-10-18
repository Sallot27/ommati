@extends('layouts.app')
@section('title', 'أسماء الله الحسنى')
@section('back', route('more'))

@section('content')
<a href="{{ route('names.show', $today['index']) }}" class="khatam-band block rounded-2xl px-5 py-6 text-center text-white">
    <p class="text-white/75">اسم اليوم</p>
    <p class="quran mt-1 text-[2.4rem] leading-tight text-white">{{ $today['name'] }}</p>
    <p class="mt-2 text-white/85">تأمّلي معناه ←</p>
</a>

<p class="mt-5 text-muted">تسعة وتسعون اسماً ثابتة في القرآن الكريم والسنة الصحيحة. كل اسم مع دليله: آية من المصحف مع تفسيرها، أو حديث صحيح.</p>
<ul class="mt-3 grid grid-cols-3 gap-2">
    @foreach ($names as $i => $n)
        <li>
            <a href="{{ route('names.show', $i) }}" @class(['card flex h-full items-center justify-center px-2 py-4 text-center hover:bg-surface-2', 'border-saffron bg-saffron-soft' => $i === $today['index']])>
                <span class="naskh text-lg">{{ $n['name'] }}</span>
            </a>
        </li>
    @endforeach
</ul>
<p class="mt-5 text-sm leading-relaxed text-muted">قال النبي ﷺ: «إن لله تسعة وتسعين اسماً، من أحصاها دخل الجنة» — اقرئي الحديث وشرحه في <a class="underline" href="{{ route('hadith.show', 10416) }}">موسوعة الأحاديث</a>. لم يثبت عن النبي ﷺ تعيين هذه الأسماء في حديث صحيح، فجُمعت هنا مما ثبت في الكتاب والسنة، على طريقة الشيخ ابن عثيمين رحمه الله في «القواعد المثلى». وأسماء الله أكثر من ذلك.</p>
@endsection

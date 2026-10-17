@extends('layouts.app')
@section('title', 'آية اليوم')
@section('back', route('home'))

@section('content')
@php $D = fn ($n) => \App\Services\Quran::digits($n); @endphp
<div class="space-y-5">
    <p class="text-center text-muted">
        {{ $date->isToday() ? 'آية اليوم' : $date->translatedFormat('j F') }} — سورة {{ $surah['name'] }}،
        {{ $from === $to ? 'الآية '.$D($from) : 'الآيات '.$D($from).' إلى '.$D($to) }}
    </p>

    <section class="card px-5 py-6" x-data="ayahAudio(@js(array_column($ayahs, 'audio')))">
        <p class="quran text-center">
            @foreach ($ayahs as $i => $a)
                <span :class="current === {{ $i }} ? 'bg-saffron-soft rounded-lg' : ''">{{ $a['text'] }}</span><span class="ayah-no">{{ $D($a['n']) }}</span>
            @endforeach
        </p>
        <div class="mt-4 flex justify-center gap-2">
            <button @click="play()" class="btn btn-primary">
                <x-icon name="pause" :size="20" x-show="playing" x-cloak />
                <x-icon name="play" :size="20" x-show="!playing" />
                <span x-text="playing ? 'إيقاف' : 'استماع'"></span>
            </button>
            <a href="{{ route('quran.show', $surah['n']) }}?a={{ $from }}" class="btn btn-soft">في المصحف</a>
        </div>
    </section>

    @if ($tadabbur['actions'] || $tadabbur['guidance'])
        <section class="rounded-2xl bg-teal-soft px-5 py-5">
            <h2 class="text-xl font-semibold text-teal">كيف أعمل بها؟</h2>
            @if ($tadabbur['actions'])
                <ul class="mt-3 space-y-3">
                    @foreach ($tadabbur['actions'] as $item)
                        <li class="flex gap-3">
                            <x-icon name="check" class="mt-1.5 text-teal" :size="22" />
                            <span class="naskh text-[1.1rem]">{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($tadabbur['guidance'])
                <h3 class="mt-5 font-semibold text-teal">توجيهات من الآيات</h3>
                <ul class="mt-2 space-y-2">
                    @foreach ($tadabbur['guidance'] as $item)
                        <li class="naskh text-[1.05rem]">• {{ $item }}</li>
                    @endforeach
                </ul>
            @endif
            <p class="mt-4 text-sm text-muted">من كتاب «{{ $sources['tadabbur']['name'] }}» — {{ $sources['tadabbur']['by'] }}. تشمل الآيات المحيطة بهذا الموضع من المصحف.</p>
        </section>
    @endif

    <section class="card px-5 py-5">
        <h2 class="text-xl font-semibold text-lapis-ink">التفسير المختصر</h2>
        @forelse ($muyassar as $t)
            <p class="naskh mt-3 whitespace-pre-line text-[1.1rem]">{{ $t }}</p>
        @empty
            <p class="mt-3 text-muted">تعذّر تحميل التفسير الآن، تأكدي من الاتصال بالإنترنت وأعيدي فتح الصفحة.</p>
        @endforelse
        <p class="mt-3 text-sm text-muted">{{ $sources['muyassar']['name'] }} — {{ $sources['muyassar']['by'] }}</p>
    </section>

    @if ($saadi)
        <details class="card group px-5 py-4">
            <summary class="flex cursor-pointer list-none items-center justify-between text-xl font-semibold text-lapis-ink">
                تفسير السعدي (أوسع)
                <x-icon name="plus" class="transition group-open:rotate-45" />
            </summary>
            @foreach ($saadi as $t)
                <div class="naskh prose-ar mt-3 whitespace-pre-line text-[1.08rem]">{{ $t }}</div>
            @endforeach
            <p class="mt-3 text-sm text-muted">{{ $sources['saadi']['name'] }} — {{ $sources['saadi']['by'] }}</p>
        </details>
    @endif

    @if ($tadabbur['reflections'])
        <section class="card px-5 py-5">
            <h2 class="text-xl font-semibold text-lapis-ink">وقفات للتدبّر</h2>
            @foreach ($tadabbur['reflections'] as $r)
                <figure class="mt-4 border-s-4 border-saffron ps-4">
                    <blockquote class="naskh text-[1.08rem]">{{ $r['body'] }}</blockquote>
                    @if ($r['question'])
                        <figcaption class="mt-2 rounded-xl bg-saffron-soft px-3 py-2 text-[0.98rem]">سؤال للتأمل: {{ $r['question'] }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </section>
    @endif

    <nav class="flex gap-3">
        <a href="{{ route('ayah.today', ['d' => $yesterday]) }}" class="btn btn-soft flex-1"><x-icon name="back" :size="18" /> آية الأمس</a>
        @if ($tomorrow)
            <a href="{{ route('ayah.today', ['d' => $tomorrow]) }}" class="btn btn-soft flex-1">اليوم التالي <x-icon name="forward" :size="18" /></a>
        @endif
    </nav>
</div>
@endsection

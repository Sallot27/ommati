@extends('layouts.app')
@section('title', 'سورة '.$surah['name'])
@section('back', route('quran.index'))

@section('content')
@php $D = fn ($n) => \App\Services\Quran::digits($n); @endphp
<div class="pb-20" x-data="quranReader({{ $surah['n'] }}, @js($reciter), {{ (int) request('a') ?: 'null' }}, {{ request()->boolean('play') ? 'true' : 'false' }})">

    <div class="mb-4 text-center">
        <p class="text-muted">{{ $surah['type'] }} · {{ $D($surah['count']) }} آية · الصفحة {{ $D($surah['pages'][0]) }}</p>
        @if (! in_array($surah['n'], [1, 9]))
            <p class="quran mt-3 text-center">بِسْمِ ٱللَّهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ</p>
        @endif
    </div>

    <p class="text-sm text-muted">اضغطي على أي آية لسماعها أو قراءة تفسيرها.</p>

    <article class="quran mt-2 text-justify" style="text-align-last: center">
        @foreach ($surah['ayahs'] as $i => $text)
            <span id="a-{{ $i + 1 }}" role="button" tabindex="0"
                  @click="openSheet({{ $i + 1 }})" @keydown.enter="openSheet({{ $i + 1 }})"
                  :class="current === {{ $i + 1 }} ? 'bg-saffron-soft rounded-lg' : (sheet === {{ $i + 1 }} ? 'bg-surface-2 rounded-lg' : '')"
                  class="cursor-pointer scroll-mt-28 transition-colors">{{ $text }}<span class="ayah-no">{{ $D($i + 1) }}</span></span>
        @endforeach
    </article>

    <nav class="mt-8 flex gap-3">
        @if ($next)
            <a href="{{ route('quran.show', $next['n']) }}" class="btn btn-soft flex-1"><x-icon name="back" :size="18" /> سورة {{ $next['name'] }}</a>
        @endif
        @if ($prev)
            <a href="{{ route('quran.show', $prev['n']) }}" class="btn btn-soft flex-1">سورة {{ $prev['name'] }} <x-icon name="forward" :size="18" /></a>
        @endif
    </nav>

    {{-- Player bar --}}
    <div class="fixed inset-x-0 z-30 px-3" style="bottom: calc(4.6rem + env(safe-area-inset-bottom))">
        <div class="mx-auto flex max-w-2xl items-center gap-2 rounded-2xl border border-line bg-surface px-3 py-2 shadow-lg shadow-black/10">
            <button @click="toggle()" class="grid size-12 shrink-0 place-items-center rounded-full bg-lapis-ink text-white dark:text-[#0f1823]" :aria-label="playing ? 'إيقاف' : 'تشغيل'">
                <x-icon name="pause" x-show="playing" x-cloak />
                <x-icon name="play" x-show="!playing" />
            </button>
            <div class="min-w-0 flex-1">
                <select x-model="reciter" @change="setReciter($event.target.value)" class="w-full truncate bg-transparent text-sm font-medium outline-none" aria-label="القارئ">
                    @foreach ($reciters as $id => $r)
                        <option value="{{ $id }}">{{ $r['name'] }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-muted" x-text="current ? 'الآية ' + ar(current) : 'اضغطي للاستماع من البداية'"></p>
            </div>
            <label class="flex items-center gap-1 text-sm text-muted">
                <x-icon name="repeat" :size="18" />
                <select x-model.number="repeat" class="bg-transparent outline-none" aria-label="تكرار كل آية">
                    <option value="1">١</option><option value="2">٢</option><option value="3">٣</option><option value="5">٥</option><option value="10">١٠</option>
                </select>
            </label>
        </div>
    </div>

    {{-- Ayah sheet --}}
    <div x-show="sheet" x-cloak class="fixed inset-0 z-50" @keydown.escape.window="sheet = null">
        <div class="absolute inset-0 bg-black/40" @click="sheet = null"></div>
        <section class="absolute inset-x-0 bottom-0 mx-auto max-h-[85dvh] max-w-2xl overflow-y-auto rounded-t-3xl bg-surface px-5 pb-8 pt-3 safe-bottom"
                 x-show="sheet" x-transition:enter="transition duration-200" x-transition:enter-start="translate-y-full" role="dialog" aria-modal="true" aria-label="الآية">
            <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-line"></div>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">سورة {{ $surah['name'] }} — الآية <span x-text="ar(sheet)"></span></h2>
                <button @click="sheet = null" class="grid size-10 place-items-center rounded-full hover:bg-surface-2" aria-label="إغلاق"><x-icon name="x" /></button>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <button @click="playFrom(sheet); sheet = null" class="btn btn-primary"><x-icon name="play" :size="20" /> استمعي</button>
                <a :href="'{{ route('hifz.learn', $surah['n']) }}?from=' + sheet" class="btn btn-soft"><x-icon name="sprout" :size="20" /> أحفظها</a>
            </div>

            <div class="mt-5 flex gap-2 overflow-x-auto" role="tablist">
                @foreach (['muyassar' => 'الميسّر', 'mukhtasar' => 'المختصر', 'saadi' => 'السعدي'] as $key => $label)
                    <button role="tab" @click="tafsirSource = '{{ $key }}'; loadTafsir()"
                            :aria-selected="tafsirSource === '{{ $key }}'"
                            :class="tafsirSource === '{{ $key }}' ? 'bg-lapis-ink text-white dark:text-[#0f1823]' : 'bg-surface-2'"
                            class="chip shrink-0 px-4 py-1.5">{{ $label }}</button>
                @endforeach
            </div>
            <div class="naskh mt-3 min-h-24 text-[1.08rem]">
                <p x-show="tafsirLoading" class="text-muted">جاري تحميل التفسير…</p>
                <p x-show="!tafsirLoading" x-text="tafsirText" class="whitespace-pre-line"></p>
            </div>
        </section>
    </div>
</div>
@endsection

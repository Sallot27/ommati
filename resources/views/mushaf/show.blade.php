@extends('layouts.app')
@section('title', 'المصحف')
@section('back', route('quran.index'))
@section('immersive', '1')

@section('header-actions')
    <button type="button" onclick="window.dispatchEvent(new Event('mushaf-index'))" class="btn btn-soft min-h-10 px-3 text-sm"><x-icon name="grid" :size="20" /> الفهرس</button>
@endsection

@section('content')
<div x-data="mushaf(@js($cfg))" @mushaf-index.window="sheet = 'index'" class="-mx-4 pb-16">

    {{-- Page --}}
    <div class="px-2">
        <div x-ref="page" class="mushaf-page mx-auto max-w-[34rem]">
            <div class="flex items-center justify-between px-1 pb-1.5 text-sm text-muted">
                <span x-text="surahTitle ? 'سورة ' + surahTitle : ''"></span>
                <span x-text="'الجزء ' + ar(meta.j)"></span>
            </div>

            <div class="mushaf-frame">
                <p x-show="loading && !lines.length" class="py-32 text-center text-muted">جاري تحميل صفحة المصحف…</p>
                <p x-show="error" x-cloak x-text="error" class="rounded-xl bg-saffron-soft px-4 py-3 text-center"></p>

                <div x-ref="lines" class="mushaf-lines" :class="{ 'opacity-40': loading, 'mushaf-opening': page <= 2 }" dir="rtl">
                    <template x-for="(line, i) in lines" :key="page + '-' + i">
                        <div class="mushaf-line" :data-line="lineKind(line)">
                            <template x-for="(w, j) in line" :key="j">
                                <span :style="'font-family:' + family(w)"
                                      :class="{ 'mushaf-hl': isAyah(w) && w.k === active, 'cursor-pointer': isAyah(w), 'mushaf-h': w.t === 'h' }"
                                      @click="isAyah(w) && select(w.k)"
                                      x-text="glyph(w)"></span>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <p class="pt-1.5 text-center text-sm text-muted" x-text="ar(page)"></p>
        </div>
        <p class="mt-1 text-center text-xs text-muted">اضغطي على أي آية لتفسيرها أو الاستماع لها · اسحبي للتقليب</p>
    </div>

    {{-- Bottom bar: RTL — previous on the right, next on the left --}}
    <div class="fixed inset-x-0 z-30 px-3" style="bottom: max(env(safe-area-inset-bottom), 0.75rem)">
        <div class="mx-auto flex max-w-2xl items-center gap-1.5 rounded-2xl border border-line bg-surface px-2 py-2 shadow-lg shadow-black/10">
            <button @click="prev()" :disabled="page <= 1" class="grid size-12 place-items-center rounded-xl bg-surface-2 disabled:opacity-40" aria-label="الصفحة السابقة">
                <x-icon name="back" :size="20" />
            </button>
            <button @click="toggle()" class="grid size-12 shrink-0 place-items-center rounded-full bg-lapis-ink text-white dark:text-[#0f1823]" :aria-label="playing ? 'إيقاف' : 'تشغيل'">
                <x-icon name="pause" x-show="playing" x-cloak />
                <x-icon name="play" x-show="!playing" />
            </button>
            <button @click="sheet = 'index'" class="min-w-0 flex-1 px-1 text-start">
                <span class="block truncate text-sm font-medium" x-text="reciters[reciter].name"></span>
                <span class="block truncate text-xs text-muted" x-text="playing && active ? keyLabel(active) : 'صفحة ' + ar(page) + ' من ٦٠٤'"></span>
            </button>
            <button @click="next()" :disabled="page >= 604" class="grid size-12 place-items-center rounded-xl bg-lapis-ink text-white disabled:opacity-40 dark:text-[#0f1823]" aria-label="الصفحة التالية">
                <x-icon name="forward" :size="20" />
            </button>
        </div>
    </div>

    {{-- Ayah sheet: listen + tafsir --}}
    <div x-show="sheet === 'ayah'" x-cloak class="fixed inset-0 z-50" @keydown.escape.window="closeSheet()">
        <div class="absolute inset-0 bg-black/30" @click="closeSheet()"></div>
        <section class="safe-bottom absolute inset-x-0 bottom-0 mx-auto max-h-[70dvh] max-w-2xl overflow-y-auto rounded-t-3xl bg-surface px-5 pb-8 pt-3" role="dialog" aria-modal="true" aria-label="الآية">
            <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-line"></div>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold" x-text="keyLabel(sel)"></h2>
                <button @click="closeSheet()" class="grid size-10 place-items-center rounded-full hover:bg-surface-2" aria-label="إغلاق"><x-icon name="x" /></button>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <button @click="playFrom(sel)" class="btn btn-primary"><x-icon name="play" :size="20" /> استمعي</button>
                <a :href="sel ? '{{ url('/hifz/learn') }}/' + sel.split(':')[0] + '?from=' + sel.split(':')[1] : '#'" class="btn btn-soft"><x-icon name="sprout" :size="20" /> أحفظها</a>
            </div>
            <div class="mt-5 flex gap-2 overflow-x-auto" role="tablist">
                @foreach (['muyassar' => 'الميسّر', 'mukhtasar' => 'المختصر', 'saadi' => 'السعدي', 'tadabbur' => 'تدبّر وعمل'] as $key => $label)
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

    {{-- Index sheet: surahs, juz, page, reciter --}}
    <div x-show="sheet === 'index'" x-cloak class="fixed inset-0 z-50" @keydown.escape.window="sheet = null">
        <div class="absolute inset-0 bg-black/40" @click="sheet = null"></div>
        <section class="safe-bottom absolute inset-x-0 bottom-0 mx-auto flex max-h-[85dvh] max-w-2xl flex-col rounded-t-3xl bg-surface px-5 pt-3" role="dialog" aria-modal="true" aria-label="فهرس المصحف">
            <div class="mx-auto mb-3 h-1.5 w-12 shrink-0 rounded-full bg-line"></div>

            <label class="block shrink-0">
                <span class="text-sm text-muted">القارئ</span>
                <select x-model="reciter" @change="setReciter($event.target.value)" class="mt-1 w-full rounded-xl border border-line bg-surface-2 px-3 py-2.5">
                    <template x-for="(r, id) in reciters" :key="id">
                        <option :value="id" x-text="r.name" :selected="id === reciter"></option>
                    </template>
                </select>
            </label>

            <div class="mt-4 grid shrink-0 grid-cols-3 gap-1 rounded-xl bg-surface-2 p-1" role="tablist">
                <button role="tab" @click="tab = 'surah'" :class="tab === 'surah' ? 'bg-surface shadow-sm' : ''" class="rounded-lg py-2">السور</button>
                <button role="tab" @click="tab = 'juz'" :class="tab === 'juz' ? 'bg-surface shadow-sm' : ''" class="rounded-lg py-2">الأجزاء</button>
                <button role="tab" @click="tab = 'page'" :class="tab === 'page' ? 'bg-surface shadow-sm' : ''" class="rounded-lg py-2">صفحة</button>
            </div>

            <div class="mt-3 min-h-0 flex-1 overflow-y-auto pb-8">
                <div x-show="tab === 'surah'">
                    <input x-model="q" type="search" placeholder="ابحثي عن سورة" class="mb-2 w-full rounded-xl border border-line bg-surface-2 px-3 py-2.5">
                    <ul class="divide-y divide-line">
                        <template x-for="s in filtered" :key="s.n">
                            <li>
                                <button @click="goPage(s.page)" class="flex w-full items-center gap-3 py-2.5 text-start" :class="meta.s.includes(s.n) ? 'text-lapis font-semibold' : ''">
                                    <span class="star-badge"><span x-text="ar(s.n)"></span></span>
                                    <span class="flex-1" x-text="'سورة ' + s.name"></span>
                                    <span class="text-sm text-muted" x-text="'ص ' + ar(s.page)"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
                <div x-show="tab === 'juz'" class="grid grid-cols-3 gap-2">
                    <template x-for="(p, j) in juz" :key="j">
                        <button @click="goPage(p)" class="rounded-xl border border-line py-3" :class="meta.j == j ? 'bg-lapis-ink text-white dark:text-[#0f1823]' : 'bg-surface-2'">
                            <span class="block font-medium" x-text="'الجزء ' + ar(j)"></span>
                            <span class="text-xs opacity-70" x-text="'ص ' + ar(p)"></span>
                        </button>
                    </template>
                </div>
                <form x-show="tab === 'page'" @submit.prevent="goJump()" class="flex gap-2">
                    <input x-model="jump" inputmode="numeric" :placeholder="'من ١ إلى ٦٠٤'" class="flex-1 rounded-xl border border-line bg-surface-2 px-3 py-3 text-center text-lg" aria-label="رقم الصفحة">
                    <button class="btn btn-primary px-6">اذهبي</button>
                </form>

                <p class="mt-6 text-xs leading-6 text-muted">
                    صفحات مصحف المدينة النبوية برواية حفص عن عاصم (إصدار ١٤٤١هـ، ٦٠٤ صفحات) بخط عثمان طه، وخطوطه من مجمع الملك فهد لطباعة المصحف الشريف.
                    التلاوات من cdn.islamic.network، والتفاسير من quran.com.
                </p>
            </div>
        </section>
    </div>
</div>
@endsection

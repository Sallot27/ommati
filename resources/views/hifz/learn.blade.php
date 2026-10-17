@extends('layouts.app')
@section('title', 'حفظ سورة '.$surah['name'])
@section('back', route('hifz.index'))

@section('content')
<div x-data="hifzSession(@js(['surah' => $surah['n'], 'name' => $surah['name'], 'count' => $surah['count'], 'from' => $from, 'memorized' => $memorized, 'target' => $target, 'reciter' => $reciter]))" x-cloak>
    <p x-show="loading" class="py-10 text-center text-muted">جاري التحميل…</p>

    <template x-if="!loading && !done">
        <div>
            <div class="flex items-center justify-between">
                <p class="text-muted">الآية <span x-text="ar(index)"></span> من <span x-text="ar(total)"></span></p>
                <div class="flex gap-1" aria-label="ورد اليوم">
                    <template x-for="n in target" :key="n">
                        <span class="size-2.5 rounded-full" :class="n <= learnedToday.length ? 'bg-teal' : 'bg-line'"></span>
                    </template>
                </div>
            </div>

            <ol class="mt-3 grid grid-cols-3 gap-1 text-center text-sm">
                <li class="rounded-full py-1" :class="step === 'listen' ? 'bg-lapis-ink text-white dark:text-[#0f1823]' : 'bg-surface-2 text-muted'">١. استمعي</li>
                <li class="rounded-full py-1" :class="step === 'read' ? 'bg-lapis-ink text-white dark:text-[#0f1823]' : 'bg-surface-2 text-muted'">٢. اقرئي</li>
                <li class="rounded-full py-1" :class="step === 'recite' ? 'bg-lapis-ink text-white dark:text-[#0f1823]' : 'bg-surface-2 text-muted'">٣. سمّعي</li>
            </ol>

            <template x-if="index > 1 && ayahs[index - 2]">
                <p class="quran mt-4 text-center text-[1.15rem] leading-loose text-muted" x-text="ayahs[index - 2].text"></p>
            </template>

            <section class="card mt-3 px-4 py-6" @click="step === 'recite' && reveal()">
                <p class="quran text-center">
                    <template x-for="(w, i) in words" :key="index + '-' + i">
                        <span :class="i === words.length - 1 ? 'whitespace-nowrap' : ''"><span :class="wordClass(i)" :data-first="firstLetter(w)" x-text="w"></span><template x-if="i === words.length - 1"><span class="ayah-no" x-text="ar(index)"></span></template> </span>
                    </template>
                </p>
                <p x-show="step === 'recite' && revealed < words.length" class="mt-3 text-center text-sm text-muted">اقرئي من حفظك، ثم اضغطي هنا لتظهر الكلمة التالية وتتأكدي</p>
            </section>

            {{-- Step 1: listen --}}
            <div x-show="step === 'listen'" class="mt-4 space-y-3">
                <div class="flex items-center justify-center gap-2 text-muted">
                    <span>كرّري الاستماع</span>
                    @foreach ([3, 5, 7, 10] as $n)
                        <button @click="repeats = {{ $n }}" class="chip size-10 border" :class="repeats === {{ $n }} ? 'border-teal bg-teal-soft text-teal' : 'border-line'">{{ \App\Services\Quran::digits($n) }}</button>
                    @endforeach
                </div>
                <button @click="listen()" class="btn btn-teal w-full text-lg">
                    <x-icon name="pause" x-show="playing" />
                    <x-icon name="play" x-show="!playing" />
                    <span x-text="playing ? 'إيقاف' : (played ? 'أكملي الاستماع' : 'استمعي للآية')"></span>
                </button>
                <p class="text-center text-sm text-muted">استمعتِ <span x-text="ar(played)"></span> من <span x-text="ar(repeats)"></span> مرات</p>
                <button @click="toRead()" class="btn btn-primary w-full">اقرئيها بنفسك الآن</button>
            </div>

            {{-- Step 2: read with fading --}}
            <div x-show="step === 'read'" class="mt-4 space-y-3">
                <p class="text-center text-muted" x-text="['اقرئي الآية كاملة عدة مرات', 'الآن تظهر أوائل الكلمات فقط', 'الآن تظهر الكلمة الأولى فقط', 'الآية مخفية — اقرئيها من حفظك'][fade]"></p>
                <button @click="moreFade()" class="btn btn-primary w-full" x-text="fade < 3 ? 'أخفي أكثر' : 'سمّعيها الآن'"></button>
                <div class="grid grid-cols-2 gap-2">
                    <button @click="fade = Math.max(0, fade - 1)" class="btn btn-soft"><x-icon name="eye" :size="20" /> أظهري أكثر</button>
                    <button @click="played = 0; step = 'listen'" class="btn btn-soft"><x-icon name="headphones" :size="20" /> استماع</button>
                </div>
            </div>

            {{-- Step 3: recite --}}
            <div x-show="step === 'recite'" class="mt-4 space-y-3">
                <template x-if="revealed < words.length">
                    <div class="grid grid-cols-2 gap-2">
                        <button @click="reveal()" class="btn btn-primary">الكلمة التالية</button>
                        <button @click="revealAll()" class="btn btn-soft">أظهري الكل</button>
                    </div>
                </template>
                <template x-if="revealed >= words.length">
                    <div class="space-y-2">
                        <p class="text-center text-lg">هل سمّعتِها بلا خطأ؟</p>
                        <button @click="gotIt()" class="btn btn-teal w-full text-lg"><x-icon name="check" /> نعم، حفظتها</button>
                        <button @click="again()" class="btn btn-soft w-full"><x-icon name="repeat" :size="20" /> أحتاج تكرار</button>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Linking the new ayat together --}}
    <div x-show="linking" class="fixed inset-0 z-50 grid place-items-end bg-black/40 sm:place-items-center">
        <section class="max-h-[85dvh] w-full max-w-2xl overflow-y-auto rounded-t-3xl bg-surface px-5 pb-8 pt-5 safe-bottom sm:rounded-3xl">
            <h2 class="text-xl font-semibold">اربطي ما حفظتِ اليوم</h2>
            <p class="mt-1 text-muted">اقرئي الآيات متتابعة من أولها، أو استمعي لها متصلة.</p>
            <p class="quran mt-3 text-center">
                <template x-for="n in learnedToday" :key="n">
                    <span><span x-text="ayahs[n - 1]?.text"></span><span class="ayah-no" x-text="ar(n)"></span> </span>
                </template>
            </p>
            <div class="mt-4 grid grid-cols-2 gap-2">
                <button @click="playLink()" class="btn btn-soft"><x-icon name="headphones" :size="20" /> <span x-text="playing ? 'إيقاف' : 'استماع'"></span></button>
                <button @click="nextAyah()" class="btn btn-primary">أكملي</button>
            </div>
        </section>
    </div>

    {{-- Done for today --}}
    <template x-if="done">
        <section class="rounded-2xl bg-teal px-5 py-8 text-center text-white dark:text-[#0f1823]">
            <x-icon name="star" :size="44" class="mx-auto" />
            <p class="mt-3 text-2xl font-semibold">بارك الله فيكِ!</p>
            <p class="mt-1 text-lg opacity-90">حفظتِ اليوم <span x-text="ar(learnedToday.length)"></span> آيات من سورة <span x-text="name"></span></p>
            <p class="mt-2 opacity-90">سنذكّرك بمراجعتها غداً بإذن الله.</p>
            <div class="mt-5 grid gap-2">
                <button x-show="index < total" @click="keepGoing()" class="btn bg-white/20">أريد أن أحفظ المزيد</button>
                <a href="{{ route('hifz.index') }}" class="btn bg-white/20">العودة</a>
            </div>
        </section>
    </template>
</div>
@endsection

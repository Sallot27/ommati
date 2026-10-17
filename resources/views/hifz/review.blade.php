@extends('layouts.app')
@section('title', 'مراجعة المحفوظ')
@section('back', route('hifz.index'))

@section('content')
@if ($items->isEmpty())
    <div class="card px-5 py-8 text-center">
        <x-icon name="check" :size="40" class="mx-auto text-teal" />
        <p class="mt-3 text-xl font-semibold">لا توجد مراجعة اليوم</p>
        <p class="mt-1 text-muted">أحسنتِ! ستظهر الآيات هنا حين يحين موعد مراجعتها.</p>
        <a href="{{ route('hifz.index') }}" class="btn btn-primary mt-5">ورد الحفظ</a>
    </div>
@else
<div x-data="reviewSession(@js($items))">
    <template x-if="!finished">
        <div>
            <p class="text-muted">سورة <span x-text="item.name"></span> — الآية <span x-text="ar(item.a)"></span> <span class="float-left" x-text="ar(i + 1) + ' من ' + ar(items.length)"></span></p>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-surface-2"><div class="h-full bg-teal transition-all" :style="`width: ${i / items.length * 100}%`"></div></div>

            <template x-if="item.prev">
                <div class="mt-4">
                    <p class="text-sm text-muted">ما قبلها:</p>
                    <p class="quran text-center text-[1.2rem] text-muted" x-text="item.prev"></p>
                </div>
            </template>

            <section class="card mt-3 px-4 py-6 text-center">
                <p x-show="!shown" class="py-6 text-lg text-muted">سمّعي الآية من حفظك، ثم اضغطي «أظهري الآية» للتأكد</p>
                <p x-show="shown" class="quran" x-text="item.text"></p>
            </section>

            <div class="mt-4 space-y-2">
                <div class="grid grid-cols-2 gap-2">
                    <button @click="shown = !shown" class="btn btn-primary"><x-icon name="eye" :size="20" /> <span x-text="shown ? 'أخفيها' : 'أظهري الآية'"></span></button>
                    <button @click="play()" class="btn btn-soft"><x-icon name="headphones" :size="20" /> <span x-text="playing ? 'إيقاف' : 'استماع'"></span></button>
                </div>
                <div x-show="shown" class="grid grid-cols-2 gap-2">
                    <button @click="rate('good')" class="btn btn-teal"><x-icon name="check" :size="20" /> حفظتها جيداً</button>
                    <button @click="rate('again')" class="btn btn-soft"><x-icon name="repeat" :size="20" /> أخطأت فيها</button>
                </div>
            </div>
        </div>
    </template>
    <template x-if="finished">
        <section class="rounded-2xl bg-teal px-5 py-8 text-center text-white dark:text-[#0f1823]">
            <p class="text-2xl font-semibold">أتممتِ المراجعة</p>
            <p class="mt-2 opacity-90">ثابتة: <span x-text="ar(results.good)"></span> · تحتاج تكرار: <span x-text="ar(results.again)"></span></p>
            <p class="mt-1 opacity-90">الآيات التي أخطأتِ فيها ستعود لك غداً.</p>
            <a href="{{ route('hifz.index') }}" class="btn mt-5 bg-white/20">العودة</a>
        </section>
    </template>
</div>
@endif
@endsection

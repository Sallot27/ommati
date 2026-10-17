@extends('layouts.app')
@section('title', 'الإعدادات')
@section('back', route('more'))

@section('content')
@php $D = fn ($n) => \App\Services\Quran::digits($n); @endphp
<div class="space-y-5">
    <section class="card px-5 py-5" x-data>
        <h2 class="font-semibold">حجم الخط</h2>
        <div class="mt-3 flex items-center gap-3">
            <button @click="$store.prefs.setSize($store.prefs.size - 1)" class="btn btn-soft size-14 text-lg" aria-label="تصغير الخط">أ−</button>
            <div class="flex flex-1 justify-center gap-1.5">
                <template x-for="n in 5" :key="n">
                    <span class="h-2.5 flex-1 rounded-full" :class="n <= $store.prefs.size ? 'bg-teal' : 'bg-surface-2'"></span>
                </template>
            </div>
            <button @click="$store.prefs.setSize($store.prefs.size + 1)" class="btn btn-soft size-14 text-2xl" aria-label="تكبير الخط">أ+</button>
        </div>
        <p class="quran mt-3 text-center">ٱلْحَمْدُ لِلَّهِ رَبِّ ٱلْعَٰلَمِينَ</p>

        <h2 class="mt-5 font-semibold">المظهر</h2>
        <div class="mt-2 grid grid-cols-3 gap-2">
            @foreach (['light' => 'فاتح', 'dark' => 'داكن', 'auto' => 'تلقائي'] as $k => $label)
                <button @click="$store.prefs.setTheme('{{ $k }}')" class="btn border" :class="$store.prefs.theme === '{{ $k }}' ? 'border-teal bg-teal-soft text-teal' : 'border-line bg-surface'">{{ $label }}</button>
            @endforeach
        </div>
    </section>

    <section class="card px-5 py-5" x-data="pushSettings(@js($vapid))">
        <h2 class="flex items-center gap-2 font-semibold"><x-icon name="bell" :size="20" /> التذكيرات</h2>
        <p class="mt-1 text-muted">تصلك تنبيهات بالأذكار وأوقات الصلاة وورد الحفظ على هذا الجهاز.</p>
        <ul class="mt-3 space-y-1 text-[0.95rem]">
            <li>أذكار الصباح: {{ $D($reminders['morning'] ?? '—') }}</li>
            <li>آية اليوم وحديثه: {{ $D($reminders['daily'] ?? '—') }}</li>
            <li>ورد الحفظ: {{ $D($reminders['hifz'] ?? '—') }}</li>
            <li>أذكار المساء: {{ $D($reminders['evening'] ?? '—') }}</li>
            <li>أذكار النوم: {{ $D($reminders['sleep'] ?? '—') }}</li>
            <li>عند دخول وقت كل صلاة</li>
        </ul>

        <template x-if="!supported">
            <div class="mt-4 rounded-xl bg-saffron-soft px-4 py-3">
                <template x-if="isIOS && !standalone">
                    <p>على الآيفون: أضيفي التطبيق للشاشة الرئيسية أولاً، ثم افتحيه من الأيقونة وفعّلي التذكيرات. <a href="{{ route('install') }}" class="underline">الطريقة</a></p>
                </template>
                <template x-if="!(isIOS && !standalone)">
                    <p>هذا المتصفح لا يدعم الإشعارات.</p>
                </template>
            </div>
        </template>

        <template x-if="supported">
            <div class="mt-4 space-y-2">
                @if ($vapid)
                    <button x-show="!enabled" @click="enable()" :disabled="busy" class="btn btn-teal w-full text-lg">تفعيل التذكيرات</button>
                    <div x-show="enabled" class="grid grid-cols-2 gap-2">
                        <button @click="test()" class="btn btn-soft">إشعار تجريبي</button>
                        <button @click="disable()" :disabled="busy" class="btn btn-soft">إيقاف</button>
                    </div>
                @else
                    <p class="rounded-xl bg-saffron-soft px-4 py-3">التذكيرات غير مُعدّة بعد (مفاتيح VAPID).</p>
                @endif
                <p x-show="message" x-text="message" class="text-center text-muted"></p>
            </div>
        </template>
    </section>

    <form method="POST" action="{{ route('settings.update') }}" class="card space-y-4 px-5 py-5">
        @csrf
        <label class="block">
            <span class="font-semibold">القارئ المفضّل</span>
            <select name="reciter" class="mt-2 w-full rounded-xl border border-line bg-surface-2 px-3 py-3 text-lg"
                    onchange="try { localStorage.setItem('reciter', JSON.stringify(this.value)) } catch (e) {}">
                @foreach ($reciters as $id => $r)
                    <option value="{{ $id }}" @selected($id === $reciter)>{{ $r['name'] }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="font-semibold">عدد آيات الحفظ اليومية</span>
            <select name="hifz_daily_target" class="mt-2 w-full rounded-xl border border-line bg-surface-2 px-3 py-3 text-lg">
                @foreach ([1, 2, 3, 5, 7, 10] as $n)
                    <option value="{{ $n }}" @selected($n === $target)>{{ $D($n) }}</option>
                @endforeach
            </select>
        </label>
        <button class="btn btn-primary w-full">حفظ</button>
    </form>
</div>
@endsection

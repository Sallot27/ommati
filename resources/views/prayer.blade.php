@extends('layouts.app')
@section('title', 'المواقيت والقبلة')
@section('back', route('more'))

@section('content')
@php $defaults = ['lat' => $lat, 'lng' => $lng, 'place' => $place]; @endphp
<div x-data="prayerTimes(@js($defaults))" class="space-y-5">
    <section class="card px-5 py-4">
        <div class="flex items-center justify-between gap-2">
            <div>
                <p class="flex items-center gap-1 text-lg font-semibold"><x-icon name="pin" :size="20" class="text-teal" /> <span x-text="place"></span></p>
                <p class="text-sm text-muted" x-text="today?.hijri"></p>
            </div>
            <button @click="locate()" class="btn btn-soft min-h-10 text-sm" :disabled="locating">
                <span x-text="locating ? 'جاري التحديد…' : 'موقعي الحالي'"></span>
            </button>
        </div>
        <label class="mt-3 block">
            <span class="text-sm text-muted">أو اختاري المدينة</span>
            <select class="mt-1 w-full rounded-xl border border-line bg-surface-2 px-3 py-2.5 text-lg"
                    @change="const o = $event.target.selectedOptions[0]; if (o.value) setPlace(o.value, +o.dataset.lat, +o.dataset.lng)">
                <option value="">— اختاري —</option>
                @foreach ($cities as $name => [$cLat, $cLng])
                    <option value="{{ $name }}" data-lat="{{ $cLat }}" data-lng="{{ $cLng }}">{{ $name }}</option>
                @endforeach
            </select>
        </label>
    </section>

    <p x-show="error" x-text="error" class="rounded-xl bg-saffron-soft px-4 py-3" x-cloak></p>

    <section class="card overflow-hidden" x-show="today" x-cloak>
        <div class="bg-lapis-ink px-5 py-4 text-white dark:text-[#0f1823]" x-show="next">
            <p class="opacity-80">الصلاة القادمة</p>
            <p class="text-2xl font-semibold"><span x-text="next?.name"></span> — <span x-text="next ? fmt(next.time) : ''"></span></p>
            <p class="opacity-90" x-text="countdown"></p>
        </div>
        <ul class="divide-y divide-line">
            <template x-for="key in ['Fajr','Sunrise','Dhuhr','Asr','Maghrib','Isha']" :key="key">
                <li class="flex items-center justify-between px-5 py-3.5 text-lg" :class="isNext(key) ? 'bg-teal-soft font-semibold text-teal' : ''">
                    <span x-text="names[key]"></span>
                    <span x-text="today ? fmt(today.timings[key]) : ''"></span>
                </li>
            </template>
        </ul>
        <p class="px-5 pb-3 text-sm text-muted">حسب تقويم أم القرى. المصدر: AlAdhan.</p>
    </section>

    <section class="card px-5 py-5 text-center" x-data="qibla(@js($defaults))">
        <h2 class="text-xl font-semibold">اتجاه القبلة</h2>
        <div class="relative mx-auto mt-4 size-64">
            <svg viewBox="0 0 200 200" class="size-full" aria-hidden="true">
                <circle cx="100" cy="100" r="92" fill="none" stroke="var(--line)" stroke-width="2"/>
                <circle cx="100" cy="100" r="78" fill="none" stroke="var(--line)" stroke-dasharray="2 6"/>
                <g :style="heading !== null ? `transform: rotate(${-heading}deg); transform-origin: 100px 100px; transition: transform .2s` : ''">
                    <text x="100" y="24" text-anchor="middle" font-size="13" fill="var(--muted)">ش</text>
                    <text x="100" y="186" text-anchor="middle" font-size="13" fill="var(--muted)">ج</text>
                    <text x="184" y="104" text-anchor="middle" font-size="13" fill="var(--muted)">شرق</text>
                    <text x="16" y="104" text-anchor="middle" font-size="13" fill="var(--muted)">غرب</text>
                </g>
                <g :style="`transform: rotate(${needle}deg); transform-origin: 100px 100px; transition: transform .2s`">
                    <path d="M100 30 L110 100 L100 92 L90 100 Z" :fill="aligned ? 'var(--teal)' : 'var(--saffron)'"/>
                    <rect x="92" y="18" width="16" height="16" rx="2" fill="var(--text)"/>
                    <rect x="92" y="22" width="16" height="3" fill="var(--saffron)"/>
                </g>
                <circle cx="100" cy="100" r="6" fill="var(--text)"/>
            </svg>
        </div>
        <p class="mt-2 text-lg" x-show="active" :class="aligned ? 'text-teal font-semibold' : ''" x-text="aligned ? 'أنتِ باتجاه القبلة ✓' : 'أديري الجوال حتى يشير السهم للأعلى'"></p>
        <p class="mt-2 text-muted" x-show="!active">القبلة على بُعد <span x-text="ar(Math.round(bearing))"></span>° من الشمال باتجاه عقارب الساعة.</p>
        <button x-show="needsPermission && !active" @click="enable()" class="btn btn-primary mt-3">تفعيل البوصلة</button>
        <p class="mt-2 text-sm text-muted">ضعي الجوال مستوياً، وابتعدي عن المعادن والمغناطيس.</p>
    </section>
</div>
@endsection

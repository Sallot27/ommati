@props(['q', 'imams', 'surahName', 'label' => null])
@php
    $imam = $imams[$q['who']] ?? ['name' => $q['who']];
    $words = preg_split('/\s+/u', trim(preg_replace('/\([٠-٩0-9]+\)/u', ' ', $q['ayah'])));
    $ayah = implode(' ', array_slice($words, 0, 9)).(count($words) > 9 ? ' …' : '');
@endphp
<figure {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    @if ($label)
        <p class="bg-saffron-soft px-5 py-2 text-sm font-semibold">{{ $label }}</p>
    @endif
    <div class="px-5 pb-4 pt-4">
        <p class="quran text-center text-[1.25rem] leading-loose text-muted">﴿{{ $ayah }}﴾</p>
        <blockquote class="naskh mt-2 border-s-4 border-saffron ps-4 text-[1.12rem]">{{ $q['text'] }}</blockquote>
        <figcaption class="mt-3 text-sm leading-relaxed text-muted">
            <span class="block font-semibold text-ink">{{ $imam['name'] }} رحمه الله <span class="font-normal text-muted">({{ $q['ref'] }})</span></span>
            تعليقاً على آية من سورة {{ $surahName($q['surah']) }}
        </figcaption>
    </div>
</figure>

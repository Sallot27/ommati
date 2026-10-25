@props(['book', 'size' => 'md'])
@php
    $cls = ['sm' => 'h-16 w-12 rounded-md', 'md' => 'h-24 w-[4.25rem] rounded-lg', 'lg' => 'h-56 w-40 rounded-xl'][$size];
@endphp
@if ($book->hasCover())
    <img src="{{ route('library.cover', $book) }}?v={{ $book->updated_at?->timestamp }}" alt="" loading="lazy"
         {{ $attributes->merge(['class' => "$cls shrink-0 border border-line bg-surface-2 object-cover shadow-sm"]) }}>
@else
    <span {{ $attributes->merge(['class' => "$cls khatam-band grid shrink-0 place-items-center p-1.5 text-center shadow-sm"]) }}>
        <span class="naskh line-clamp-4 text-[0.62rem] leading-snug text-white/90 {{ $size === 'lg' ? '!text-base' : '' }}">{{ $book->title }}</span>
    </span>
@endif

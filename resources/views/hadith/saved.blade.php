@extends('layouts.app')
@section('title', 'الأحاديث')
@section('back', route('more'))

@section('content')
<a href="{{ route('hadith.today') }}" class="btn btn-primary mb-5 w-full">حديث اليوم</a>

<h2 class="mb-2 font-semibold">الأحاديث المحفوظة</h2>
@forelse ($saved as $h)
    <a href="{{ route('hadith.show', $h->id) }}" class="card mb-2 flex items-center gap-3 px-4 py-3.5 hover:bg-surface-2">
        <x-icon name="heart-fill" class="text-rose" :size="20" />
        <span class="flex-1">{{ $h->title }}</span>
    </a>
@empty
    <p class="card px-4 py-5 text-muted">اضغطي «احفظيه» تحت أي حديث ليظهر هنا.</p>
@endforelse

@if ($recent->isNotEmpty())
    <h2 class="mb-2 mt-6 font-semibold">أحاديث الأيام الماضية</h2>
    <ul class="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
        @foreach ($recent as $h)
            <li><a href="{{ route('hadith.show', $h->id) }}" class="block px-4 py-3.5 hover:bg-surface-2">{{ $h->title }}</a></li>
        @endforeach
    </ul>
@endif
@endsection

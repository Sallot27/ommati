@extends('layouts.app')
@section('title', $book->title)
@section('back', route('library.show', $book))

@php
    $cfg = [
        'page' => $page,
        'streamUrl' => route('library.stream', $book),
        'progressUrl' => route('library.progress', $book),
        'bookmarkUrl' => url('/library/'.$book->id.'/bookmarks'),
        'coverUrl' => route('library.cover.store', $book),
        'hasCover' => $book->hasCover(),
        'bookmarks' => $bookmarks->map(fn ($b) => ['id' => $b->id, 'page' => $b->page, 'note' => $b->note])->values(),
    ];
@endphp

@section('header-actions')
    <span class="text-sm text-muted" data-needs-pdf></span>
@endsection

@section('content')
<div x-data="pdfReader(@js($cfg))" class="-mx-4 pb-24">
    <p x-show="loading" class="px-4 py-16 text-center text-muted">جاري فتح الكتاب…</p>
    <div x-show="error" x-cloak class="mx-4 rounded-2xl bg-saffron-soft px-5 py-4">
        <p x-text="error"></p>
        <a href="{{ route('library.file', ['book' => $book, 'download' => 1]) }}" class="btn btn-soft mt-3">حمّلي نسخة على الجهاز</a>
    </div>

    <div x-ref="stage" class="overflow-x-auto bg-surface-2 px-2 py-2" x-show="!loading && !error">
        <canvas x-ref="canvas" class="mx-auto block bg-white shadow"></canvas>
    </div>

    {{-- Reading controls --}}
    <div x-show="!loading && !error" x-cloak class="fixed inset-x-0 z-30 px-3" style="bottom: calc(5.4rem + env(safe-area-inset-bottom))">
        <div class="mx-auto flex max-w-2xl items-center gap-1.5 rounded-2xl border border-line bg-surface px-2 py-2 shadow-lg shadow-black/10">
            {{-- RTL: previous page on the right, next page on the left --}}
            <button @click="prev()" :disabled="page <= 1" class="flex h-12 items-center gap-1 rounded-xl bg-surface-2 px-3 disabled:opacity-40" aria-label="الصفحة السابقة">
                <x-icon name="back" :size="20" /> <span class="text-sm">السابقة</span>
            </button>
            <form @submit.prevent="goJump()" class="flex flex-1 items-center justify-center gap-1 text-sm">
                <input x-model="jump" :placeholder="ar(page)" inputmode="numeric" class="w-12 rounded-lg border border-line bg-surface-2 px-1 py-1.5 text-center text-base" aria-label="اذهبي إلى صفحة">
                <span class="whitespace-nowrap text-muted">/ <span x-text="ar(pages)"></span></span>
            </form>
            <button @click="sheet = true" class="grid size-12 place-items-center rounded-xl" :class="isBookmarked() ? 'bg-saffron-soft text-saffron' : 'bg-surface-2'" aria-label="العلامات">
                <x-icon name="star" />
            </button>
            <button @click="next()" :disabled="page >= pages" class="flex h-12 items-center gap-1 rounded-xl bg-lapis-ink px-3 text-white disabled:opacity-40 dark:text-[#0f1823]" aria-label="الصفحة التالية">
                <span class="text-sm">التالية</span> <x-icon name="forward" :size="20" />
            </button>
        </div>
    </div>

    {{-- Bookmarks sheet --}}
    <div x-show="sheet" x-cloak class="fixed inset-0 z-50" @keydown.escape.window="sheet = false">
        <div class="absolute inset-0 bg-black/40" @click="sheet = false"></div>
        <section class="safe-bottom absolute inset-x-0 bottom-0 mx-auto max-h-[80dvh] max-w-2xl overflow-y-auto rounded-t-3xl bg-surface px-5 pb-8 pt-3" role="dialog" aria-modal="true" aria-label="العلامات">
            <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-line"></div>
            <h2 class="text-xl font-semibold">ضعي علامة على صفحة <span x-text="ar(page)"></span></h2>
            <form @submit.prevent="addBookmark()" class="mt-3 space-y-2">
                <input x-model="note" maxlength="300" placeholder="ملاحظة (اختياري)" class="w-full rounded-xl border border-line bg-surface-2 px-3 py-3">
                <button class="btn btn-primary w-full"><x-icon name="star" :size="20" /> حفظ العلامة</button>
            </form>
            <p class="mt-2 text-sm text-muted">مكان توقفك يُحفظ تلقائياً — العلامات للصفحات التي تحبين الرجوع إليها.</p>

            <h3 class="mt-5 font-semibold" x-show="bookmarks.length">علاماتك</h3>
            <ul class="mt-1 divide-y divide-line">
                <template x-for="b in bookmarks" :key="b.id">
                    <li class="flex items-center gap-3 py-2.5">
                        <button @click="go(b.page); sheet = false" class="flex-1 text-start">
                            <span class="font-medium">صفحة <span x-text="ar(b.page)"></span></span>
                            <span class="block text-sm text-muted" x-text="b.note" x-show="b.note"></span>
                        </button>
                        <button @click="removeBookmark(b)" class="grid size-9 place-items-center rounded-full text-muted hover:bg-surface-2" aria-label="حذف العلامة"><x-icon name="x" :size="18" /></button>
                    </li>
                </template>
            </ul>

            <div class="mt-5 flex items-center justify-center gap-3">
                <button @click="setZoom(zoom - 0.5)" class="btn btn-soft size-12 text-lg" aria-label="تصغير">−</button>
                <span class="text-muted">حجم الصفحة</span>
                <button @click="setZoom(zoom + 0.5)" class="btn btn-soft size-12 text-lg" aria-label="تكبير">+</button>
            </div>
        </section>
    </div>
</div>
@endsection

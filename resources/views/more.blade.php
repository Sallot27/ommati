@extends('layouts.app')
@section('title', 'المزيد')

@section('content')
@php
$links = [
    [route('khatma'), 'book', 'ورد الختمة', 'اختمي القرآن بورد يومي'],
    [route('quotes'), 'quote', 'من كلام الأئمة', 'ابن تيمية وابن القيم والسعدي وغيرهم'],
    [route('names'), 'sun', 'أسماء الله الحسنى', 'مع آياتها وتفسيرها'],
    [route('duas'), 'heart', 'أدعية من القرآن', 'أدعية الأنبياء والصالحين'],
    [route('days'), 'star', 'الأيام الفاضلة', 'الصيام والسنن حسب التقويم الهجري'],
    [route('ayah.today'), 'book', 'آية اليوم', 'التفسير وكيف نعمل بها'],
    [route('hadith.today'), 'quote', 'حديث اليوم', 'الشرح والفوائد العملية'],
    [route('hadith.saved'), 'heart', 'الأحاديث المحفوظة', 'وأحاديث الأيام الماضية'],
    [route('library.index'), 'library', 'المكتبة', 'الكتب الدينية'],
    [route('listen'), 'headphones', 'التلاوات', 'استماع لكبار القراء'],
    [route('prayer'), 'compass', 'المواقيت والقبلة', 'مواقيت الصلاة واتجاه القبلة'],
    [route('tasbih'), 'beads', 'السبحة', 'عدّاد التسبيح'],
    [route('settings'), 'sliders', 'الإعدادات', 'حجم الخط، القارئ، التذكيرات'],
    [route('install'), 'phone', 'أضيفي التطبيق للشاشة', 'ليفتح كتطبيق عادي'],
];
@endphp
<ul class="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
    @foreach ($links as [$href, $icon, $title, $sub])
        <li>
            <a href="{{ $href }}" class="flex items-center gap-4 px-4 py-3.5 hover:bg-surface-2">
                <span class="grid size-11 place-items-center rounded-full bg-surface-2 text-teal"><x-icon :name="$icon" /></span>
                <span class="flex-1">
                    <span class="block text-lg font-medium">{{ $title }}</span>
                    <span class="text-sm text-muted">{{ $sub }}</span>
                </span>
                <x-icon name="forward" :size="18" class="text-muted" />
            </a>
        </li>
    @endforeach
</ul>
<p class="mt-6 text-center text-sm text-muted"><a href="{{ route('admin.dashboard') }}" class="underline">لوحة التحكم</a></p>
@endsection

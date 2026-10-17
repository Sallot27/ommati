@extends('layouts.app')
@section('title', 'إضافة التطبيق للشاشة')
@section('back', route('more'))

@section('content')
<div class="space-y-4">
    <p class="text-lg leading-relaxed">بعد الإضافة يظهر التطبيق بأيقونة على الشاشة الرئيسية ويفتح مثل أي تطبيق، وتصلك التذكيرات.</p>

    <section class="card px-5 py-5">
        <h2 class="text-xl font-semibold">على الآيفون (Safari)</h2>
        <ol class="mt-3 list-inside list-decimal space-y-2 text-lg leading-relaxed">
            <li>افتحي هذا الموقع في متصفح Safari.</li>
            <li>اضغطي زر المشاركة (المربع والسهم للأعلى) أسفل الشاشة.</li>
            <li>اختاري «إضافة إلى الشاشة الرئيسية».</li>
            <li>اضغطي «إضافة».</li>
            <li>افتحي التطبيق من الأيقونة الجديدة، ثم من الإعدادات فعّلي التذكيرات.</li>
        </ol>
    </section>

    <section class="card px-5 py-5">
        <h2 class="text-xl font-semibold">على أندرويد (Chrome)</h2>
        <ol class="mt-3 list-inside list-decimal space-y-2 text-lg leading-relaxed">
            <li>افتحي الموقع في Chrome.</li>
            <li>اضغطي النقاط الثلاث أعلى الشاشة.</li>
            <li>اختاري «إضافة إلى الشاشة الرئيسية» أو «تثبيت التطبيق».</li>
        </ol>
    </section>
</div>
@endsection

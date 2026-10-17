@extends('layouts.app')
@section('title', 'لا يوجد اتصال')

@section('content')
<div class="card px-5 py-10 text-center">
    <p class="text-xl font-semibold">لا يوجد اتصال بالإنترنت</p>
    <p class="mt-2 text-muted">الصفحات التي فتحتِها من قبل تعمل بدون إنترنت. تأكدي من الاتصال ثم أعيدي المحاولة.</p>
    <a href="/" class="btn btn-primary mt-5">إعادة المحاولة</a>
</div>
@endsection

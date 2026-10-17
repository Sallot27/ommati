@extends('layouts.admin')
@section('title', 'الدخول')
@section('content')
<form method="POST" action="{{ route('admin.login') }}" class="card mx-auto mt-10 max-w-sm space-y-4 px-6 py-6">
    @csrf
    <h1 class="text-xl font-semibold">دخول لوحة التحكم</h1>
    <label class="block">البريد
        <input name="email" type="email" value="{{ old('email') }}" required autofocus class="mt-1 w-full rounded-xl border border-line bg-surface-2 px-3 py-2.5" dir="ltr">
    </label>
    <label class="block">كلمة المرور
        <input name="password" type="password" required class="mt-1 w-full rounded-xl border border-line bg-surface-2 px-3 py-2.5" dir="ltr">
    </label>
    <button class="btn btn-primary w-full">دخول</button>
</form>
@endsection

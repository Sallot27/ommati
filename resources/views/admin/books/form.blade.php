@extends('layouts.admin')
@section('title', $book->exists ? 'تعديل كتاب' : 'رفع كتب')
@section('content')
<form method="POST" enctype="multipart/form-data"
      action="{{ $book->exists ? route('admin.books.update', $book) : route('admin.books.store') }}"
      class="card max-w-xl space-y-4 px-5 py-5">
    @csrf
    @if ($book->exists) @method('PUT') @endif
    <h1 class="text-xl font-semibold">{{ $book->exists ? 'تعديل كتاب' : 'رفع كتب' }}</h1>

    @unless ($book->exists)
        <label class="block">الملفات (PDF، EPUB… يمكن اختيار أكثر من ملف)
            <input type="file" name="files[]" multiple required accept=".pdf,.epub,.doc,.docx,.txt,.mp3,.m4a" class="mt-1 block w-full rounded-xl border border-line bg-surface-2 px-3 py-2.5">
        </label>
        <p class="text-sm text-muted">عند رفع أكثر من ملف يُؤخذ العنوان من اسم كل ملف.</p>
    @endunless

    <label class="block">العنوان
        <input name="title" value="{{ old('title', $book->title) }}" @if ($book->exists) required @endif class="mt-1 w-full rounded-xl border border-line bg-surface-2 px-3 py-2.5">
    </label>
    <label class="block">المؤلف
        <input name="author" value="{{ old('author', $book->author) }}" class="mt-1 w-full rounded-xl border border-line bg-surface-2 px-3 py-2.5">
    </label>
    <label class="block">التصنيف
        <input name="category" list="cats" value="{{ old('category', $book->category) }}" class="mt-1 w-full rounded-xl border border-line bg-surface-2 px-3 py-2.5">
        <datalist id="cats">@foreach ($categories as $c)<option value="{{ $c }}">@endforeach</datalist>
    </label>
    <label class="block">نبذة
        <textarea name="description" rows="3" class="mt-1 w-full rounded-xl border border-line bg-surface-2 px-3 py-2.5">{{ old('description', $book->description) }}</textarea>
    </label>
    <button class="btn btn-primary">{{ $book->exists ? 'حفظ' : 'رفع' }}</button>
</form>
@endsection

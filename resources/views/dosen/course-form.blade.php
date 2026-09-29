@extends('layouts.mahasiswa')
@section('header', 'Tambah course')
@section('content')
<div class="mx-auto max-w-3xl">
    <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500 mb-4">
        <a class="flex items-center gap-1.5 font-medium text-slate-500 hover:text-brand transition" href="{{ route('dosen.course.index') }}">
            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
            <span>Course Dosen</span>
        </a>
        <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-semibold text-slate-800" aria-current="page">
            Tambah Course
        </span>
    </nav>
    <h1 class="page-heading">Tambah course</h1>
    <p class="page-description">Siapkan identitas kelas. Materi dan tugas ditambahkan setelah course dibuat.</p>
<form method="post" enctype="multipart/form-data" action="{{ route('dosen.course.store') }}" class="surface mt-7 space-y-6 p-6 sm:p-8" data-course-form>@csrf
<div><label class="form-label" for="cover">Gambar sampul <span class="font-normal text-muted">(opsional)</span></label><div class="rounded-lg border border-dashed border-line bg-canvas p-5"><img data-cover-preview hidden alt="Pratinjau sampul" class="mb-4 h-40 w-full rounded-lg object-cover"><input class="field" id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp" data-cover-input><p class="mt-2 text-xs text-muted">JPG, PNG, atau WebP. Maksimal 5 MB. Gunakan gambar lebar, misalnya 1600 × 600 px.</p><button type="button" data-cover-remove class="quiet-link mt-3" hidden>Hapus gambar</button></div></div>
<div class="grid gap-5 sm:grid-cols-[140px_1fr]"><div><label class="form-label" for="code">Kode course</label><input required maxlength="20" class="field" name="code" id="code" value="{{ old('code') }}" placeholder="IF204"></div><div><label class="form-label" for="title">Nama course</label><input required maxlength="120" class="field" name="title" id="title" value="{{ old('title') }}" placeholder="Struktur Data dan Algoritma"></div></div>
<div><label class="form-label" for="lecturer">Dosen pengampu</label><input required class="field" name="lecturer" id="lecturer" value="{{ old('lecturer', 'Dr. Budi Santoso, M.Kom.') }}"></div>
<div><label class="form-label" for="description">Deskripsi singkat</label><textarea required maxlength="2000" class="field" rows="4" name="description" id="description">{{ old('description') }}</textarea></div>
<div class="space-y-4">
    <div>
        <label class="form-label" for="video">Tautan video pengantar (YouTube / Link Video) <span class="font-normal text-muted">(opsional)</span></label>
        <input class="field" type="url" name="video" id="video" value="{{ old('video') }}" placeholder="https://www.youtube.com/watch?v=...">
        <p class="mt-1 text-xs text-muted">Mendukung tautan YouTube biasa, youtu.be, Shorts, dan Live. Pastikan pemilik video mengizinkan penyematan.</p>
    </div>
    <div>
        <label class="form-label" for="video_file">Atau unggah berkas video MP4 <span class="font-normal text-muted">(opsional)</span></label>
        <input class="field" type="file" name="video_file" id="video_file" accept="video/mp4,video/webm" data-course-video-input>
        <p class="mt-1 text-xs text-muted">Format berkas .mp4 atau .webm (maks. 20 MB). Video lokal akan diputar menggunakan pemutar video HTML5.</p>
        <p class="mt-2 hidden text-xs font-medium text-ink" data-course-video-status aria-live="polite"></p>
    </div>
</div>
<div class="flex items-center justify-between pt-5"><a class="button-secondary" href="{{ route('dosen.course.index') }}">Batal</a><button class="button-primary" data-course-submit><span data-course-submit-label>Buat course</span></button></div><p class="text-xs text-muted">Course pratinjau tersimpan di sesi ini. Penetapan peserta oleh admin belum dihubungkan.</p></form></div>
@endsection

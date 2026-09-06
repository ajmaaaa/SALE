@extends('layouts.mahasiswa')
@section('title', 'Course | SALE')
@section('header', request()->is('dosen*') ? 'Course saya' : 'Course')
@section('content')
@php($role = request()->is('dosen*') ? 'dosen' : 'mahasiswa')
<div class="space-y-7">
    <header class="flex flex-wrap items-end justify-between gap-4"><div><h1 class="page-heading">{{ $role === 'dosen' ? 'Course saya' : 'Course' }}</h1><p class="page-description">{{ $role === 'dosen' ? 'Kelola materi dan pekerjaan kelas dalam satu tempat.' : 'Ruang belajar dari kelas yang ditetapkan program studi.' }}</p></div>@if($role === 'dosen')<a class="button-primary" href="{{ route('dosen.course.create') }}">+ Tambah course</a>@endif</header>
    <form class="flex gap-3" method="get"><label class="sr-only" for="search">Cari course</label><input class="field max-w-md" id="search" name="q" value="{{ request('q') }}" placeholder="Cari nama course, kode, atau dosen"><button class="button-secondary">Cari</button></form>
    <div class="grid gap-5 md:grid-cols-2 2xl:grid-cols-3">
    @forelse($courses as $course)
        <a href="{{ route($role.'.course.show', $course['id']) }}" class="overflow-hidden rounded-xl border border-line bg-white transition-colors hover:border-brand">
            @if($course['cover'])<img src="{{ route('preview.file', $course['cover']) }}" alt="Sampul {{ $course['title'] }}" class="h-36 w-full object-cover">@else<div class="flex h-28 items-center justify-between bg-brand-dark px-6 text-white"><span class="font-mono text-2xl tracking-wider">{{ $course['code'] }}</span><svg width="84" height="64" viewBox="0 0 84 64" fill="none" stroke="currentColor" class="opacity-30" aria-hidden="true"><path d="M42 9v18M16 48V27h52v21"/><circle cx="42" cy="8" r="6"/><circle cx="16" cy="53" r="6"/><circle cx="68" cy="53" r="6"/></svg></div>@endif
            <div class="p-6"><p class="text-xs font-semibold text-brand">{{ $course['code'] }} · Semester ganjil</p><h2 class="mt-2 text-lg font-semibold">{{ $course['title'] }}</h2><p class="mt-2 text-sm text-muted">{{ $course['lecturer'] }}</p><div class="mt-6 flex justify-between border-t border-line pt-4 text-sm"><span class="text-muted">{{ count(array_filter(\App\Support\LearningPreview::items(), fn($i) => $i['course'] === $course['id'])) }} konten kelas</span><span class="font-semibold text-brand">Buka course →</span></div></div>
        </a>
    @empty<div class="surface p-8"><h2 class="section-heading">Course tidak ditemukan</h2><p class="mt-2 text-muted">Coba kata pencarian lainnya.</p></div>@endforelse
    </div>
</div>
@endsection

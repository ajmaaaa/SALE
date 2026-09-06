@extends('layouts.mahasiswa')
@section('title', $course['title'].' | SALE')
@section('header', $course['title'])
@section('content')
@php($role = request()->is('dosen*') ? 'dosen' : 'mahasiswa')
<div class="space-y-7">
    <nav class="text-sm text-muted" aria-label="Breadcrumb"><a class="quiet-link" href="{{ route($role.'.course.index') }}">Course</a> / {{ $course['code'] }}</nav>
    @if($course['cover'])<img src="{{ route('preview.file', $course['cover']) }}" alt="Sampul course" class="h-44 w-full rounded-xl object-cover">@endif
    <header class="flex flex-wrap items-end justify-between gap-4"><div><p class="mb-2 text-sm font-semibold text-brand">{{ $course['code'] }} · Semester ganjil 2026/2027</p><h1 class="page-heading">{{ $course['title'] }}</h1><p class="page-description">{{ $course['description'] }}</p></div>@if($role === 'dosen')<a href="{{ route('dosen.item.create', $course['id']) }}" class="button-primary">+ Tambah konten</a>@endif</header>
    <div class="grid items-start gap-7 xl:grid-cols-[minmax(0,1fr)_280px]">
        <div class="min-w-0 space-y-6">
            @if($course['video'])<section class="surface p-5"><p class="eyebrow">Video pengantar course</p><h2 class="section-heading mt-2">Mulai dari pengantar dosen</h2><a class="button-secondary mt-4" href="{{ $course['video'] }}" target="_blank" rel="noopener noreferrer">Buka video ↗</a></section>@endif
            <div><h2 class="section-heading">Materi & pekerjaan kelas</h2><p class="mt-1 text-sm text-muted">Buka konten untuk melihat lampiran, instruksi, dan diskusinya.</p></div>
            @forelse(collect($items)->where('type', '!=', 'pengumuman')->groupBy('module') as $module => $contents)
                <section class="surface overflow-hidden"><div class="border-b border-line px-5 py-4"><h3 class="font-semibold">{{ $module }}</h3></div><div class="divide-y divide-line">
                @foreach($contents as $item)
                    <a href="{{ route('mahasiswa.course.item', [$course['id'], $item['id']]) }}" class="flex items-center gap-4 px-5 py-5 hover:bg-brand-soft/50"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $item['type'] === 'materi' ? 'bg-canvas text-muted' : 'bg-brand-soft text-brand' }} font-mono text-sm">{{ $item['type'] === 'coding' ? '</>' : ($item['type'] === 'materi' ? '≡' : '✓') }}</span><div class="min-w-0 flex-1"><span class="text-xs font-semibold {{ $item['type'] === 'materi' ? 'text-muted' : 'text-brand' }}">{{ \App\Support\LearningPreview::labels()[$item['type']] }}</span><h4 class="mt-1 font-medium">{{ $item['title'] }}</h4><p class="mt-1 text-xs text-muted">{{ $item['due'] ? 'Tenggat '.\Carbon\Carbon::parse($item['due'])->translatedFormat('d M Y, H:i') : 'Materi belajar' }} · {{ count(session('learning.discussions.'.$item['id'], [])) }} diskusi</p></div><span aria-hidden="true" class="text-muted">→</span></a>
                @endforeach
                </div></section>
            @empty<section class="surface p-8"><h3 class="section-heading">Modul pertama dimulai di sini</h3><p class="mt-2 text-sm text-muted">{{ $role === 'dosen' ? 'Tambahkan materi, tugas, atau kuis untuk kelas ini.' : 'Dosen belum membagikan materi untuk kelas ini.' }}</p></section>@endforelse
        </div>
        <aside class="space-y-5">
            <section class="surface p-5"><p class="eyebrow">Pengampu course</p><h2 class="mt-3 font-semibold">{{ $course['lecturer'] }}</h2><p class="mt-2 text-sm text-muted">Diskusikan pertanyaan melalui materi atau tugas terkait.</p></section>
            <section class="surface p-5"><h2 class="font-semibold">Pengumuman</h2>@forelse(collect($items)->where('type','pengumuman') as $announcement)<a href="{{ route('mahasiswa.course.item', [$course['id'], $announcement['id']]) }}" class="mt-4 block"><h3 class="text-sm font-semibold text-brand">{{ $announcement['title'] }}</h3><p class="mt-2 text-sm leading-6 text-muted">{{ \Illuminate\Support\Str::limit($announcement['body'], 140) }}</p></a>@empty<p class="mt-3 text-sm text-muted">Belum ada pengumuman.</p>@endforelse</section>
            <details class="surface p-5"><summary class="cursor-pointer font-semibold">RPS & capaian pembelajaran</summary><p class="mt-3 text-sm text-muted">Capaian dicantumkan pada setiap materi dan tugas. Dokumen RPS dapat ditambahkan dosen sebagai materi pada modul Informasi kelas.</p></details>
        </aside>
    </div>
</div>
@endsection

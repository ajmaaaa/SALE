@extends('layouts.mahasiswa')

@section('title', 'Course | SALE')
@section('header', 'Course')

@section('content')
<div class="space-y-7">
    <header class="flex flex-wrap items-center justify-between gap-4 pb-2"><div>
        <h1 class="page-heading">Course</h1>
        <p class="page-description">Kelas aktif yang telah ditetapkan oleh program studi pada semester ini.</p>
        </div>@if(request()->is('dosen*'))<a class="button-primary" href="{{ route('dosen.course.create') }}">+ Tambah course</a>@endif
    </header>

    <form class="flex flex-col gap-3 sm:flex-row" action="{{ route(request()->is('dosen*') ? 'dosen.course.index' : 'mahasiswa.course.index') }}" method="GET">
        <label class="sr-only" for="course-search">Cari course</label>
        <input id="course-search" name="q" type="search" class="field sm:max-w-md" placeholder="Cari judul, kode, atau dosen" value="{{ request('q') }}">
        <button type="submit" class="button-secondary">Terapkan</button>
    </form>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" aria-label="Daftar course">
        @forelse ($courses as $course)
            @php
                $contents = collect(\App\Support\LearningPreview::items())->where('course', $course['id']);
                $next = $contents->whereIn('type',['tugas','coding','kuis'])->sortBy('due')->first();
                $course += ['sks'=>'3 SKS', 'modules'=>$contents->where('type','!=','pengumuman')->pluck('module')->unique()->count().' modul', 'tasks'=>$contents->whereIn('type',['tugas','coding','kuis'])->count().' pekerjaan', 'type'=>$next ? \App\Support\LearningPreview::labels()[$next['type']] : 'Materi kelas', 'work'=>$next['title'] ?? 'Belum ada tugas aktif', 'due'=>!empty($next['due']) ? \Carbon\Carbon::parse($next['due'])->translatedFormat('d M, H:i') : ''];
            @endphp

            <a href="{{ route((request()->is('dosen*') ? 'dosen' : 'mahasiswa').'.course.show', $course['id']) }}" class="group flex min-h-64 flex-col overflow-hidden rounded-xl bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                @if($course['cover'])<img src="{{ route('preview.file',$course['cover']) }}" alt="Sampul {{ $course['title'] }}" class="h-36 w-full object-cover"><div class="px-5 pt-4"><p class="text-xs font-semibold text-brand">{{ $course['code'] }}</p><h2 class="mt-2 text-lg font-semibold">{{ $course['title'] }}</h2><p class="mt-1 text-xs text-muted">{{ $course['lecturer'] }}</p></div>@else
                <div class="relative min-h-32 overflow-hidden bg-brand-dark px-5 py-5 text-white">
                    @if($course['id'] === 1)
                        <svg class="absolute -right-3 -top-3 h-36 w-36 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="60" cy="22" r="10"/><circle cx="31" cy="64" r="10"/><circle cx="89" cy="64" r="10"/><circle cx="17" cy="101" r="8"/><circle cx="47" cy="101" r="8"/><circle cx="75" cy="101" r="8"/><circle cx="104" cy="101" r="8"/><path d="M54 30 36 55M66 30l18 25M27 74l-7 19M35 74l9 19M85 74l-8 19M93 74l8 19"/></svg>
                    @elseif($course['id'] === 2)
                        <svg class="absolute -right-3 -top-2 h-36 w-36 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="13" y="17" width="94" height="74" rx="7"/><path d="M13 35h94M27 26h1M36 26h1M45 26h1M76 51 54 74l14 3 5 15 10-4-6-14 14-4z"/></svg>
                    @elseif($course['id'] === 3)
                        <svg class="absolute -right-3 -top-3 h-36 w-36 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="60" cy="60" r="13"/><circle cx="22" cy="28" r="8"/><circle cx="98" cy="26" r="8"/><circle cx="18" cy="93" r="8"/><circle cx="101" cy="94" r="8"/><path d="m29 34 21 18M91 32 70 52M26 88l24-19M94 88 70 69"/></svg>
                    @else
                        <svg class="absolute -right-2 -top-2 h-36 w-36 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="15" y="20" width="34" height="22" rx="4"/><rect x="70" y="20" width="34" height="22" rx="4"/><rect x="43" y="79" width="34" height="22" rx="4"/><path d="M49 31h21M32 42v24h28v13M87 42v24H60"/></svg>
                    @endif
                    <div class="relative z-10"><div class="flex items-center gap-3 text-xs font-semibold"><span>{{ $course['code'] }}</span><span>{{ $course['sks'] }}</span></div>
                    <h2 class="mt-3 text-xl font-semibold leading-7 text-white">{{ $course['title'] }}</h2>
                    <p class="mt-1 text-xs text-white">{{ $course['lecturer'] }}</p></div>
                </div>
                @endif
                <div class="flex flex-1 flex-col px-5 py-4">
                    <p class="text-xs font-semibold text-brand">{{ $course['type'] }}</p>
                    <p class="mt-1 text-sm font-medium text-ink">{{ $course['work'] }}</p>
                    @if($course['due'])<p class="mt-2 text-xs font-medium {{ $loop->first ? 'text-danger' : 'text-muted' }}">{{ $course['due'] }}</p>@endif
                    <div class="mt-auto flex gap-4 pt-5 text-xs font-medium text-muted"><span>{{ $course['modules'] }}</span><span>{{ $course['tasks'] }}</span></div>
                </div>
            </a>
        @empty<p class="p-6 text-sm text-muted">Course tidak ditemukan. Coba kata pencarian lainnya.</p>@endforelse
    </section>
</div>
@endsection

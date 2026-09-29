@extends('layouts.mahasiswa')

@section('title', 'Course | SALE')
@section('header', 'Course')

@section('content')
<div class="space-y-7">
    <header class="flex flex-wrap items-center justify-between gap-4 pb-2">
        <div>
            <h1 class="page-heading">Course</h1>
            <p class="page-description">Kelas aktif yang telah ditetapkan oleh program studi pada semester ini.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="document.getElementById('join-class-modal').showModal()" class="button-secondary text-xs font-semibold">
                + Gabung Kelas
            </button>
        </div>
    </header>

    <form class="flex flex-col gap-3 sm:flex-row sm:items-center" action="{{ route(request()->is('dosen*') ? 'dosen.course.index' : 'mahasiswa.course.index') }}" method="GET">
        <label class="sr-only" for="course-search">Cari course</label>
        <input id="course-search" name="q" type="search" class="field sm:max-w-md" placeholder="Cari judul, kode, atau dosen" value="{{ request('q') }}">

        @if(!empty($semesters) && $semesters->count())
            <label class="sr-only" for="course-semester">Semester</label>
            <select id="course-semester" name="semester" onchange="this.form.submit()" class="field sm:w-56 text-xs font-semibold">
                <option value="">Semua Semester</option>
                @foreach($semesters as $sem)
                    <option value="{{ $sem->id }}" {{ (string) request('semester', $selectedSemesterId ?? '') === (string) $sem->id ? 'selected' : '' }}>
                        {{ $sem->display_name }} {{ $sem->is_active ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        @endif

        <button type="submit" class="button-secondary">Terapkan</button>
        @if(request()->filled('q') || request()->filled('semester'))
            <a href="{{ route(request()->is('dosen*') ? 'dosen.course.index' : 'mahasiswa.course.index') }}" class="button-secondary">Reset</a>
        @endif
    </form>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" aria-label="Daftar course">
        @forelse ($courses as $course)
            @include('learning.partials.course-card', ['course' => $course, 'role' => request()->is('dosen*') ? 'dosen' : 'mahasiswa', 'isFirst' => $loop->first])
        @empty
            <div class="col-span-full py-2 text-xs text-muted">
                {{ request()->filled('q') ? 'Kelas tidak ditemukan.' : 'Belum ada kelas.' }}
            </div>
        @endforelse
    </section>

    @include('learning.partials.join-class-dialog', ['joinAsDosen' => request()->is('dosen*')])
</div>
@endsection

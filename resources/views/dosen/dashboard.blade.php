@extends('layouts.mahasiswa')

@section('title', 'Dashboard Dosen | SALE')
@section('header', 'Dashboard Dosen')

@section('content')
<div class="space-y-8">
    <header class="flex flex-col gap-4 pb-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-semibold text-brand">{{ now()->translatedFormat('l, d F Y') }}</p>
            <h1 class="page-heading">Selamat datang, Budi Santoso.</h1>
            <p class="page-description">Ringkasan perkuliahan dan status penilaian kelas Anda.</p>
        </div>
    </header>

    {{-- Simple Dashboard: Only 2 Main Stat Cards --}}
    @php
        $allCourses = \App\Support\LearningPreview::courses();
        $totalCourses = count($allCourses);
        
        $classesList = [
            ['data_status' => 'belum_selesai'],
            ['data_status' => 'selesai'],
            ['data_status' => 'belum_selesai'],
            ['data_status' => 'belum_selesai'],
            ['data_status' => 'selesai'],
            ['data_status' => 'belum_selesai'],
        ];
        $pendingCount = count(array_filter($classesList, fn($c) => $c['data_status'] === 'belum_selesai'));
    @endphp

    {{-- Stat Overview Section: High Contrast, Repetition, Proximity --}}
    <div class="grid gap-6 sm:grid-cols-2">
        {{-- Card 1: Jumlah Course --}}
        <div class="surface p-6 rounded-2xl flex items-center justify-between gap-5 border border-line/70 shadow-sm hover:shadow-md transition">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-muted">Jumlah Course (Matkul)</p>
                <p class="mt-1 text-2xl font-extrabold text-ink">{{ $totalCourses }} <span class="text-xs font-normal text-muted">Matkul Aktif</span></p>
            </div>
            <a href="{{ route('dosen.course.index') }}" class="button-secondary text-xs px-3.5 py-2 font-semibold shrink-0">
                Kelola Matkul
            </a>
        </div>

        {{-- Card 2: Jumlah Penilaian Belum Dinilai --}}
        <div class="surface p-6 rounded-2xl flex items-center justify-between gap-5 border border-line/70 shadow-sm hover:shadow-md transition">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-muted">Jumlah Penilaian Belum Dinilai</p>
                <p class="mt-1 text-2xl font-extrabold text-ink">{{ $pendingCount }} <span class="text-xs font-normal text-muted">Kelas Belum Dinilai</span></p>
            </div>
            <a href="{{ route('dosen.grades') }}" class="button-secondary text-xs px-3.5 py-2 font-semibold shrink-0">
                Lihat Penilaian
            </a>
        </div>
    </div>

    {{-- Course List Section: Strong Alignment & Card Repetition --}}
    <section class="surface p-6 rounded-2xl border border-line/70 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-line/50 pb-4">
            <div>
                <h2 class="text-lg font-bold text-ink">Daftar Matkul Diampu</h2>
                <p class="mt-0.5 text-xs text-muted">Seluruh kuis, tugas, dan rekap nilai berada di dalam matkul masing-masing.</p>
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($allCourses as $course)
                @php
                    $currentSec = 'A';
                    $contents = collect(\App\Support\LearningPreview::items())
                        ->where('course', $course['id'])
                        ->filter(fn($i) => ($i['section'] ?? 'A') === $currentSec || ($i['section'] ?? 'A') === 'ALL');

                    $next = $contents->whereIn('type',['tugas','coding','kuis'])->sortBy('due')->first();
                    $courseData = $course + [
                        'sks'=>'3 SKS', 
                        'modules'=>$contents->where('type','!=','pengumuman')->pluck('module')->unique()->count().' modul', 
                        'tasks'=>$contents->whereIn('type',['tugas','coding','kuis'])->count().' pekerjaan', 
                        'type'=>$next ? \App\Support\LearningPreview::labels()[$next['type']] : 'Materi kelas', 
                        'work'=>$next['title'] ?? 'Belum ada tugas aktif', 
                        'due'=>!empty($next['due']) ? \Carbon\Carbon::parse($next['due'])->translatedFormat('d M, H:i') : ''
                    ];
                @endphp

                <div class="group flex flex-col overflow-hidden rounded-xl bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md border border-line/60">
                    @if(!empty($courseData['cover']))
                        <a href="{{ route('dosen.course.show', ['course' => $courseData['id'], 'section' => $currentSec]) }}" class="block">
                            <img src="{{ route('preview.file',$courseData['cover']) }}" alt="Sampul {{ $courseData['title'] }}" class="h-36 w-full object-cover">
                            <div class="px-5 pt-4">
                                <p class="text-xs font-semibold text-brand">{{ $courseData['code'] }}</p>
                                <h2 class="mt-2 text-lg font-semibold text-ink group-hover:text-brand transition">{{ $courseData['title'] }}</h2>
                                <p class="mt-1 text-xs text-muted">{{ $courseData['lecturer'] }}</p>
                            </div>
                        </a>
                    @else
                        <a href="{{ route('dosen.course.show', ['course' => $courseData['id'], 'section' => $currentSec]) }}" class="relative min-h-32 overflow-hidden bg-brand-dark px-5 py-5 text-white block">
                            @if($courseData['id'] === 1)
                                <svg class="absolute -right-3 -top-3 h-36 w-36 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="60" cy="22" r="10"/><circle cx="31" cy="64" r="10"/><circle cx="89" cy="64" r="10"/><circle cx="17" cy="101" r="8"/><circle cx="47" cy="101" r="8"/><circle cx="75" cy="101" r="8"/><circle cx="104" cy="101" r="8"/><path d="M54 30 36 55M66 30l18 25M27 74l-7 19M35 74l9 19M85 74l-8 19M93 74l8 19"/></svg>
                            @elseif($courseData['id'] === 2)
                                <svg class="absolute -right-3 -top-2 h-36 w-36 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="13" y="17" width="94" height="74" rx="7"/><path d="M13 35h94M27 26h1M36 26h1M45 26h1M76 51 54 74l14 3 5 15 10-4-6-14 14-4z"/></svg>
                            @elseif($courseData['id'] === 3)
                                <svg class="absolute -right-3 -top-3 h-36 w-36 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="60" cy="60" r="13"/><circle cx="22" cy="28" r="8"/><circle cx="98" cy="26" r="8"/><circle cx="18" cy="93" r="8"/><circle cx="101" cy="94" r="8"/><path d="m29 34 21 18M91 32 70 52M26 88l24-19M94 88 70 69"/></svg>
                            @else
                                <svg class="absolute -right-2 -top-2 h-36 w-36 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="15" y="20" width="34" height="22" rx="4"/><rect x="70" y="20" width="34" height="22" rx="4"/><rect x="43" y="79" width="34" height="22" rx="4"/><path d="M49 31h21M32 42v24h28v13M87 42v24H60"/></svg>
                            @endif
                            <div class="relative z-10">
                                <div class="flex items-center gap-3 text-xs font-semibold">
                                    <span>{{ $courseData['code'] }}</span>
                                    <span>·</span>
                                    <span>{{ $courseData['sks'] }}</span>
                                </div>
                                <h3 class="mt-3 text-lg font-semibold leading-6 text-white group-hover:underline line-clamp-2">{{ $courseData['title'] }}</h3>
                                <p class="mt-1 text-xs text-white/80 line-clamp-1">{{ $courseData['lecturer'] }}</p>
                            </div>
                        </a>
                    @endif

                    <a href="{{ route('dosen.course.show', ['course' => $courseData['id'], 'section' => $currentSec]) }}" class="flex flex-1 flex-col px-5 py-4">
                        <p class="text-xs font-semibold text-brand">{{ $courseData['type'] }}</p>
                        <p class="mt-1 text-sm font-medium text-ink line-clamp-2">{{ $courseData['work'] }}</p>
                        @if(!empty($courseData['due']))
                            <p class="mt-2 text-xs font-medium text-danger">{{ $courseData['due'] }}</p>
                        @endif
                        <div class="mt-auto flex gap-4 pt-4 text-xs font-medium text-muted">
                            <span>{{ $courseData['modules'] }}</span>
                            <span>{{ $courseData['tasks'] }}</span>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

    </section>
</div>
@endsection

@extends('layouts.mahasiswa')

@section('title', 'Nilai | SALE')
@section('header', 'Nilai')

@section('content')
<div class="space-y-7">
    <header>
        <h1 class="page-heading">Nilai</h1>
        <p class="page-description">Lihat nilai sementara dan rincian setiap komponen penilaian per mata kuliah.</p>
    </header>

    <form class="flex flex-col gap-3 sm:flex-row" action="{{ route('mahasiswa.grade.index') }}" method="GET">
        <label class="sr-only" for="grade-search">Cari mata kuliah</label><input id="grade-search" name="q" type="search" class="field sm:max-w-md" placeholder="Cari mata kuliah atau kode">
        <label class="sr-only" for="grade-period">Pilih semester</label><select id="grade-period" name="period" class="field sm:w-56"><option>Semester Ganjil 2026/2027</option><option>Semester Genap 2025/2026</option></select>
        <button type="submit" class="button-secondary">Terapkan</button>
    </form>

    <section class="grid items-start gap-4 xl:grid-cols-2" aria-label="Nilai per mata kuliah">
        @foreach ([
            ['code' => 'IF204', 'title' => 'Struktur Data dan Algoritma', 'score' => '88,5', 'letter' => 'A', 'items' => [['Kuis Struktur Data Dasar', 'Kuis', '10%', '92'], ['Praktikum Linked List', 'Tugas', '15%', '86'], ['Praktikum Binary Tree', 'Tugas', '20%', 'Belum dinilai'], ['Ujian Tengah Semester', 'Ujian', '25%', '88']]],
            ['code' => 'IF218', 'title' => 'Interaksi Manusia dan Komputer', 'score' => '86,0', 'letter' => 'A', 'items' => [['Analisis Antarmuka', 'Tugas', '15%', '84'], ['Studi Kasus Usability', 'Tugas', '20%', '86'], ['Kuis Prinsip Desain', 'Kuis', '10%', '90'], ['Ujian Tengah Semester', 'Ujian', '25%', 'Belum dinilai']]],
            ['code' => 'IF221', 'title' => 'Kecerdasan Buatan Terapan', 'score' => '88,0', 'letter' => 'A', 'items' => [['Praktikum Klasifikasi', 'Tugas', '20%', '88'], ['Kuis Evaluasi Model', 'Kuis', '10%', 'Belum dinilai'], ['Proyek Model Prediktif', 'Proyek', '25%', '90']]],
            ['code' => 'IF230', 'title' => 'Rekayasa Perangkat Lunak', 'score' => '84,5', 'letter' => 'A-', 'items' => [['Dokumen Kebutuhan', 'Tugas', '20%', '85'], ['Kuis UML', 'Kuis', '10%', '88'], ['Proyek Kelompok', 'Proyek', '30%', 'Belum dinilai']]],
        ] as $grade)
            <details class="group overflow-hidden rounded-xl bg-white shadow-sm" @if($loop->first) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-5">
                    <div><p class="text-xs font-semibold text-brand">{{ $grade['code'] }}</p><h2 class="mt-1 font-semibold text-ink">{{ $grade['title'] }}</h2></div>
                    <div class="flex items-center gap-5"><span class="text-right"><span class="block text-xs text-muted">Nilai sementara</span><span class="text-xl font-semibold text-ink">{{ $grade['score'] }}</span><span class="ml-1 text-sm font-semibold text-brand">{{ $grade['letter'] }}</span></span><svg class="h-4 w-4 text-muted transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></div>
                </summary>
                <div class="bg-[#f3f6f9] px-5 py-4">
                    <div class="grid grid-cols-[minmax(0,1fr)_70px_90px] gap-3 pb-2 text-xs font-semibold text-muted"><span>Komponen</span><span class="text-right">Bobot</span><span class="text-right">Nilai</span></div>
                    @foreach ($grade['items'] as $item)
                        <div class="grid grid-cols-[minmax(0,1fr)_70px_90px] gap-3 py-2.5 text-sm"><span><span class="font-medium text-ink">{{ $item[0] }}</span><span class="ml-2 text-xs text-muted">{{ $item[1] }}</span></span><span class="text-right text-muted">{{ $item[2] }}</span><span class="text-right font-semibold text-ink">{{ $item[3] }}</span></div>
                    @endforeach
                </div>
            </details>
        @endforeach
    </section>
</div>
@endsection

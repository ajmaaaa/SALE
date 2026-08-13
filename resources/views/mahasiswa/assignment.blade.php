@extends('layouts.mahasiswa')

@section('title', 'Tugas & Kuis - Lumina Academy')

@section('content')
<div class="space-y-6">

    <!-- Top Navigation Tabs & Search Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Sub Tabs (Assignments / Grades) -->
        <div class="flex items-center gap-6 text-sm font-semibold border-b md:border-b-0 border-slate-200 pb-2 md:pb-0">
            <a href="/mahasiswa/assignment" class="text-indigo-600 border-b-2 border-indigo-600 pb-1">Assignments</a>
            <a href="/mahasiswa/grade" class="text-slate-500 hover:text-slate-700 pb-1 transition-colors">Grades</a>
        </div>

        <!-- Search Bar & Header Icons -->
        <div class="flex items-center gap-4">
            <div class="relative w-64 md:w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" 
                       placeholder="Cari mata kuliah..." 
                       class="w-full pl-9 pr-4 py-2 bg-slate-100/80 border border-transparent rounded-full text-xs text-slate-700 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-300 focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>

            <div class="flex items-center gap-3">
                <button class="w-8 h-8 flex items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 text-sm">
                    <i class="fa-regular fa-moon"></i>
                </button>
                <button class="w-8 h-8 flex items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 text-sm">
                    <i class="fa-regular fa-bell"></i>
                </button>
                <button class="w-8 h-8 flex items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 text-sm">
                    <i class="fa-regular fa-star"></i>
                </button>
                <div class="w-8 h-8 rounded-full overflow-hidden border border-slate-200">
                    <img src="https://ui-avatars.com/api/?name=Mahasiswa&background=0D8ABC&color=fff" alt="User" class="w-full h-full object-cover">
                </div>
            </div>
        </div>
    </div>

    <!-- Title & Filter Pill Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Tugas & Kuis</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola dan pantau tenggat waktu akademik Anda.</p>
        </div>
        
        <!-- Filter Pills -->
        <div class="flex items-center gap-2">
            <button class="px-4 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                Semua
            </button>
            <button class="px-4 py-1.5 rounded-full text-xs font-semibold bg-indigo-600 text-white shadow-sm">
                Aktif
            </button>
            <button class="px-4 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                Selesai
            </button>
        </div>
    </div>

    <!-- Grid Layout Utama (8 Cols Left, 4 Cols Right) -->
    <div class="grid grid-cols-12 gap-6 pt-2">

        <!-- Left Column: Lista Tugas (8 Cols) -->
        <div class="col-span-12 lg:col-span-8 space-y-4">

            <!-- Assignment Card 1: Urgent (Wajib + Timer) -->
            <div class="bg-gradient-to-r from-white via-white to-rose-50/30 rounded-2xl border border-slate-200 p-6 shadow-sm relative overflow-hidden flex flex-col justify-between gap-6 hover:shadow-md transition-shadow">
                <!-- Red Left Accent Line -->
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-rose-500"></div>

                <div class="space-y-3 pl-2">
                    <!-- Badges -->
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 bg-slate-100 text-slate-600 text-[11px] font-bold rounded">Wajib</span>
                        <span class="px-2.5 py-0.5 bg-rose-100 text-rose-600 text-[11px] font-bold rounded flex items-center gap-1">
                            <i class="fa-regular fa-clock"></i> 12 Jam Lagi
                        </span>
                    </div>

                    <!-- Title & Details -->
                    <div class="space-y-1">
                        <h2 class="text-lg font-bold text-slate-900 leading-snug">Makalah Analisis Algoritma Lanjut</h2>
                        <p class="text-xs font-medium text-slate-600">Mata Kuliah: Struktur Data (CS201)</p>
                        <p class="text-xs text-slate-500 pt-1">Unggah dalam format PDF. Minimal 5 halaman.</p>
                    </div>
                </div>

                <!-- Footer Status & Action Button -->
                <div class="flex items-center justify-between pt-2 border-t border-slate-100 pl-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 text-slate-500 text-xs font-medium rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Belum Dikerjakan
                    </span>
                    <a href="#" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-xl shadow-md shadow-indigo-100 transition-colors">
                        Kerjakan Sekarang
                    </a>
                </div>
            </div>

            <!-- Assignment Card 2: Regular Quiz -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between gap-6 hover:shadow-md transition-shadow">
                <div class="space-y-3">
                    <!-- Badges -->
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 bg-slate-100 text-slate-600 text-[11px] font-bold rounded">Kuis</span>
                        <span class="px-2.5 py-0.5 bg-slate-100 text-slate-500 text-[11px] font-medium rounded flex items-center gap-1">
                            <i class="fa-regular fa-calendar"></i> 15 Nov 2024
                        </span>
                    </div>

                    <!-- Title & Details -->
                    <div class="space-y-1">
                        <h2 class="text-lg font-bold text-slate-900 leading-snug">Kuis 3: Jaringan Komputer</h2>
                        <p class="text-xs font-medium text-slate-600">Mata Kuliah: Pengantar Jaringan (NET101)</p>
                        <p class="text-xs text-slate-500 pt-1">Durasi: 45 Menit. Soal Pilihan Ganda.</p>
                    </div>
                </div>

                <!-- Footer Status & Action Button -->
                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 text-slate-500 text-xs font-medium rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Belum Dikerjakan
                    </span>
                    <a href="#" class="px-5 py-2.5 bg-white border border-indigo-600 text-indigo-600 hover:bg-indigo-50 font-medium text-xs rounded-xl transition-colors">
                        Lihat Detail
                    </a>
                </div>
            </div>

        </div>

        <!-- Right Column: Progres & Stats & Riwayat Nilai (4 Cols) -->
        <div class="col-span-12 lg:col-span-4 space-y-5">

            <!-- Banner Progres Semester -->
            <div class="bg-gradient-to-br from-indigo-900 via-indigo-700 to-indigo-600 p-5 rounded-2xl text-white shadow-lg shadow-indigo-100 space-y-4">
                <h3 class="font-bold text-sm text-indigo-100">Progres Semester</h3>
                
                <div class="flex items-baseline justify-between">
                    <span class="text-4xl font-extrabold tracking-tight">85%</span>
                    <span class="text-xs text-indigo-200">Tugas Selesai</span>
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-indigo-950/40 h-2.5 rounded-full overflow-hidden p-0.5">
                    <div class="bg-emerald-400 h-full w-[85%] rounded-full"></div>
                </div>
            </div>

            <!-- Stats Count Cards -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Perlu Dikerjakan -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-1">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center text-sm mb-2">
                        <i class="fa-regular fa-clipboard"></i>
                    </div>
                    <span class="text-2xl font-extrabold text-slate-900">3</span>
                    <p class="text-[11px] text-slate-400 leading-tight">Perlu Dikerjakan</p>
                </div>

                <!-- Diselesaikan (Bulan ini) -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-1">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-500 flex items-center justify-center text-sm mb-2">
                        <i class="fa-regular fa-circle-check"></i>
                    </div>
                    <span class="text-2xl font-extrabold text-slate-900">12</span>
                    <p class="text-[11px] text-slate-400 leading-tight">Diselesaikan (Bulan ini)</p>
                </div>
            </div>

            <!-- Widget Riwayat Nilai Terbaru -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-sm">Riwayat Nilai Terbaru</h3>
                    <a href="/mahasiswa/grade" class="text-[11px] font-semibold text-indigo-600 hover:underline">Lihat Semua</a>
                </div>

                <div class="space-y-3">
                    <!-- Grade Item 1 -->
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <div>
                                <h5 class="text-xs font-bold text-slate-800">Quiz 1: UX Fundamentals</h5>
                                <p class="text-[10px] text-slate-400">DES202 - Interaction Design</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-extrabold text-emerald-600">92</span>
                            <span class="text-[10px] text-slate-400 block -mt-1">/100</span>
                        </div>
                    </div>

                    <!-- Grade Item 2 -->
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <div>
                                <h5 class="text-xs font-bold text-slate-800">Essay: Market Analysis</h5>
                                <p class="text-[10px] text-slate-400">MGT204 - Business Ethics</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-extrabold text-emerald-600">85</span>
                            <span class="text-[10px] text-slate-400 block -mt-1">/100</span>
                        </div>
                    </div>

                    <!-- Grade Item 3 (AI Graded) -->
                    <div class="flex items-center justify-between p-3 rounded-xl bg-indigo-50/50 border border-indigo-100">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-code"></i>
                            </div>
                            <div>
                                <h5 class="text-xs font-bold text-slate-800">Programming Lab 3</h5>
                                <p class="text-[10px] text-slate-400">CS301 - Data Structures</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-extrabold text-slate-800">88</span>
                            <span class="text-[9px] font-bold bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded block mt-0.5">AI Graded</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
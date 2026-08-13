@extends('layouts.mahasiswa')

@section('title', 'Algoritma & Struktur Data Lanjut - Lumina Academy')

@section('content')
<div class="space-y-6">

    <!-- Top Navigation & Search Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500">
            <a href="/mahasiswa/course" class="hover:text-indigo-600 transition-colors">Courses</a>
            <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
            <span class="font-semibold text-slate-800">Pemrograman Web Lanjut</span>
        </nav>

        <!-- Search Bar & Header Icons -->
        <div class="flex items-center gap-4">
            <div class="relative w-64 md:w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" 
                       placeholder="Cari mata kuliah..." 
                       class="w-full pl-9 pr-4 py-2 bg-slate-100 border border-transparent rounded-full text-xs text-slate-700 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-300 transition-all">
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

    <!-- Grid Content Utama -->
    <div class="grid grid-cols-12 gap-6">

        <!-- Kolom Kiri: Video, Info Course, Modul (8 Cols) -->
        <div class="col-span-12 lg:col-span-8 space-y-6">

            <!-- Video Player Banner -->
            <div class="relative rounded-2xl overflow-hidden aspect-video bg-slate-900 shadow-md group">
                <img src="https://images.unsplash.com/photo-1524178232363-1fb2b075b655?q=80&w=1000&auto=format&fit=crop" 
                     alt="Video Thumbnail" 
                     class="w-full h-full object-cover opacity-80">
                <div class="absolute inset-0 bg-black/20 flex items-center justify-center">
                    <button class="w-16 h-16 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full flex items-center justify-center shadow-lg transition-transform group-hover:scale-110">
                        <i class="fa-solid fa-play text-xl ml-1"></i>
                    </button>
                </div>
            </div>

            <!-- Course Header Info -->
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-700 text-[11px] font-bold rounded">CS301</span>
                    <span class="px-2.5 py-0.5 bg-amber-100 text-amber-800 text-[11px] font-bold rounded">Wajib</span>
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Algoritma & Struktur Data Lanjut</h1>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Pelajari implementasi graf, tree lanjutan, dan analisis kompleksitas algoritma untuk menyelesaikan masalah komputasi kompleks.
                </p>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200">
                <div class="flex gap-6 text-sm font-semibold">
                    <button class="pb-3 border-b-2 border-indigo-600 text-indigo-600">Modul</button>
                    <button class="pb-3 text-slate-500 hover:text-slate-700 flex items-center gap-1.5">
                        Pengumuman <span class="w-4 h-4 bg-rose-500 text-white text-[10px] rounded-full flex items-center justify-center">2</span>
                    </button>
                    <button class="pb-3 text-slate-500 hover:text-slate-700">Tentang</button>
                </div>
            </div>

            <!-- Modul Accordion List -->
            <div class="space-y-4">

                <!-- Modul 1 (Active / Expanded) -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <!-- Header Modul 1 -->
                    <div class="p-4 bg-indigo-50/50 flex items-center justify-between cursor-pointer">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-sm">
                                1
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 text-sm">Pengenalan Graf & Representasinya</h4>
                                <p class="text-[11px] text-slate-500">Minggu 1 • Selesai</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-up text-xs text-slate-400"></i>
                    </div>

                    <!-- Content Item Modul 1 -->
                    <div class="p-4 space-y-3 border-t border-slate-100">
                        <!-- Item 1: Slide -->
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-rose-100 text-rose-600 rounded-lg text-sm">
                                    <i class="fa-regular fa-file-pdf"></i>
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-800">Slide Kuliah: Representasi Graf.pdf</h5>
                                    <p class="text-[10px] text-slate-400">Materi • 2.4 MB</p>
                                </div>
                            </div>
                            <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                        </div>

                        <!-- Item 2: Video Sesi Sinkron -->
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-indigo-100 text-indigo-600 rounded-lg text-sm">
                                    <i class="fa-solid fa-video"></i>
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-800">Rekaman Sesi Sinkron</h5>
                                    <p class="text-[10px] text-slate-400">Materi • 45 Menit</p>
                                </div>
                            </div>
                            <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                        </div>

                        <!-- Item 3: Tugas -->
                        <div class="flex items-center justify-between p-3 rounded-xl bg-indigo-50/60 border border-indigo-100">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-amber-100 text-amber-600 rounded-lg text-sm">
                                    <i class="fa-regular fa-clipboard"></i>
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-800">Tugas Praktikum 1: Adjacency Matrix</h5>
                                    <p class="text-[10px] text-rose-500 font-semibold">Tenggat: 12 Okt 2024</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-emerald-600 bg-emerald-100 px-2.5 py-1 rounded-md">95/100</span>
                        </div>
                    </div>
                </div>

                <!-- Modul 2 (Collapsed) -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full border-2 border-indigo-600 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                2
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 text-sm">Algoritma Pencarian (BFS & DFS)</h4>
                                <p class="text-[11px] text-slate-500">Minggu 2 • Sedang Berlangsung</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                    </div>
                </div>

            </div>

        </div>

        <!-- Kolom Kanan: Progres, Dosen, Lumina AI Widget (4 Cols) -->
        <div class="col-span-12 lg:col-span-4 space-y-6">

            <!-- Card 1: Progres Pembelajaran -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                <h3 class="font-bold text-slate-900 text-sm">Progres Pembelajaran</h3>
                <div class="flex items-baseline justify-between">
                    <span class="text-3xl font-extrabold text-indigo-600">35%</span>
                    <span class="text-xs text-slate-400">Selesai</span>
                </div>
                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                    <div class="bg-indigo-600 h-full w-[35%] rounded-full"></div>
                </div>
                <p class="text-[11px] text-slate-400">2 dari 6 modul telah diselesaikan. Pertahankan semangatmu!</p>
            </div>

            <!-- Card 2: Dosen Pengampu -->
            <div class="bg-gradient-to-br from-indigo-50/50 via-white to-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase">Dosen Pengampu</span>
                
                <div class="flex items-center gap-3">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=200&auto=format&fit=crop" 
                         alt="Dosen" 
                         class="w-12 h-12 rounded-full object-cover">
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm leading-tight">Dr. Budi Santoso, M.Kom</h4>
                        <p class="text-[11px] text-indigo-600">Fakultas Ilmu Komputer</p>
                    </div>
                </div>

                <div class="space-y-2 text-xs text-slate-600 pt-1 border-t border-slate-100">
                    <div class="flex items-center gap-2">
                        <i class="fa-regular fa-envelope text-slate-400"></i>
                        <span>b.santoso@lumina.ac.id</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-regular fa-clock text-slate-400"></i>
                        <span>Waktu Konsultasi: Sel & Kam, 13:00 - 15:00</span>
                    </div>
                </div>

                <button class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors">
                    Kirim Pesan
                </button>
            </div>

            <!-- Card 3: Lumina AI Prompt Widget -->
            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100/50 p-5 rounded-2xl border border-indigo-200/80 shadow-sm space-y-3 relative overflow-hidden">
                <div class="flex items-center gap-2 text-indigo-600">
                    <i class="fa-solid fa-sparkles text-xs"></i>
                    <span class="text-xs font-bold">Lumina AI</span>
                </div>
                <p class="text-xs text-slate-700 leading-relaxed">
                    Kesulitan memahami materi BFS? Tanya Lumina AI untuk penjelasan interaktif.
                </p>
                <button class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-xl transition-colors shadow-md shadow-indigo-200">
                    Tanya Sekarang
                </button>
            </div>

        </div>

    </div>

</div>
@endsection
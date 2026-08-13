@extends('layouts.mahasiswa')

@section('title', 'Daftar Mata Kuliah - Lumina Academy')

@section('content')
<div class="space-y-6">

    <!-- Top Header Bar (Search & Quick Icons) -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Search Bar -->
        <div class="relative w-full md:w-96">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-sm"></i>
            </span>
            <input type="text" 
                   placeholder="Cari mata kuliah..." 
                   class="w-full pl-10 pr-4 py-2.5 bg-slate-100/80 border border-transparent rounded-full text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-300 focus:ring-2 focus:ring-indigo-100 transition-all">
        </div>

        <!-- Right Quick Actions / Icons -->
        <div class="flex items-center gap-4 self-end md:self-auto">
            <button class="w-9 h-9 flex items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 transition-colors">
                <i class="fa-regular fa-moon text-base"></i>
            </button>
            <button class="w-9 h-9 flex items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 transition-colors relative">
                <i class="fa-regular fa-bell text-base"></i>
            </button>
            <button class="w-9 h-9 flex items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 transition-colors">
                <i class="fa-regular fa-star text-base"></i>
            </button>
            <div class="w-9 h-9 rounded-full overflow-hidden border border-slate-200 ml-1">
                <img src="https://ui-avatars.com/api/?name=Mahasiswa&background=0D8ABC&color=fff" alt="User Profile" class="w-full h-full object-cover">
            </div>
        </div>
    </div>

    <!-- Title & Filter Section -->
    <div class="space-y-2">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Daftar Mata Kuliah</h1>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-1">
            <p class="text-sm text-slate-500">Kelola dan pantau progress belajar Anda semester ini.</p>
            
            <!-- Filter Pills -->
            <div class="flex flex-wrap items-center gap-2">
                <button class="px-4 py-1.5 rounded-full text-xs font-semibold bg-white border border-indigo-600 text-indigo-600 shadow-sm">
                    Semua
                </button>
                <button class="px-4 py-1.5 rounded-full text-xs font-medium bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    Sedang Berlangsung
                </button>
                <button class="px-4 py-1.5 rounded-full text-xs font-medium bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    Selesai
                </button>
                <button class="px-4 py-1.5 rounded-full text-xs font-medium bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    Wajib
                </button>
            </div>
        </div>
    </div>

    <!-- Course Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pt-2">

        <!-- Card 1: Pemrograman Web Lanjut -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
            <div>
                <!-- Accent Line Header -->
                <div class="h-1.5 bg-emerald-700 w-1/3"></div>
                
                <div class="p-6 space-y-4">
                    <!-- Status Badges -->
                    <div class="flex items-center justify-between">
                        <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-semibold rounded-md">
                            Sedang Berlangsung
                        </span>
                        <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-md">
                            Wajib
                        </span>
                    </div>

                    <!-- Title & Instructor -->
                    <div class="space-y-1">
                        <h2 class="text-lg font-bold text-slate-900 leading-snug">Pemrograman Web Lanjut</h2>
                        <p class="text-xs text-slate-500 flex items-center gap-1.5">
                            <i class="fa-regular fa-user text-slate-400"></i> Dr. Alan Turing
                        </p>
                    </div>

                    <!-- Meta Info -->
                    <div class="flex items-center gap-4 text-xs text-slate-500 pt-1">
                        <span class="flex items-center gap-1.5">
                            <i class="fa-regular fa-book-open text-slate-400"></i> 12 Modul
                        </span>
                        <span class="flex items-center gap-1.5">
                            <i class="fa-regular fa-clipboard-list text-slate-400"></i> 3 Tugas
                        </span>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="p-6 pt-0">
                <a href="#" class="block w-full py-2.5 bg-indigo-700 hover:bg-indigo-800 text-white text-center font-medium text-sm rounded-xl transition-colors">
                    Lanjutkan Belajar
                </a>
            </div>
        </div>

        <!-- Card 2: Kecerdasan Buatan Terapan -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
            <div>
                <!-- Accent Line Header -->
                <div class="h-1.5 bg-emerald-700 w-1/3"></div>
                
                <div class="p-6 space-y-4">
                    <!-- Status Badges -->
                    <div class="flex items-center justify-between">
                        <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-semibold rounded-md">
                            Sedang Berlangsung
                        </span>
                        <span class="px-3 py-1 bg-slate-100 text-slate-600 text-xs font-semibold rounded-md">
                            Pilihan
                        </span>
                    </div>

                    <!-- Title & Instructor -->
                    <div class="space-y-1">
                        <h2 class="text-lg font-bold text-slate-900 leading-snug">Kecerdasan Buatan Terapan</h2>
                        <p class="text-xs text-slate-500 flex items-center gap-1.5">
                            <i class="fa-regular fa-user text-slate-400"></i> Prof. Ada Lovelace
                        </p>
                    </div>

                    <!-- Meta Info -->
                    <div class="flex items-center gap-4 text-xs text-slate-500 pt-1">
                        <span class="flex items-center gap-1.5">
                            <i class="fa-regular fa-book-open text-slate-400"></i> 14 Modul
                        </span>
                        <span class="flex items-center gap-1.5">
                            <i class="fa-regular fa-clipboard-list text-slate-400"></i> 5 Tugas
                        </span>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="p-6 pt-0">
                <a href="#" class="block w-full py-2.5 bg-indigo-700 hover:bg-indigo-800 text-white text-center font-medium text-sm rounded-xl transition-colors">
                    Lanjutkan Belajar
                </a>
            </div>
        </div>

        <!-- Card 3: Struktur Data & Algoritma (Selesai) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
            <div>
                <!-- Accent Line Header -->
                <div class="h-1.5 bg-emerald-700 w-full"></div>
                
                <div class="p-6 space-y-4">
                    <!-- Status Badges -->
                    <div class="flex items-center justify-between">
                        <span class="px-3 py-1 bg-sky-50 text-sky-600 text-xs font-semibold rounded-md flex items-center gap-1">
                            <i class="fa-regular fa-circle-check"></i> Selesai
                        </span>
                        <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-md">
                            Wajib
                        </span>
                    </div>

                    <!-- Title & Instructor -->
                    <div class="space-y-1">
                        <h2 class="text-lg font-bold text-slate-900 leading-snug">Struktur Data & Algoritma</h2>
                        <p class="text-xs text-slate-500 flex items-center gap-1.5">
                            <i class="fa-regular fa-user text-slate-400"></i> Dr. Grace Hopper
                        </p>
                    </div>

                    <!-- Meta Info -->
                    <div class="flex items-center gap-4 text-xs text-slate-500 pt-1">
                        <span class="flex items-center gap-1.5">
                            <i class="fa-regular fa-book-open text-slate-400"></i> 10 Modul
                        </span>
                        <span class="flex items-center gap-1.5">
                            <i class="fa-regular fa-clipboard-list text-slate-400"></i> 4 Tugas
                        </span>
                    </div>
                </div>
            </div>

            <!-- Action Button / Link -->
            <div class="p-6 pt-0 border-t border-slate-100 flex items-center justify-center">
                <a href="#" class="inline-flex items-center gap-2 text-indigo-600 font-semibold text-xs hover:text-indigo-800 transition-colors py-2">
                    <span>Lihat Sertifikat</span>
                    <i class="fa-regular fa-award text-sm"></i>
                </a>
            </div>
        </div>

        <!-- Card 4: Interaksi Manusia & Komputer (Belum Dimulai) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow">
            <div>
                <div class="p-6 space-y-4">
                    <!-- Status Badges -->
                    <div class="flex items-center justify-between">
                        <span class="px-3 py-1 bg-slate-100 text-slate-600 text-xs font-semibold rounded-md">
                            Belum Dimulai
                        </span>
                        <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-md">
                            Wajib
                        </span>
                    </div>

                    <!-- Title & Instructor -->
                    <div class="space-y-1">
                        <h2 class="text-lg font-bold text-slate-900 leading-snug">Interaksi Manusia & Komputer</h2>
                        <p class="text-xs text-slate-500 flex items-center gap-1.5">
                            <i class="fa-regular fa-user text-slate-400"></i> Prof. Don Norman
                        </p>
                    </div>

                    <!-- Meta Info -->
                    <div class="flex items-center gap-4 text-xs text-slate-500 pt-1">
                        <span class="flex items-center gap-1.5">
                            <i class="fa-regular fa-book-open text-slate-400"></i> 8 Modul
                        </span>
                        <span class="flex items-center gap-1.5">
                            <i class="fa-regular fa-clipboard-list text-slate-400"></i> 2 Tugas
                        </span>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="p-6 pt-0">
                <a href="#" class="block w-full py-2.5 bg-indigo-700 hover:bg-indigo-800 text-white text-center font-medium text-sm rounded-xl transition-colors">
                    Mulai Belajar
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
@extends('layouts.mahasiswa')

@section('title', 'Dashboard Mahasiswa - Lumina Academy')

@section('content')
<div class="grid grid-cols-12 gap-6">

    <!-- Left Main Column (8 Cols) -->
    <div class="col-span-12 lg:col-span-8 space-y-6">

        <!-- Banner Rekomendasi Belajar AI -->
        <div class="bg-gradient-to-r from-indigo-900 via-indigo-800 to-indigo-900 rounded-2xl p-6 text-white relative overflow-hidden shadow-xl">
            <div class="flex justify-between items-start">
                <div class="max-w-xl space-y-2">
                    <div class="inline-flex items-center gap-2 bg-indigo-800/80 px-3 py-1 rounded-full text-xs text-indigo-200 font-medium">
                        <i class="fa-solid fa-sparkles text-amber-400"></i> Rekomendasi Belajar AI
                    </div>
                    <p class="text-sm text-indigo-100 leading-relaxed">
                        Berdasarkan ketertarikan Anda pada <strong>Machine Learning</strong>, materi berikut dapat membantu meningkatkan pemahaman Anda.
                    </p>
                </div>
            </div>

            <!-- Suggestion Cards -->
            <div class="grid grid-cols-2 gap-4 mt-6">
                <div class="bg-white/10 backdrop-blur-md border border-white/10 p-4 rounded-xl flex items-center justify-between">
                    <div>
                        <span class="text-[10px] bg-amber-400/20 text-amber-300 font-bold px-2 py-0.5 rounded">Pilihan</span>
                        <h4 class="font-semibold text-sm mt-1">Pengantar Neural Networks</h4>
                        <p class="text-xs text-indigo-200 mt-0.5">Estimasi: 45 Menit</p>
                    </div>
                    <button class="w-8 h-8 bg-white text-indigo-900 rounded-lg flex items-center justify-center font-bold shadow hover:bg-indigo-50">
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </button>
                </div>

                <div class="bg-white/10 backdrop-blur-md border border-white/10 p-4 rounded-xl flex items-center justify-between">
                    <div>
                        <span class="text-[10px] bg-indigo-400/20 text-indigo-200 font-bold px-2 py-0.5 rounded">Terkait Progres</span>
                        <h4 class="font-semibold text-sm mt-1">Optimasi Python untuk Data</h4>
                        <p class="text-xs text-indigo-200 mt-0.5">Estimasi: 30 Menit</p>
                    </div>
                    <button class="w-8 h-8 bg-white text-indigo-900 rounded-lg flex items-center justify-center font-bold shadow hover:bg-indigo-50">
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Section: Mata Kuliah Aktif -->
        <div>
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-slate-900 text-lg">Mata Kuliah Aktif</h3>
                <a href="/mahasiswa/course" class="text-xs font-semibold text-indigo-600 hover:underline">Lihat Semua</a>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <!-- Course Card 1 -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <div class="flex justify-between items-start">
                        <span class="text-xs font-semibold bg-emerald-50 text-emerald-600 px-2.5 py-1 rounded-full">Wajib</span>
                        <span class="text-sm font-bold text-slate-700">75%</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900">Struktur Data & Algoritma</h4>
                        <p class="text-xs text-slate-500 mt-0.5">CS-201 • Dr. Alan</p>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div class="bg-emerald-500 h-full w-[75%] rounded-full"></div>
                    </div>
                    <div class="flex justify-between items-center pt-2">
                        <span class="text-xs text-slate-400">Tenggat: Besok</span>
                        <a href="#" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700">Lanjutkan</a>
                    </div>
                </div>

                <!-- Course Card 2 -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <div class="flex justify-between items-start">
                        <span class="text-xs font-semibold bg-emerald-50 text-emerald-600 px-2.5 py-1 rounded-full">Wajib</span>
                        <span class="text-sm font-bold text-slate-700">40%</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900">Desain Interaksi Manusia & Komputer</h4>
                        <p class="text-xs text-slate-500 mt-0.5">IT-305 • Prof. Sarah</p>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div class="bg-indigo-600 h-full w-[40%] rounded-full"></div>
                    </div>
                    <div class="flex justify-between items-center pt-2">
                        <span class="text-xs text-slate-400">Materi: Modul 4</span>
                        <a href="#" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700">Lanjutkan</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: Diskusi Aktif -->
        <div>
            <h3 class="font-bold text-slate-900 text-lg mb-4">Diskusi Aktif</h3>
            <div class="space-y-3">
                <div class="bg-white p-4 rounded-xl border border-slate-200 flex justify-between items-center">
                    <div>
                        <span class="text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-0.5 rounded">PBO Kelas A</span>
                        <h5 class="text-sm font-semibold text-slate-800 mt-1">Cara mengatasi error 'NullPointerException' di Java?</h5>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-indigo-600 font-semibold">12 Balasan</span>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 flex justify-between items-center">
                    <div>
                        <span class="text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-0.5 rounded">Kalkulus 1</span>
                        <h5 class="text-sm font-semibold text-slate-800 mt-1">Penjelasan intuisi di balik rumus Integral Tentu, tolong bantu.</h5>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-indigo-600 font-semibold">5 Balasan</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Right Sidebar Column (4 Cols) -->
    <div class="col-span-12 lg:col-span-4 space-y-6">

        <!-- Tugas & Kuis Widget -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-slate-900 text-base">Tugas & Kuis</h3>
                <span class="text-xs bg-rose-100 text-rose-600 px-2 py-0.5 rounded-full font-bold">2 Pending</span>
            </div>

            <div class="space-y-3">
                <!-- Task 1 -->
                <div class="p-3 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl">
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold text-rose-600">DUE TODAY</span>
                        <span class="text-[10px] text-rose-500">11:59 PM</span>
                    </div>
                    <h5 class="text-xs font-bold text-slate-800 mt-1">Final Project Proposal</h5>
                    <p class="text-[11px] text-slate-500">Web Engineering</p>
                </div>

                <!-- Task 2 -->
                <div class="p-3 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl">
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold text-amber-600">Due Tomorrow</span>
                        <span class="text-[10px] text-amber-500">10:00 AM</span>
                    </div>
                    <h5 class="text-xs font-bold text-slate-800 mt-1">Reading Assignment Ch. 4</h5>
                    <p class="text-[11px] text-slate-500">Database Systems</p>
                </div>

                <!-- Task 3 -->
                <div class="p-3 bg-slate-50 border-l-4 border-slate-300 rounded-r-xl">
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold text-slate-500">Due Oct 28</span>
                    </div>
                    <h5 class="text-xs font-bold text-slate-800 mt-1">Midterm Quiz 2</h5>
                    <p class="text-[11px] text-slate-500">Machine Learning</p>
                </div>
            </div>
        </div>

        <!-- Aktivitas Terbaru Widget -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="font-bold text-slate-900 text-base">Aktivitas Terbaru</h3>
            
            <div class="space-y-4 relative before:absolute before:inset-0 before:left-2.5 before:w-0.5 before:bg-slate-100">
                <div class="flex gap-3 relative">
                    <div class="w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center text-white text-[10px] z-10">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-slate-800">Menyelesaikan Modul 3</h5>
                        <p class="text-[11px] text-slate-400">Struktur Data • 2 jam yang lalu</p>
                    </div>
                </div>

                <div class="flex gap-3 relative">
                    <div class="w-5 h-5 rounded-full bg-amber-500 flex items-center justify-center text-white text-[10px] z-10">
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-slate-800">Mendapatkan Badge "Fast Learner"</h5>
                        <p class="text-[11px] text-slate-400">Sistem Gamifikasi • Kemarin</p>
                    </div>
                </div>

                <div class="flex gap-3 relative">
                    <div class="w-5 h-5 rounded-full bg-indigo-500 flex items-center justify-center text-white text-[10px] z-10">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-slate-800">Diskusi Grup Dibuat</h5>
                        <p class="text-[11px] text-slate-400">Proyek HCI • 2 Hari yang lalu</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
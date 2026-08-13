@extends('layouts.mahasiswa')

@section('title', 'Forum Diskusi - Lumina Academy')

@section('content')
<div class="space-y-6">

    <!-- Top Bar Header (Search, Title, Action Button) -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Forum Diskusi</h1>
            <p class="text-xs text-slate-500 mt-0.5">Diskusikan materi kuliah, bagikan ide, dan tanyakan keraguanmu di sini.</p>
        </div>

        <div class="flex items-center gap-4">
            <!-- Search Bar -->
            <div class="relative w-64 md:w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" 
                       placeholder="Search courses, documents..." 
                       class="w-full pl-9 pr-4 py-2 bg-slate-100/80 border border-transparent rounded-full text-xs text-slate-700 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-300 focus:ring-2 focus:ring-indigo-100 transition-all">
            </div>

            <!-- Action Icons -->
            <div class="flex items-center gap-2">
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

            <!-- New Discussion Button -->
            <button class="px-4 py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-medium text-xs rounded-xl shadow-md shadow-indigo-100 transition-colors flex items-center gap-2">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Buat Diskusi Baru</span>
            </button>
        </div>
    </div>

    <!-- Main Content Split Layout (Left: Thread List, Right: Discussion Detail) -->
    <div class="grid grid-cols-12 gap-6 items-start">

        <!-- ================= LEFT COLUMN: THREAD LIST (4 Cols) ================= -->
        <div class="col-span-12 lg:col-span-4 space-y-4">
            
            <!-- Filter Dropdown Mata Kuliah -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-2">
                <label class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Mata Kuliah</label>
                <div class="relative">
                    <select class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold rounded-xl px-3 py-2.5 appearance-none focus:outline-none focus:border-indigo-500 pr-8 cursor-pointer">
                        <option>CS201: Algoritma Lanjut</option>
                        <option>NET101: Pengantar Jaringan</option>
                        <option>DES202: Interaction Design</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400">
                        <i class="fa-solid fa-chevron-down text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Thread Cards List -->
            <div class="space-y-3">

                <!-- Thread Item 1 (Active) -->
                <div class="bg-white p-4 rounded-2xl border-2 border-indigo-600 shadow-sm relative space-y-2 cursor-pointer">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-0.5 bg-rose-100 text-rose-600 text-[10px] font-bold rounded">Tanya Dosen</span>
                        <span class="text-[10px] text-slate-400">2 jam lalu</span>
                    </div>

                    <h3 class="text-sm font-bold text-indigo-900 leading-snug">
                        Kendala implementasi Binary Search Tree di Java
                    </h3>

                    <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                        Saya mendapatkan NullPointerException di baris ke-45 ketika node yang dihapus...
                    </p>

                    <div class="flex items-center justify-between pt-2 text-xs text-slate-500">
                        <div class="flex items-center gap-2">
                            <img src="https://ui-avatars.com/api/?name=Budi+Santoso&background=E0E7FF&color=4F46E5" class="w-5 h-5 rounded-full" alt="Budi">
                            <span class="text-[11px] font-medium text-slate-700">Budi Santoso</span>
                        </div>
                        <span class="flex items-center gap-1 text-[11px] text-slate-400">
                            <i class="fa-regular fa-comment"></i> 4
                        </span>
                    </div>
                </div>

                <!-- Thread Item 2 -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm hover:border-slate-300 transition-colors space-y-2 cursor-pointer">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-0.5 bg-slate-100 text-slate-600 text-[10px] font-bold rounded">Diskusi Umum</span>
                        <span class="text-[10px] text-slate-400">Kemarin</span>
                    </div>

                    <h3 class="text-sm font-bold text-slate-900 leading-snug">
                        Pembentukan Kelompok Tugas Besar
                    </h3>

                    <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                        Mencari 2 anggota lagi untuk kelompok pemrograman web lanjut...
                    </p>

                    <div class="flex items-center justify-between pt-2 text-xs text-slate-500">
                        <div class="flex items-center gap-2">
                            <img src="https://ui-avatars.com/api/?name=Siti+Aminah&background=FCE7F3&color=BE185D" class="w-5 h-5 rounded-full" alt="Siti">
                            <span class="text-[11px] font-medium text-slate-700">Siti Aminah</span>
                        </div>
                        <span class="flex items-center gap-1 text-[11px] text-slate-400">
                            <i class="fa-regular fa-comment"></i> 12
                        </span>
                    </div>
                </div>

                <!-- Thread Item 3 -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm hover:border-slate-300 transition-colors space-y-2 cursor-pointer">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-bold rounded">Materi</span>
                        <span class="text-[10px] text-slate-400">3 hari lalu</span>
                    </div>

                    <h3 class="text-sm font-bold text-slate-900 leading-snug">
                        Ringkasan Bab 4: Kompleksitas Waktu
                    </h3>

                    <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                        Berikut catatan singkat saya mengenai Big-O Notation dan analisis algoritma...
                    </p>

                    <div class="flex items-center justify-between pt-2 text-xs text-slate-500">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-[9px] font-bold">
                                DW
                            </div>
                            <span class="text-[11px] font-medium text-slate-700">Doni Wijaya</span>
                        </div>
                        <span class="flex items-center gap-1 text-[11px] text-slate-400">
                            <i class="fa-regular fa-comment"></i> 0
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <!-- ================= RIGHT COLUMN: DISCUSSION DETAIL (8 Cols) ================= -->
        <div class="col-span-12 lg:col-span-8 space-y-4">
            
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-6">

                <!-- Question Post Header -->
                <div class="space-y-3">
                    <!-- Badges & Tags -->
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 bg-rose-100 text-rose-600 text-[10px] font-bold rounded">Tanya Dosen</span>
                        <span class="px-2.5 py-0.5 bg-slate-100 text-slate-600 text-[10px] font-medium rounded flex items-center gap-1">
                            <i class="fa-solid fa-tag text-[9px]"></i> Java
                        </span>
                        <span class="px-2.5 py-0.5 bg-slate-100 text-slate-600 text-[10px] font-medium rounded flex items-center gap-1">
                            <i class="fa-solid fa-tag text-[9px]"></i> BST
                        </span>
                    </div>

                    <!-- Question Title -->
                    <h2 class="text-xl font-extrabold text-indigo-950 leading-snug">
                        Kendala implementasi Binary Search Tree di Java
                    </h2>

                    <!-- Author Info -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=100&auto=format&fit=crop" 
                                 class="w-9 h-9 rounded-full object-cover" alt="Author">
                            <div>
                                <h4 class="font-bold text-slate-900 text-xs">Budi Santoso</h4>
                                <p class="text-[10px] text-slate-400">Mahasiswa</p>
                            </div>
                        </div>
                        <span class="text-[11px] text-slate-400">Hari ini, 09:30 AM</span>
                    </div>
                </div>

                <!-- Question Post Body -->
                <div class="space-y-4 text-xs text-slate-700 leading-relaxed">
                    <p>Halo semuanya,</p>
                    <p>
                        Saya sedang mengerjakan tugas struktur data dan mengalami masalah saat mengimplementasikan fungsi <code class="bg-slate-100 text-indigo-700 font-mono px-1.5 py-0.5 rounded">deleteNode</code> pada BST. Kode saya melempar <code class="bg-rose-50 text-rose-600 font-mono px-1.5 py-0.5 rounded">NullPointerException</code> pada baris ke-45 ketika node yang dihapus memiliki dua anak.
                    </p>

                    <!-- Code Snippet Box -->
                    <div class="bg-slate-900 text-slate-200 p-4 rounded-xl font-mono text-[11px] overflow-x-auto leading-normal">
                        <p class="text-slate-500">// Baris 43-46</p>
                        <p>Node minNode = findMin(root.right);</p>
                        <p>root.value = minNode.value;</p>
                        <p>root.right = deleteNode(root.right, minNode.value); <span class="text-rose-400">// Error terjadi di sini</span></p>
                    </div>

                    <p>
                        Apakah ada yang bisa memberikan petunjuk bagian mana yang salah dari logika tersebut?
                    </p>

                    <!-- Like / Upvote Button -->
                    <div class="pt-2">
                        <button class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg flex items-center gap-1.5 text-xs transition-colors">
                            <i class="fa-regular fa-thumbs-up text-indigo-600"></i>
                            <span>2 Likes</span>
                        </button>
                    </div>
                </div>

                <!-- Replies Section -->
                <div class="space-y-4 pt-4 border-t border-slate-100">

                    <!-- Reply Item 1 (Lecturer Reply) -->
                    <div class="bg-emerald-50/40 border border-emerald-200/80 p-4 rounded-2xl space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-emerald-800 text-white flex items-center justify-center font-bold text-xs">
                                    DA
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-slate-900 text-xs">Dr. Dian Sastrowardoyo</h4>
                                        <span class="px-2 py-0.5 bg-emerald-800 text-white text-[9px] font-bold rounded">DOSEN PENGAMPU</span>
                                    </div>
                                </div>
                            </div>
                            <span class="text-[10px] text-slate-400">Hari ini, 11:05 AM</span>
                        </div>

                        <div class="pl-11 text-xs text-slate-700 leading-relaxed space-y-2">
                            <p>Halo Budi,</p>
                            <p>
                                Kesalahan umum pada bagian ini adalah memastikan bahwa fungsi <code class="bg-white border border-emerald-200 text-emerald-800 font-mono px-1 rounded">findMin()</code> tidak mengembalikan <code class="bg-white border border-emerald-200 text-emerald-800 font-mono px-1 rounded">null</code> jika <code class="bg-white border border-emerald-200 text-emerald-800 font-mono px-1 rounded">root.right</code> ternyata kosong di kondisi tertentu.
                            </p>
                        </div>
                    </div>

                </div>

                <!-- Input Reply Box -->
                <div class="pt-2">
                    <div class="flex items-center gap-3 p-2 bg-slate-100 rounded-2xl border border-slate-200">
                        <img src="https://ui-avatars.com/api/?name=Mahasiswa&background=0D8ABC&color=fff" class="w-8 h-8 rounded-full ml-1" alt="User">
                        <input type="text" 
                               placeholder="Tulis balasan Anda di sini..." 
                               class="w-full bg-transparent text-xs text-slate-800 placeholder-slate-400 focus:outline-none">
                        <button class="text-slate-400 hover:text-slate-600 p-2">
                            <i class="fa-solid fa-paperclip text-sm"></i>
                        </button>
                        <button class="w-8 h-8 bg-indigo-700 hover:bg-indigo-800 text-white rounded-xl flex items-center justify-center shadow transition-colors">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                        </button>
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>
@endsection
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian Akhir Semester: Struktur Data - Lumina Academy</title>
    
    <!-- Tailwind CSS CDN & FontAwesome -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-100 font-sans text-slate-800 h-screen flex flex-col overflow-hidden">

    <!-- Top Navigation Header -->
    <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-4">
            <a href="/mahasiswa/assignment" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </a>
            <div>
                <h1 class="font-bold text-slate-900 text-sm md:text-base leading-tight">Ujian Akhir Semester: Struktur Data</h1>
                <p class="text-[11px] text-slate-400">Modul 4: Implementasi Binary Tree</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <!-- Timer Badge -->
            <div class="flex items-center gap-2 px-3.5 py-1.5 bg-rose-50 border border-rose-200 text-rose-600 rounded-lg text-xs font-bold">
                <i class="fa-regular fa-clock text-sm"></i>
                <span>01:45:22</span>
            </div>

            <!-- Submit Button -->
            <button class="px-5 py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-medium text-xs rounded-xl shadow-md shadow-indigo-200 transition-colors">
                Kumpulkan Jawaban
            </button>
        </div>
    </header>

    <!-- Main Workspace (3-Column Layout) -->
    <div class="flex-1 grid grid-cols-12 gap-4 p-4 overflow-hidden">

        <!-- ================= PANEL SOAL (3 Cols) ================= -->
        <div class="col-span-12 lg:col-span-3 bg-white rounded-2xl border border-slate-200 p-5 flex flex-col justify-between overflow-y-auto shadow-sm">
            <div class="space-y-4">
                <!-- Question Header -->
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500">Panel Soal</span>
                    <span class="text-xs font-bold bg-indigo-600 text-white px-2.5 py-0.5 rounded-full">Soal 1 dari 5</span>
                </div>

                <!-- Passage / Wacana -->
                <div class="space-y-2">
                    <h2 class="text-sm font-bold text-indigo-900 leading-snug">
                        Wacana 1: Pohon Biner dalam Basis Data
                    </h2>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Dalam ilmu komputer, pohon biner (binary tree) sering digunakan untuk mengimplementasikan struktur data pencarian cepat, seperti binary search tree (BST). Pada kasus nyata, indeks dalam basis data relasional banyak yang diadaptasi dari konsep B-Tree, yang merupakan generalisasi dari BST. Kecepatan pencarian, penyisipan, dan penghapusan data sangat bergantung pada keseimbangan tinggi pohon tersebut.
                    </p>
                </div>

                <!-- Task Box Prompt -->
                <div class="p-3 bg-indigo-50/60 border-l-4 border-indigo-600 rounded-r-xl space-y-1">
                    <p class="text-[11px] text-slate-700 leading-relaxed">
                        Tugas Anda adalah melengkapi implementasi kelas <span class="font-bold text-indigo-700">BinaryTree</span> di editor sebelah kanan agar metode <span class="font-bold text-indigo-700">insert()</span> dapat berfungsi dengan benar berdasarkan prinsip BST.
                    </p>
                </div>

                <!-- Divider -->
                <hr class="border-slate-100">

                <!-- Multiple Choice Question Section -->
                <div class="space-y-3">
                    <p class="text-xs font-bold text-slate-800 leading-snug">
                        Berdasarkan wacana dan kode di atas, manakah pernyataan yang BENAR mengenai Binary Search Tree? (Pilih lebih dari satu)
                    </p>

                    <!-- Option 1 -->
                    <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors">
                        <input type="checkbox" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs text-slate-700">Anak kiri selalu memiliki nilai lebih kecil dari parent-nya.</span>
                    </label>

                    <!-- Option 2 (Active/Checked) -->
                    <label class="flex items-start gap-3 p-3 rounded-xl border-2 border-indigo-600 bg-indigo-50/30 cursor-pointer">
                        <input type="checkbox" checked class="mt-0.5 rounded border-indigo-600 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs font-semibold text-slate-800">Pencarian data pada BST terburuk (unbalanced) memakan waktu O(n).</span>
                    </label>

                    <!-- Option 3 -->
                    <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors">
                        <input type="checkbox" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs text-slate-700">B-Tree sepenuhnya sama dengan Binary Tree tanpa perbedaan.</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- ================= CODE EDITOR & TERMINAL (6 Cols) ================= -->
        <div class="col-span-12 lg:col-span-6 flex flex-col gap-3 overflow-hidden">
            
            <!-- Code Editor Box -->
            <div class="flex-1 bg-[#1e293b] rounded-2xl overflow-hidden flex flex-col border border-slate-700 shadow-md">
                <!-- Editor Header Bar -->
                <div class="h-10 bg-[#0f172a] px-4 flex items-center justify-between border-b border-slate-800">
                    <!-- File Tabs -->
                    <div class="flex items-center gap-1 text-xs">
                        <button class="px-3 py-1 bg-[#1e293b] text-indigo-400 font-mono font-medium rounded-t-lg border-t border-x border-slate-700 flex items-center gap-2">
                            <i class="fa-solid fa-code text-[10px]"></i> main.py
                        </button>
                        <button class="px-3 py-1 text-slate-400 hover:text-slate-200 font-mono font-medium transition-colors">
                            test_cases.py
                        </button>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3">
                        <button class="text-xs text-slate-400 hover:text-white flex items-center gap-1">
                            <i class="fa-solid fa-rotate-right text-[10px]"></i> Reset
                        </button>
                        <button class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-lg flex items-center gap-1.5 shadow transition-colors">
                            <i class="fa-solid fa-play text-[10px]"></i> Jalankan Kode
                        </button>
                    </div>
                </div>

                <!-- Code Text Area -->
                <div class="flex-1 p-4 font-mono text-xs text-slate-200 overflow-y-auto leading-relaxed select-none">
                    <div class="flex">
                        <!-- Line Numbers -->
                        <div class="w-8 text-slate-600 text-right pr-4 select-none">
                            1<br>2<br>3<br>4<br>5<br>6<br>7<br>8<br>9<br>10<br>11<br>12
                        </div>
                        <!-- Code Lines -->
                        <div class="flex-1">
                            <p><span class="text-purple-400">class</span> <span class="text-amber-300">Node</span>:</p>
                            <p class="pl-4"><span class="text-purple-400">def</span> <span class="text-blue-400">__init__</span>(<span class="text-rose-300">self</span>, key):</p>
                            <p class="pl-8"><span class="text-rose-300">self</span>.left = <span class="text-purple-400">None</span></p>
                            <p class="pl-8"><span class="text-rose-300">self</span>.right = <span class="text-purple-400">None</span></p>
                            <p class="pl-8"><span class="text-rose-300">self</span>.val = key</p>
                            
                            <!-- Highlighted Selected Line -->
                            <div class="bg-slate-700/50 -mx-4 px-4 py-0.5 rounded">
                                <p><span class="text-purple-400">def</span> <span class="text-blue-400">insert</span>(root, key):</p>
                            </div>
                            
                            <p class="pl-8 text-slate-500"># TODO: Lengkapi logika insert di bawah ini</p>
                            <p class="pl-8"><span class="text-purple-400">if</span> root <span class="text-purple-400">is</span> <span class="text-purple-400">None</span>:</p>
                            <p class="pl-12"><span class="text-purple-400">return</span> Node(key)</p>
                            <p class="pl-8"><span class="text-purple-400">else</span>:</p>
                            <p class="pl-12 text-slate-500"># Tulis kode Anda di sini...</p>
                            <!-- Cursor Effect -->
                            <div class="w-2.5 h-4 bg-emerald-400 inline-block animate-pulse ml-12 mt-1"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Terminal Window -->
            <div class="h-36 bg-[#0f172a] rounded-2xl p-4 border border-slate-800 flex flex-col font-mono text-xs shadow-md">
                <div class="flex items-center gap-4 text-slate-400 border-b border-slate-800 pb-2 mb-2 text-[11px]">
                    <span class="text-white font-bold border-b-2 border-emerald-400 pb-2 -mb-2">Terminal</span>
                    <span class="hover:text-slate-200 cursor-pointer">Console</span>
                </div>
                <div class="flex-1 overflow-y-auto space-y-1 text-slate-300">
                    <p class="text-emerald-400"><i class="fa-solid fa-arrow-right text-[10px]"></i> python main.py</p>
                    <p class="text-rose-400 font-semibold">Traceback (most recent call last):</p>
                    <p class="text-slate-400">  File "main.py", line 15, in &lt;module&gt;</p>
                    <p class="text-slate-400">    insert(r, 50)</p>
                    <p class="text-rose-400">IndentationError: expected an indented block</p>
                </div>
            </div>

        </div>

        <!-- ================= LUMINA AI ASSISTANT (3 Cols) ================= -->
        <div class="col-span-12 lg:col-span-3 bg-white rounded-2xl border border-slate-200 p-4 flex flex-col justify-between overflow-hidden shadow-sm">
            <div class="flex flex-col h-full">
                
                <!-- AI Header -->
                <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                    <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-sm shadow-md shadow-indigo-100">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-xs">Lumina AI</h3>
                        <p class="text-[10px] text-emerald-600 font-medium flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Online (Asisten Coding)
                        </p>
                    </div>
                </div>

                <!-- Chat History Window -->
                <div class="flex-1 py-4 overflow-y-auto space-y-3">
                    
                    <!-- AI Response Bubble -->
                    <div class="flex gap-2">
                        <div class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] shrink-0 mt-1">
                            <i class="fa-solid fa-robot"></i>
                        </div>
                        <div class="p-3 bg-indigo-50 border border-indigo-100 text-slate-800 text-xs rounded-2xl rounded-tl-none space-y-1">
                            <p>
                                Halo! Saya melihat Anda mendapat <span class="text-rose-600 font-semibold">IndentationError</span>. Error ini terjadi karena baris 11 belum memiliki blok kode yang masuk (indent). Ada yang ingin didiskusikan tentang alur <span class="font-mono text-indigo-700 bg-indigo-100/60 px-1 rounded">insert</span>-nya?
                            </p>
                        </div>
                    </div>

                    <!-- User Message Bubble -->
                    <div class="flex gap-2 justify-end">
                        <div class="p-3 bg-indigo-700 text-white text-xs rounded-2xl rounded-tr-none max-w-[85%]">
                            Bagaimana cara mengecek apakah nilai yang akan di-insert lebih kecil dari root.val?
                        </div>
                        <div class="w-6 h-6 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-[10px] shrink-0 mt-1">
                            <i class="fa-regular fa-user"></i>
                        </div>
                    </div>

                    <!-- AI Typing Indicator -->
                    <div class="flex gap-2 items-center">
                        <div class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] shrink-0">
                            <i class="fa-solid fa-robot"></i>
                        </div>
                        <div class="px-3 py-2 bg-slate-100 rounded-full flex gap-1 items-center">
                            <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce"></span>
                            <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                            <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                        </div>
                    </div>

                </div>

                <!-- Input Message Field -->
                <div class="pt-2 border-t border-slate-100">
                    <div class="relative flex items-center">
                        <input type="text" 
                               placeholder="Tanya Lumina AI..." 
                               class="w-full pl-3 pr-10 py-2.5 bg-slate-100 border border-transparent rounded-full text-xs text-slate-700 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-300 transition-all">
                        <button class="absolute right-2 p-1.5 text-indigo-600 hover:text-indigo-800 transition-colors">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </div>

</body>
</html>
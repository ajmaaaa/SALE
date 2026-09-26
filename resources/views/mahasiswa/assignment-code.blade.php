<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f4f5f7">
    <title>{{ $item['title'] }} | SALE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --workbench-left-width: 340px;
            --workbench-right-width: 340px;
        }
        #workbench-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 85px);
            min-height: 640px;
            position: relative;
        }
        @media (max-width: 1279.98px) {
            #panel-question,
            #panel-ai {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
            }
        }
        @media (min-width: 1280px) {
            #workbench-container {
                flex-direction: row !important;
                align-items: stretch !important;
                gap: 0 !important;
            }
            #panel-question {
                width: var(--workbench-left-width, 340px) !important;
                min-width: 240px !important;
                max-width: 600px !important;
                flex-shrink: 0 !important;
            }
            #panel-editor {
                flex: 1 1 0% !important;
                min-width: 320px !important;
                width: auto !important;
            }
            #panel-ai {
                width: var(--workbench-right-width, 340px) !important;
                min-width: 260px !important;
                max-width: 600px !important;
                flex-shrink: 0 !important;
            }
        }
        #workbench-container,
        #panel-question,
        #panel-editor,
        #panel-ai,
        .code-editor,
        .cm-editor {
            transition: none !important;
            animation: none !important;
        }
    </style>
    <script>
        (function() {
            try {
                if (window.innerWidth >= 1280) {
                    var lw = localStorage.getItem('sale.workbench.leftWidth');
                    var rw = localStorage.getItem('sale.workbench.rightWidth');
                    if (lw) {
                        var w = Math.max(220, Math.min(600, parseInt(lw, 10)));
                        if (!isNaN(w)) document.documentElement.style.setProperty('--workbench-left-width', w + 'px');
                    }
                    if (rw) {
                        var w = Math.max(250, Math.min(600, parseInt(rw, 10)));
                        if (!isNaN(w)) document.documentElement.style.setProperty('--workbench-right-width', w + 'px');
                    }
                }
            } catch (e) {}
        })();
    </script>
</head>
<body class="min-h-screen bg-canvas font-sans text-ink antialiased">
    @php
    $currentRole = auth()->user()?->role?->name ?? (session('auth_user.role') ?? (request()->routeIs('dosen.*') ? 'dosen' : 'mahasiswa'));
    $isLecturer = ($currentRole === 'dosen') || request()->routeIs('dosen.*');
    if ($currentRole === 'mahasiswa' || session('auth_user.role') === 'mahasiswa') {
        $isLecturer = false;
    }
    $isMaterial = ($item['type'] ?? '') === 'materi';
    $storedLanguage = $item['language'] ?? 'python';
    $requestedLanguage = request()->query('language');
    $language = in_array($requestedLanguage, ['python', 'web'], true) ? $requestedLanguage : $storedLanguage;

    $materialSteps = [];
    if (!empty($item['coding_steps']) && count($item['coding_steps']) > 1) {
        $materialSteps = $item['coding_steps'];
    } elseif ($isMaterial || (int)($item['id'] ?? 0) === 1) {
        if ($language === 'web') {
            $materialSteps = [
                [
                    'title' => 'Bagian 1: Struktur Semantik HTML',
                    'cpmk' => $item['cpmk'] ?? 'CPMK-01',
                    'body' => "Pada bagian pertama ini, pelajari struktur elemen semantik dokumen web menggunakan HTML5.\n\nFokus Pembelajaran:\n1. Elemen header, main, dan struktur kontainer dokumen.\n2. Hubungan antara tag HTML dan pohon DOM peramban.\n\nInstruksi:\nCermati berkas index.html pada panel editor dan jalankan pratinjau browser untuk melihat hasil render dokumen.",
                    'code' => "<!DOCTYPE html>\n<html lang=\"id\">\n<head>\n    <meta charset=\"utf-8\">\n    <title>Bagian 1: Struktur HTML</title>\n    <style>\n        body { font-family: system-ui, sans-serif; padding: 2rem; background: #f8fafc; }\n        .card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }\n    </style>\n</head>\n<body>\n    <div class=\"card\">\n        <h1>Bagian 1: Struktur Web Semantik</h1>\n        <p>Halaman HTML dasar berhasil diinisialisasi.</p>\n    </div>\n</body>\n</html>",
                ],
                [
                    'title' => 'Bagian 2: Penataan Gaya dengan CSS Modern',
                    'cpmk' => $item['cpmk'] ?? 'CPMK-02',
                    'body' => "Pada bagian kedua ini, kita menerapkan styling CSS modern menggunakan Flexbox dan variabel warna.\n\nFokus Pembelajaran:\n1. Tata letak responsif menggunakan flexbox.\n2. Penggunaan kontras warna yang baik untuk aksesibilitas.\n\nInstruksi:\nPerhatikan penambahan style pada tag <style> dan amati perubahannya di tab Pratinjau.",
                    'code' => "<!DOCTYPE html>\n<html lang=\"id\">\n<head>\n    <meta charset=\"utf-8\">\n    <title>Bagian 2: Gaya CSS</title>\n    <style>\n        body { font-family: system-ui, sans-serif; padding: 2rem; background: #0f172a; color: #f8fafc; }\n        .container { max-width: 600px; margin: 0 auto; }\n        .card { background: #1e293b; padding: 2rem; border-radius: 12px; border: 1px solid #334155; }\n        .btn { background: #2563eb; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; font-weight: 600; cursor: pointer; }\n        .btn:hover { background: #1d4ed8; }\n    </style>\n</head>\n<body>\n    <div class=\"container\">\n        <div class=\"card\">\n            <h2>Bagian 2: Styling Modern</h2>\n            <p>Desain visual diperbarui dengan tema gelap elegan dan komponen tombol.</p>\n            <button class=\"btn\">Aksi Interaktif</button>\n        </div>\n    </div>\n</body>\n</html>",
                ],
                [
                    'title' => 'Bagian 3: Interaktivitas DOM dengan JavaScript',
                    'cpmk' => $item['cpmk'] ?? 'CPMK-03',
                    'body' => "Pada bagian ketiga ini, tambahkan logika interaktivitas JavaScript untuk menangani event klik pengguna secara langsung.\n\nFokus Pembelajaran:\n1. Event listener DOM (addEventListener).\n2. Pembaruan teks dan status antarmuka secara dinamis.\n\nInstruksi:\nJalankan kode dan klik tombol interaktif di tab Pratinjau. Setelah selesai, klik 'Selesai Course' di kanan atas.",
                    'code' => "<!DOCTYPE html>\n<html lang=\"id\">\n<head>\n    <meta charset=\"utf-8\">\n    <title>Bagian 3: Interaktivitas JS</title>\n    <style>\n        body { font-family: system-ui, sans-serif; padding: 2rem; background: #f1f5f9; }\n        .card { max-width: 480px; margin: 0 auto; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }\n        .counter-val { font-size: 2.5rem; font-weight: 800; color: #1e40af; margin: 1rem 0; }\n        .btn { background: #1e40af; color: white; border: none; padding: 0.7rem 1.4rem; border-radius: 8px; font-weight: 700; cursor: pointer; }\n    </style>\n</head>\n<body>\n    <div class=\"card\">\n        <h2>Bagian 3: Interaksi JavaScript</h2>\n        <p>Penghitung klik dinamis dengan JavaScript:</p>\n        <div class=\"counter-val\" id=\"counter\">0</div>\n        <button class=\"btn\" id=\"btn-count\">Tambah Hitungan</button>\n    </div>\n    <script>\n        let count = 0;\n        const el = document.getElementById('counter');\n        document.getElementById('btn-count').addEventListener('click', () => {\n            count++;\n            el.textContent = count;\n        });\n    </script>\n</body>\n</html>",
                ],
            ];
        } else {
            $materialSteps = [
                [
                    'title' => 'Bagian 1: Struktur Simpul (Node) dan Inisialisasi',
                    'cpmk' => $item['cpmk'] ?? 'CPMK-01',
                    'body' => "Pada bagian pertama ini, kita akan mendefinisikan kelas simpul (Node) yang menjadi fondasi dasar dari Binary Search Tree (BST).\n\nSetiap simpul pada BST memiliki tiga komponen utama:\n1. Nilai data (value / key)\n2. Penunjuk cabang kiri (left pointer) untuk simpul bernilai lebih kecil\n3. Penunjuk cabang kanan (right pointer) untuk simpul bernilai lebih besar\n\nInstruksi Belajar:\n1. Cermati kelas Node dan BinaryTree pada panel editor.\n2. Perhatikan konstruktor __init__ yang menginisialisasi left dan right bernilai None.\n3. Jalankan kode di panel editor untuk memverifikasi inisialisasi simpul dasar.",
                    'code' => "class Node:\n    def __init__(self, key):\n        self.left = None\n        self.right = None\n        self.value = key\n\n    def __repr__(self):\n        return f\"Node({self.value})\"\n\n\nclass BinaryTree:\n    def insert(self, root, key):\n        if root is None:\n            return Node(key)\n        if key < root.value:\n            root.left = self.insert(root.left, key)\n        elif key > root.value:\n            root.right = self.insert(root.right, key)\n        return root\n\n# Inisialisasi simpul akar (root)\ntree = BinaryTree()\nroot = tree.insert(None, 50)\nprint(f\"Akar terbentuk: {root}\")\nprint(f\"Cabang kiri: {root.left}, Cabang kanan: {root.right}\")\n",
                ],
                [
                    'title' => 'Bagian 2: Logika Penyisipan Rekursif (Insertion)',
                    'cpmk' => $item['cpmk'] ?? 'CPMK-02',
                    'body' => "Pada bagian kedua, kita menambahkan metode insert() untuk memasukkan data baru ke dalam struktur BST secara rekursif sesuai aturan invariant BST:\n\nAturan Binary Search Tree:\n- Jika nilai baru lebih kecil dari nilai simpul saat ini, telusuri cabang Kiri (left).\n- Jika nilai baru lebih besar dari nilai simpul saat ini, telusuri cabang Kanan (right).\n- Jika cabang yang dituju masih kosong (None), buat simpul Node baru di posisi tersebut.\n\nInstruksi Belajar:\n1. Pelajari metode insert() dan pembantu rekursifnya pada kelas BinarySearchTree.\n2. Jalankan kode untuk melihat simpul 50, 30, 70, 20, 40, 60, 80 tersusun rapi.",
                    'code' => "class Node:\n    def __init__(self, key):\n        self.value = key\n        self.left = None\n        self.right = None\n\nclass BinarySearchTree:\n    def __init__(self):\n        self.root = None\n\n    def insert(self, key):\n        if self.root is None:\n            self.root = Node(key)\n            return self.root\n        return self._insert_recursive(self.root, key)\n\n    def _insert_recursive(self, current, key):\n        if key < current.value:\n            if current.left is None:\n                current.left = Node(key)\n            else:\n                self._insert_recursive(current.left, key)\n        elif key > current.value:\n            if current.right is None:\n                current.right = Node(key)\n            else:\n                self._insert_recursive(current.right, key)\n        return current\n\n# Uji coba penyisipan nilai\nbst = BinarySearchTree()\nfor val in [50, 30, 70, 20, 40, 60, 80]:\n    bst.insert(val)\nprint(\"Binary Search Tree berhasil dibangun dengan akar 50!\")\n",
                ],
                [
                    'title' => 'Bagian 3: Traversal In-Order & Verifikasi Hasil',
                    'cpmk' => $item['cpmk'] ?? 'CPMK-03',
                    'body' => "Pada bagian akhir materi ini, kita mengimplementasikan algoritma In-Order Traversal (Kiri -> Akar -> Kanan) yang memiliki sifat istimewa pada BST: mencetak seluruh data dalam urutan terurut menaik (ascending order).\n\nKarakteristik Kompleksitas:\n- Waktu penelusuran: O(n) karena setiap simpul dikunjungi tepat satu kali.\n- Ruang memori: O(h) di mana h adalah tinggi pohon.\n\nInstruksi Belajar:\n1. Jalankan kode lengkap di panel editor.\n2. Perhatikan output traversal yang otomatis terurut rapi dari terkecil ke terbesar.\n3. Setelah selesai mempelajari seluruh bagian, klik tombol 'Selesai Course' di pojok kanan atas.",
                    'code' => "class Node:\n    def __init__(self, key):\n        self.value = key\n        self.left = None\n        self.right = None\n\nclass BinarySearchTree:\n    def __init__(self):\n        self.root = None\n\n    def insert(self, key):\n        if self.root is None:\n            self.root = Node(key)\n            return self.root\n        return self._insert_rec(self.root, key)\n\n    def _insert_rec(self, cur, key):\n        if key < cur.value:\n            if cur.left is None:\n                cur.left = Node(key)\n            else:\n                self._insert_rec(cur.left, key)\n        elif key > cur.value:\n            if cur.right is None:\n                cur.right = Node(key)\n            else:\n                self._insert_rec(cur.right, key)\n        return cur\n\n    def inorder(self):\n        result = []\n        self._inorder_rec(self.root, result)\n        return result\n\n    def _inorder_rec(self, cur, res):\n        if cur is not None:\n            self._inorder_rec(cur.left, res)\n            res.append(cur.value)\n            self._inorder_rec(cur.right, res)\n\n# Pengujian Komprehensif\nbst = BinarySearchTree()\ndata = [45, 15, 75, 10, 20, 60, 90]\nfor item in data:\n    bst.insert(item)\n\nhasil_inorder = bst.inorder()\nprint(f\"Data awal      : {data}\")\nprint(f\"Hasil In-order : {hasil_inorder}\")\nassert hasil_inorder == sorted(data), \"BST Traversal valid!\"\nprint(\"Traversal sukses dan seluruh simpul terurut dengan sempurna!\")\n",
                ],
            ];
        }
    } else {
        $materialSteps = [
            [
                'title' => $item['title'] ?? 'Praktikum Coding',
                'cpmk' => $item['cpmk'] ?? 'CPMK-01',
                'body' => $item['body'] ?? 'Lengkapi tugas pemrograman berikut.',
                'code' => $language === 'web'
                    ? '<!DOCTYPE html><html><body><h1>Halo SALE</h1></body></html>'
                    : '# Tulis jawaban Python kamu di sini',
            ]
        ];
    }
    $totalSteps = count($materialSteps);
    $finishUrl = $isLecturer ? route('dosen.course.show', $course['id']) : route('mahasiswa.course.show', $course['id']);

    if ($language === 'web') {
        $defaultFiles = [[
            'name' => 'index.html',
            'code' => $materialSteps[0]['code'] ?? '<!DOCTYPE html><html><body><h1>Halo SALE</h1></body></html>',
        ]];
    } elseif ((int)($item['id'] ?? 0) === 1 || $isMaterial) {
        $defaultFiles = [[
            'name' => 'main.py',
            'code' => $materialSteps[0]['code'] ?? 'class Node:',
        ]];
    } else {
        $defaultFiles = [['name' => 'main.py', 'code' => '# Tulis jawaban Python kamu di sini']];
    }
@endphp
    <header class="sticky top-0 z-30 bg-white shadow-[0_1px_3px_rgba(29,39,48,0.06)] border-b border-line/60">
        <div class="flex min-h-14 w-full flex-wrap items-center justify-between gap-3 px-4 py-2 sm:px-6">
            <div class="flex min-w-0 items-center gap-2.5">
                <a href="{{ $finishUrl }}" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-ink text-xs font-semibold transition cursor-pointer" aria-label="Kembali ke Halaman Course">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    <span class="hidden sm:inline">Kembali</span>
                </a>
                <div class="min-w-0 border-l border-line/60 pl-3">
                    <h1 class="truncate text-sm font-bold text-ink leading-tight">{{ $item['title'] }}</h1>
                    <p class="truncate text-xs text-muted">{{ $course['code'] }} · {{ $item['module'] }}</p>
                </div>
            </div>

            {{-- Center: Tombol Daftar Bagian (Grid Popover Trigger) --}}
            <div class="flex items-center gap-2">
                <button type="button" id="btn-open-material-modal" class="button-secondary text-xs py-1.5 px-3 font-bold flex items-center gap-1.5 bg-white hover:bg-slate-50 border-slate-300 text-slate-800 shadow-2xs cursor-pointer" title="Buka Daftar Bagian Materi">
                    <svg class="h-3.5 w-3.5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                    <span>Daftar Bagian (<span id="header-cur-step">1</span>/{{ $totalSteps }})</span>
                </button>
            </div>

            {{-- Right: Stepper Navigasi (Sebelumnya, Selanjutnya / Selesai Course di Bagian Terakhir) --}}
            <div class="flex items-center gap-2">
                @if($isLecturer)
                    <span class="hidden sm:inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 border border-slate-200">
                        <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Mode Tinjau Dosen
                    </span>
                @endif

                <button type="button" id="btn-step-prev" class="button-secondary text-xs py-1.5 px-3 font-semibold inline-flex items-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer" title="Bagian Sebelumnya" disabled>
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                    <span>Sebelumnya</span>
                </button>

                <button type="button" id="btn-step-next" class="button-primary text-xs py-1.5 px-3.5 font-semibold inline-flex items-center gap-1.5 cursor-pointer shadow-xs {{ $totalSteps <= 1 ? 'hidden' : '' }}" title="Bagian Selanjutnya">
                    <span>Selanjutnya</span>
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </button>

                @if($isMaterial || $isLecturer)
                    <a href="{{ $finishUrl }}" id="btn-step-finish" class="button-primary text-xs py-1.5 px-3.5 font-bold inline-flex items-center gap-1.5 shadow-xs cursor-pointer {{ $totalSteps > 1 ? 'hidden' : '' }}" title="Selesai Course">
                        <span>Selesai Course</span>
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                    </a>
                @else
                    <form data-code-submit method="post" action="{{ route('mahasiswa.course.submit', [$course['id'], $item['id']]) }}" id="form-code-submit" class="{{ $totalSteps > 1 ? 'hidden' : '' }}">
                        @csrf
                        <input type="hidden" name="answer" data-code-answer>
                        <button disabled class="button-primary text-xs py-1.5 px-3.5 font-bold shadow-xs">
                            Kumpulkan Kode
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </header>

    <main class="w-full p-4 sm:p-5">
        @if($errors->any())
            <div role="alert" class="mb-4 rounded-lg border border-danger bg-white p-3 text-xs text-danger">{{ $errors->first() }}</div>
        @endif

        {{-- 3-Panel Workbench: Left (Soal & Instruksi), Center (Code Editor & Linux Terminal), Right (Lumina AI Assistant) --}}
        <div id="workbench-container" class="flex flex-col xl:flex-row items-stretch gap-3 xl:gap-0 h-[calc(100vh-85px)] min-h-[640px] relative">

            {{-- PANEL 1 (KIRI): Soal, Materi & Capaian Pembelajaran ("soalnya di kiri") --}}
            <section id="panel-question" class="surface flex flex-col shrink-0 h-full rounded-xl overflow-hidden shadow-sm border border-line/60 transition-none" style="width: var(--workbench-left-width, 340px); min-width: 240px; max-width: 600px;" aria-labelledby="question-heading">
                {{-- Header Panel Kiri --}}
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span id="panel-step-badge" class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-soft text-brand font-bold text-xs">
                            1
                        </span>
                        <span class="text-xs font-semibold text-ink">
                            {{ $isMaterial ? 'Materi Pemrograman' : 'Praktikum Coding' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="panel-step-cpmk" class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-600">
                            {{ $materialSteps[0]['cpmk'] ?? ($item['cpmk'] ?? 'CPMK-01') }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-white border border-line/70 text-ink shadow-2xs">
                            Interaktif
                        </span>
                    </div>
                </div>

                {{-- Scrollable Body Panel Kiri --}}
                <div class="p-4 flex-1 overflow-y-auto [scrollbar-gutter:stable] space-y-4">
                    @foreach($materialSteps as $sIdx => $step)
                        <div data-material-card="{{ $sIdx }}" class="{{ $sIdx === 0 ? '' : 'hidden' }} space-y-4">
                            <div>
                                <h2 class="text-sm font-bold text-ink leading-snug">{{ $step['title'] }}</h2>
                                <p class="text-xs text-muted mt-0.5">{{ $item['module'] }} ({{ $course['lecturer'] }})</p>
                            </div>

                            <div class="border-t border-line/60 pt-3">
                                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-muted">
                                    {{ $isMaterial ? 'Uraian Materi & Panduan' : 'Petunjuk Pengerjaan' }}
                                </h3>
                                <p class="whitespace-pre-line text-xs font-medium leading-relaxed text-ink">{{ $step['body'] }}</p>
                            </div>

                            @if(!empty($step['attachment']))
                                @php $stepFile = session('learning.files.'.$step['attachment']); @endphp
                                @if(str_starts_with($stepFile['mime'] ?? '', 'image/'))
                                    <div class="rounded border border-slate-200 p-2 bg-slate-50">
                                        <img class="max-h-44 w-full rounded-lg object-contain" src="{{ route('preview.file', $step['attachment']) }}" alt="Lampiran {{ $step['title'] }}">
                                    </div>
                                @else
                                    <a class="button-secondary flex w-full items-center justify-center px-3 py-2 text-xs" href="{{ route('preview.file', $step['attachment']) }}">Buka lampiran{{ !empty($stepFile['name']) ? ': '.$stepFile['name'] : '' }}</a>
                                @endif
                            @endif

                            @if(!empty($step['link']))
                                <div class="rounded-lg border border-line/70 bg-white p-2.5">
                                    <a class="quiet-link inline-flex items-center gap-1.5 text-xs font-semibold text-brand hover:underline" href="{{ $step['link'] }}" target="_blank" rel="noopener">
                                        <span>Buka tautan referensi</span>
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Lampiran Berkas / Dokumen / Gambar Pendukung --}}
                    @php
                        $allAttachments = array_values(array_filter(array_unique(array_merge(
                            $item['attachments'] ?? [],
                            !empty($item['attachment']) ? [$item['attachment']] : []
                        ))));
                    @endphp
                    @if(!empty($item['question_image']) || !empty($allAttachments) || !empty($item['link']))
                        <div class="border-t border-line/60 pt-4 space-y-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-ink">Berkas &amp; Lampiran Pendukung</h3>
                            @if(!empty($item['question_image']))
                                @php
                                    $imgMeta = \App\Support\LearningPreview::fileMeta($item['question_image']);
                                    $imgAlt = $item['image_alt'] ?? 'Gambar pendukung';
                                    $imgName = !empty($item['image_alt']) ? $item['image_alt'] : ($imgMeta['name'] ?? 'Gambar pendukung');
                                    $imgUrl = route('preview.file', ['file' => $item['question_image'], 'inline' => 1]);
                                    $imgDownloadUrl = route('preview.file', ['file' => $item['question_image'], 'download' => 1]);
                                @endphp
                                <div class="rounded-lg border border-line/70 bg-white p-2.5 shadow-2xs space-y-2">
                                    <img src="{{ $imgUrl }}" alt="{{ $imgAlt }}" class="max-h-48 w-full object-contain rounded border border-line/40 bg-slate-50">
                                    <div class="flex items-center justify-between text-xs pt-1">
                                        <span class="truncate font-medium text-ink" title="{{ $imgName }}">{{ $imgName }}</span>
                                        <a href="{{ $imgDownloadUrl }}" class="button-secondary text-[11px] py-1 px-2.5 font-semibold shrink-0">Unduh</a>
                                    </div>
                                </div>
                            @endif
                            @foreach($allAttachments as $file)
                                @php
                                    $fileMeta = \App\Support\LearningPreview::fileMeta($file);
                                    $isPdf = ($fileMeta['mime'] ?? '') === 'application/pdf';
                                    $fileName = $fileMeta['name'] ?? 'Berkas lampiran';
                                    $fileUrl = route('preview.file', ['file' => $file, 'inline' => $isPdf ? 1 : null]);
                                    $fileDownloadUrl = route('preview.file', ['file' => $file, 'download' => 1]);
                                @endphp
                                <div class="flex items-center justify-between gap-2 rounded-lg border border-line/70 bg-white p-2.5 shadow-2xs">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded {{ $isPdf ? 'bg-rose-50 text-rose-600' : 'bg-brand-soft text-brand' }} font-bold text-[10px]">
                                            {{ $isPdf ? 'PDF' : 'FILE' }}
                                        </span>
                                        <span class="truncate text-xs font-medium text-ink" title="{{ $fileName }}">{{ $fileName }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="button-secondary text-[11px] py-1 px-2">Buka</a>
                                        <a href="{{ $fileDownloadUrl }}" class="button-secondary text-[11px] py-1 px-2">Unduh</a>
                                    </div>
                                </div>
                            @endforeach
                            @if(!empty($item['link']))
                                <div class="rounded-lg border border-line/70 bg-white p-2.5">
                                    <a class="quiet-link inline-flex items-center gap-1.5 text-xs font-semibold text-brand hover:underline" href="{{ $item['link'] }}" target="_blank" rel="noopener">
                                        <span>Buka tautan referensi</span>
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Footer Panel Kiri --}}
                <div class="px-4 py-2.5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between text-xs text-slate-400 shrink-0">
                    <span id="panel-step-counter-bottom">Bagian 1 dari {{ $totalSteps }}</span>
                    <span class="text-muted font-medium flex items-center gap-1">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                        Editor siap pakai
                    </span>
                </div>
            </section>

            {{-- Handle Geser Kiri (Soal <-> Editor) --}}
            <div data-resizer="left" class="hidden xl:flex w-3 shrink-0 cursor-col-resize items-center justify-center group relative z-10 select-none py-4 hover:bg-brand/5 active:bg-brand/10 transition-colors" title="Geser untuk mengatur lebar soal">
                <div class="w-1 h-12 rounded-full bg-slate-300 group-hover:bg-brand group-active:bg-brand group-hover:w-1.5 transition-all"></div>
            </div>

            {{-- PANEL 2 (TENGAH): Code Editor & Linux Sandbox Terminal --}}
            <div id="panel-editor" class="flex-1 min-w-[320px] flex flex-col h-full overflow-hidden transition-none">
                {{-- Editor Section --}}
                <section class="flex-1 flex flex-col min-h-0 rounded-xl bg-white shadow-sm border border-line/60 overflow-hidden" aria-labelledby="editor-heading">
                    {{-- Single Integrated Toolbar: Tab Berkas di kiri, Aksi & Terminal Toggle di kanan --}}
                    <div class="flex items-center justify-between border-b border-line/60 bg-slate-50 px-2.5 py-1.5 gap-2 select-none">
                        {{-- File Tabs (Kiri) --}}
                        <div data-file-tabs class="flex items-center gap-1 overflow-x-auto min-w-0" role="tablist" aria-label="Berkas kode"></div>

                        {{-- Action Buttons (Kanan): Tanyakan Baris | Terminal (Icon) | Play (Icon) --}}
                        <div class="flex items-center gap-1.5 shrink-0 ml-auto">
                            <button type="button" data-mention-code disabled class="h-8 !min-h-0 px-3 inline-flex items-center justify-center rounded-lg border border-[#b9c0ca] bg-white text-xs font-semibold text-ink transition hover:border-ink hover:bg-slate-50 disabled:opacity-40 shadow-2xs leading-none" title="Tanyakan baris kode terpilih ke Lumina AI">
                                Tanyakan Baris
                            </button>
                            <button type="button" data-terminal-toggle class="h-8 w-8 !p-0 !min-h-0 inline-flex items-center justify-center rounded-lg border border-[#b9c0ca] bg-white hover:bg-slate-50 text-slate-700 transition hover:border-ink shadow-2xs leading-none" title="Buka / Tutup Terminal" aria-label="Terminal">
                                <svg class="h-3.5 w-3.5 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
                            </button>
                            <button type="button" disabled data-run-code class="h-8 w-8 !p-0 !min-h-0 inline-flex items-center justify-center rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs transition disabled:opacity-40 leading-none" title="Jalankan kode" aria-label="Jalankan kode">
                                <svg class="h-3.5 w-3.5 fill-current text-white ml-0.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <polygon points="5 3 19 12 5 21 5 3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <textarea data-code-files-json class="hidden" aria-hidden="true">@json($defaultFiles)</textarea>
                    <div data-code-editor data-assignment-id="{{ $item['id'] }}" data-runtime-url="{{ asset('vendor/pyodide') }}/" data-code-language="{{ $language }}" data-max-files="5" data-max-file-chars="8000" data-max-total-chars="20000" class="code-editor flex-1 h-full overflow-auto bg-[#282c34]" aria-label="Editor kode {{ $language === 'web' ? 'HTML/CSS/JS' : 'Python' }}"></div>
                    <div class="flex items-center justify-between gap-3 px-3 py-1.5 bg-[#20242b] text-[11px] text-[#aeb8c4]">
                        <span data-code-save-status>Draf tersimpan di browser</span>
                        <span class="flex items-center gap-3">
                            <span data-chars-count></span>
                            <span>UTF-8, 4 Spasi</span>
                        </span>
                    </div>
                </section>

                {{-- Terminal Wrapper: Resizer di atas + Terminal Panel (On-demand) --}}
                <div id="terminal-wrapper" class="flex flex-col shrink-0 mt-2" hidden>
                    {{-- Resizer Handle Tinggi Terminal --}}
                    <div data-resizer="terminal" class="h-3 w-full shrink-0 cursor-row-resize flex items-center justify-center bg-slate-200/80 hover:bg-brand/30 group transition-colors rounded-t-lg select-none" title="Geser ke atas/bawah untuk mengatur tinggi terminal">
                        <div class="h-1 w-14 rounded-full bg-slate-400 group-hover:bg-brand transition-colors"></div>
                    </div>

                    <section id="panel-terminal" class="flex flex-col rounded-b-xl bg-[#0d1117] text-[#c9d1d9] shadow-sm border border-line/60 overflow-hidden" style="height: 240px; min-height: 120px;" aria-labelledby="terminal-heading">
                        <div class="flex items-center justify-between px-4 py-2 bg-[#161b22] border-b border-white/10 select-none">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-1.5">
                                    <button type="button" data-terminal-close class="h-3 w-3 rounded-full bg-[#ff5f56] hover:opacity-80 transition inline-block shadow-xs" title="Tutup Terminal" aria-label="Tutup Terminal"></button>
                                    <button type="button" data-terminal-minimize class="h-3 w-3 rounded-full bg-[#ffbd2e] hover:opacity-80 transition inline-block shadow-xs" title="Perkecil Terminal" aria-label="Perkecil Terminal"></button>
                                    <button type="button" data-terminal-maximize class="h-3 w-3 rounded-full bg-[#27c93f] hover:opacity-80 transition inline-block shadow-xs" title="Perbesar Terminal" aria-label="Perbesar Terminal"></button>
                                </div>
                                <span class="text-xs font-mono text-slate-300 font-medium">{{ $language === 'web' ? 'Output Web / Pratinjau Browser' : 'Output Python / Terminal' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" data-stop-code hidden class="text-xs text-rose-300 px-2 py-1">Hentikan</button>
                                <button type="button" data-clear-terminal class="text-xs font-mono text-slate-400 hover:text-white px-2 py-1 transition">
                                    Bersihkan
                                </button>
                                <button type="button" data-terminal-close-btn class="text-xs text-slate-400 hover:text-white px-2 py-0.5 rounded hover:bg-white/10 transition" title="Tutup Terminal">
                                    ✕
                                </button>
                            </div>
                        </div>

                        {{-- Output Tabs --}}
                        <div class="flex items-end gap-1 px-3 pt-1.5 bg-[#0d1117] border-b border-white/10" role="tablist" aria-label="Panel output">
                            <button type="button" role="tab" aria-selected="true" data-output-tab="console" class="rounded-t-md px-3 py-1 font-mono text-[11px] border border-b-0 border-white/10 bg-white/10 text-white">Konsol</button>
                            <button type="button" role="tab" aria-selected="false" data-output-tab="preview" class="rounded-t-md px-3 py-1 font-mono text-[11px] border border-b-0 border-transparent text-slate-400 hover:text-slate-200">Pratinjau</button>
                        </div>

                        {{-- Console Panel --}}
                        <div data-console-panel class="flex-1 flex flex-col min-h-0">
                            <div data-terminal-body class="flex-1 overflow-y-auto p-3.5 font-mono text-xs leading-relaxed space-y-1 select-text bg-[#0d1117]">
                                <p class="text-slate-500">Jalankan Kode untuk melihat output program (Python di tab Konsol, Web di tab Pratinjau).</p>
                                <div data-terminal-output class="space-y-1"></div>
                            </div>
                        </div>

                        {{-- Preview Panel --}}
                        <div data-preview-panel hidden class="flex-1 min-h-0 overflow-hidden bg-white">
                            <iframe data-preview-frame title="Pratinjau HTML/CSS/JS" sandbox="allow-scripts allow-forms" class="h-full w-full border-0 bg-white"></iframe>
                        </div>
                    </section>
                </div>
            </div>

            {{-- Handle Geser Kanan (Editor <-> Lumina AI) --}}
            <div data-resizer="right" class="hidden xl:flex w-3 shrink-0 cursor-col-resize items-center justify-center group relative z-10 select-none py-4 hover:bg-brand/5 active:bg-brand/10 transition-colors" title="Geser untuk mengatur lebar Lumina AI">
                <div class="w-1 h-12 rounded-full bg-slate-300 group-hover:bg-brand group-active:bg-brand group-hover:w-1.5 transition-all"></div>
            </div>

            {{-- PANEL 3 (KANAN): Lumina AI Assistant ("ai assitennya di kanan") --}}
            <aside id="panel-ai" class="surface flex flex-col shrink-0 h-full rounded-xl overflow-hidden shadow-sm border border-line/60 transition-none" style="width: var(--workbench-right-width, 340px); min-width: 260px; max-width: 600px;" aria-labelledby="assistant-heading">
                <div class="border-b border-line/60 p-4 bg-white">
                    <div class="flex items-center justify-between">
                        <h2 id="assistant-heading" class="text-sm font-bold text-ink">Lumina AI</h2>
                        <span class="text-xs font-semibold text-brand bg-brand-soft px-2 py-0.5 rounded">Asisten Belajar</span>
                    </div>
                </div>

                @if ($errors->has('ai'))
                    <p class="p-3 text-xs text-red-700">{{ $errors->first('ai') }}</p>
                @endif
                @guest
                    <form method="POST" action="{{ route('ai.login') }}" class="p-3 space-y-2.5 border-b border-line bg-canvas/30">
                        @csrf
                        <input type="hidden" name="assignment" value="{{ $item['id'] }}">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-ink">Akses Asisten AI</span>
                            <button type="button" onclick="document.querySelector('#ai-login-email').value='demo.ai@sale.test';document.querySelector('#ai-login-password').value='password123456';this.closest('form').submit();" class="text-[10px] font-bold text-brand hover:underline inline-flex items-center gap-1 bg-brand-soft px-2 py-0.5 rounded border border-brand/20">
                                <span>✦ 1-Klik Masuk Demo AI</span>
                            </button>
                        </div>
                        <p class="text-[11px] text-muted leading-relaxed">Masuk untuk mengaktifkan sesi bimbingan AI, atau gunakan tombol <strong>1-Klik Masuk Demo AI</strong> untuk uji coba langsung.</p>
                        <label class="block text-xs">Email akun AI<input id="ai-login-email" class="field mt-1 text-xs" type="email" name="email" required autocomplete="username" placeholder="demo.ai@sale.test"></label>
                        <label class="block text-xs">Password<input id="ai-login-password" class="field mt-1 text-xs" type="password" name="password" required autocomplete="current-password" placeholder="••••••••"></label>
                        <button class="button-primary text-xs w-full py-2 font-bold" type="submit">Masuk akun AI</button>
                    </form>
                @endguest

                {{-- Chat Messages (Full-height scrollable stream) --}}
                <div data-ai-messages class="flex-1 space-y-3 overflow-y-auto p-4 text-xs leading-5 flex flex-col" aria-live="polite">
                    <article class="self-start mr-auto max-w-[92%] rounded-2xl rounded-tl-xs bg-white p-3.5 border border-line/70 shadow-xs">
                        <div class="flex items-center gap-1.5 mb-1.5">
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-brand/10 text-brand text-[10px] font-bold">✦</span>
                            <p class="font-semibold text-ink">Lumina AI</p>
                        </div>
                        <p class="text-muted leading-relaxed">
                            Halo! Saya Lumina AI, asisten coding Anda untuk modul <strong class="text-ink">{{ $item['title'] }}</strong>.
                            Tanyakan satu konsep atau pilih potongan kode lalu klik <strong class="text-ink">Tanyakan baris terpilih</strong>. Contoh mengajarkan konsep pendukung, bukan implementasi tugas. Bantuan dibatasi sepanjang tugas, termasuk setelah membuka chat kembali. Pertanyaan dan kode terpilih dikirim ke layanan AI Google untuk diproses.
                        </p>
                    </article>
                </div>

                {{-- Pinned Chat Input at Bottom --}}
                <form data-ai-form method="POST" action="{{ route('ai.send', $item['id']) }}" class="border-t border-line/60 p-3 bg-white">
                    @csrf
                    <div data-code-context hidden class="mb-2 overflow-hidden rounded-lg border border-line bg-canvas">
                        <div class="flex items-center justify-between gap-2 px-2.5 py-1.5">
                            <span data-code-context-label class="text-[11px] font-semibold text-ink"></span>
                            <button type="button" data-remove-context aria-label="Hapus lampiran kode" class="px-1.5 text-muted hover:text-ink">×</button>
                        </div>
                        <pre data-code-context-text class="max-h-24 overflow-auto px-2.5 pb-2 text-[11px] font-mono text-muted"></pre>
                    </div>
                    <div class="relative rounded-xl border border-[#b9c0ca] bg-white transition-all focus-within:border-brand focus-within:ring-1 focus-within:ring-brand shadow-2xs">
                        <label for="assistant-message" class="sr-only">Pertanyaan untuk Lumina AI</label>
                        <textarea required maxlength="2000" id="assistant-message" rows="2" class="w-full bg-transparent border-0 p-2.5 pr-10 pb-7 text-xs text-ink placeholder:text-[#737b86] resize-none outline-none focus:outline-none focus:ring-0 leading-relaxed block" placeholder="Tanyakan petunjuk konsep kode..."></textarea>
                        <div class="absolute right-2 bottom-2 flex items-center">
                            <button disabled type="submit" class="button-primary h-7 w-7 !p-0 !min-h-0 rounded-lg disabled:opacity-30 inline-flex items-center justify-center transition-all duration-150 transform scale-0 opacity-0 pointer-events-none shrink-0 shadow-xs" title="Kirim pertanyaan ke Lumina AI (Enter)" aria-label="Kirim pertanyaan">
                                <svg class="h-3.5 w-3.5 fill-current text-white -mr-0.5 -mt-0.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="mt-1.5 flex items-center justify-between px-1">
                        <span data-ai-status role="status" class="text-[10px] text-muted">Memuat kuota AI…</span>
                        <span class="text-[10px] text-muted">Tekan <kbd class="font-mono bg-canvas px-1 py-0.5 rounded border border-line/60 font-semibold">Enter ↵</kbd> kirim</span>
                    </div>
                </form>
            </aside>

        </div>
    </main>

    {{-- MODAL POPOVER: DAFTAR BAGIAN MATERI --}}
    <dialog id="material-grid-modal" class="fixed inset-0 m-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl backdrop:bg-slate-900/50 max-w-lg w-[calc(100%-2rem)] h-fit">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Daftar Bagian Materi</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Pilih nomor bagian untuk langsung berpindah materi dan kode.</p>
            </div>
            <button type="button" id="modal-material-close" class="h-7 w-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center font-bold text-sm cursor-pointer">✕</button>
        </div>

        <div class="space-y-2 max-h-[50vh] overflow-y-auto p-1" id="material-steps-container">
            @foreach($materialSteps as $sIdx => $s)
                <button type="button"
                    data-grid-material-step="{{ $sIdx }}"
                    class="w-full text-left p-3 rounded-xl border text-xs font-medium transition flex items-center justify-between gap-3 cursor-pointer shadow-2xs {{ $sIdx === 0 ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white hover:border-slate-400 text-slate-700' }}">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md {{ $sIdx === 0 ? 'bg-white/20 text-white' : 'bg-brand-soft text-brand' }} font-bold text-xs" data-badge-icon>
                            {{ $sIdx + 1 }}
                        </span>
                        <span class="truncate font-semibold">{{ $s['title'] }}</span>
                    </div>
                    <span class="text-[11px] shrink-0 opacity-80">{{ $s['cpmk'] ?? 'Materi' }}</span>
                </button>
            @endforeach
        </div>

        <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-500">Total {{ $totalSteps }} Bagian Materi</span>
            <button type="button" id="modal-material-close-btn" class="button-secondary text-xs py-1.5 px-3.5 font-medium cursor-pointer">Tutup</button>
        </div>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const materialSteps = @json($materialSteps);
            const totalSteps = materialSteps.length;
            let currentStep = 0;

            const headerCurStep = document.getElementById('header-cur-step');
            const btnStepPrev = document.getElementById('btn-step-prev');
            const btnStepNext = document.getElementById('btn-step-next');
            const btnStepFinish = document.getElementById('btn-step-finish');
            const formCodeSubmit = document.getElementById('form-code-submit');

            const panelStepBadge = document.getElementById('panel-step-badge');
            const panelStepCpmk = document.getElementById('panel-step-cpmk');
            const panelStepCounterBottom = document.getElementById('panel-step-counter-bottom');
            const materialCards = document.querySelectorAll('[data-material-card]');

            const gridModal = document.getElementById('material-grid-modal');
            const btnOpenMaterialModal = document.getElementById('btn-open-material-modal');
            const modalMaterialClose = document.getElementById('modal-material-close');
            const modalMaterialCloseBtn = document.getElementById('modal-material-close-btn');
            const gridStepBtns = document.querySelectorAll('[data-grid-material-step]');

            const setMaterialStep = (idx) => {
                if (idx < 0 || idx >= totalSteps) return;
                currentStep = idx;

                // Update cards visibility
                materialCards.forEach((card, i) => {
                    card.classList.toggle('hidden', i !== currentStep);
                });

                // Update header and panel indicators
                if (headerCurStep) headerCurStep.textContent = `${currentStep + 1}`;
                if (panelStepBadge) panelStepBadge.textContent = `${currentStep + 1}`;
                if (panelStepCpmk && materialSteps[currentStep]) {
                    panelStepCpmk.textContent = materialSteps[currentStep].cpmk || 'CPMK';
                }
                if (panelStepCounterBottom) {
                    panelStepCounterBottom.textContent = `Bagian ${currentStep + 1} dari ${totalSteps}`;
                }

                // Update stepper buttons
                if (btnStepPrev) btnStepPrev.disabled = currentStep === 0;

                if (currentStep === totalSteps - 1) {
                    btnStepNext?.classList.add('hidden');
                    btnStepFinish?.classList.remove('hidden');
                    formCodeSubmit?.classList.remove('hidden');
                } else {
                    btnStepNext?.classList.remove('hidden');
                    btnStepFinish?.classList.add('hidden');
                    formCodeSubmit?.classList.add('hidden');
                }

                // Update grid buttons style
                gridStepBtns.forEach((btn, i) => {
                    const isActive = (i === currentStep);
                    btn.className = `w-full text-left p-3 rounded-xl border text-xs font-medium transition flex items-center justify-between gap-3 cursor-pointer shadow-2xs ${isActive ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white hover:border-slate-400 text-slate-700'}`;
                    const badge = btn.querySelector('[data-badge-icon]');
                    if (badge) {
                        badge.className = `flex h-6 w-6 shrink-0 items-center justify-center rounded-md ${isActive ? 'bg-white/20 text-white' : 'bg-brand-soft text-brand'} font-bold text-xs`;
                    }
                });

                // Update editor code if available
                if (materialSteps[currentStep] && materialSteps[currentStep].code && typeof window.setWorkbenchCode === 'function') {
                    window.setWorkbenchCode(materialSteps[currentStep].code);
                }
            };

            btnStepPrev?.addEventListener('click', () => setMaterialStep(currentStep - 1));
            btnStepNext?.addEventListener('click', () => setMaterialStep(currentStep + 1));

            btnOpenMaterialModal?.addEventListener('click', () => gridModal?.showModal());
            modalMaterialClose?.addEventListener('click', () => gridModal?.close());
            modalMaterialCloseBtn?.addEventListener('click', () => gridModal?.close());

            gridStepBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const targetStep = Number(btn.dataset.gridMaterialStep);
                    setMaterialStep(targetStep);
                    gridModal?.close();
                });
            });
        });
    </script>
</body>
</html>

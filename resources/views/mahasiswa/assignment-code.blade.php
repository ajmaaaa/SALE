<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#f4f5f7">
    <title>{{ $item['title'] }} | SALE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preload" href="/vendor/pyodide/pyodide.asm.wasm" as="fetch" type="application/wasm" crossorigin>
    <link rel="preload" href="/vendor/pyodide/python_stdlib.zip" as="fetch" crossorigin>
    <style>
        :root {
            --workbench-left-width: 340px;
            --workbench-right-width: 340px;
        }
        #workbench-container {
            display: flex;
            flex-direction: column;
            height: auto;
            min-height: calc(100dvh - 140px);
            position: relative;
        }
        @media (max-width: 1279.98px) {
            #workbench-container {
                display: flex !important;
                flex-direction: column !important;
                height: auto !important;
                min-height: calc(100dvh - 140px) !important;
                gap: 0 !important;
            }
            #panel-question,
            #panel-editor,
            #panel-grading,
            #panel-ai {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
            }
            .mobile-panel-hidden {
                display: none !important;
            }
            .mobile-panel-active {
                display: flex !important;
                width: 100% !important;
                height: calc(100dvh - 140px) !important;
                min-height: 480px !important;
                flex: 1 1 auto !important;
            }
        }
        @media (min-width: 1280px) {
            #workbench-container {
                flex-direction: row !important;
                align-items: stretch !important;
                gap: 0 !important;
                height: calc(100vh - 85px) !important;
                min-height: 640px !important;
            }
            #panel-question {
                display: flex !important;
                width: var(--workbench-left-width, 340px) !important;
                min-width: 240px !important;
                max-width: 600px !important;
                flex-shrink: 0 !important;
                height: 100% !important;
            }
            #panel-editor {
                display: flex !important;
                flex: 1 1 0% !important;
                min-width: 320px !important;
                width: auto !important;
                height: 100% !important;
            }
            #panel-grading,
            #panel-ai {
                display: flex !important;
                width: var(--workbench-right-width, 340px) !important;
                min-width: 260px !important;
                max-width: 600px !important;
                flex-shrink: 0 !important;
                height: 100% !important;
            }
            .mobile-panel-hidden,
            .mobile-panel-active {
                display: flex !important;
            }
        }
        #workbench-container,
        #panel-question,
        #panel-editor,
        #panel-grading,
        #panel-ai,
        .code-editor,
        .cm-editor {
            transition: none !important;
            animation: none !important;
        }
        .code-editor .cm-scroller {
            -webkit-overflow-scrolling: touch;
            touch-action: pan-x pan-y;
        }
        #panel-terminal.is-fullscreen {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100dvh !important;
            max-height: 100dvh !important;
            z-index: 99999 !important;
            border-radius: 0 !important;
            margin: 0 !important;
            border: none !important;
            display: flex !important;
            flex-direction: column !important;
        }
        #panel-terminal.is-fullscreen [data-console-panel],
        #panel-terminal.is-fullscreen [data-preview-panel] {
            flex: 1 1 auto !important;
            height: calc(100dvh - 85px) !important;
            min-height: 0 !important;
        }
        #panel-terminal.is-fullscreen [data-terminal-body],
        #panel-terminal.is-fullscreen [data-preview-frame] {
            height: 100% !important;
        }
        #terminal-wrapper.has-fullscreen [data-resizer="terminal"] {
            display: none !important;
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
    $isLecturer = auth()->user()?->hasRole(\App\Models\Role::DOSEN) ?? false;
    $isMaterial = ($item['type'] ?? '') === 'materi';
    $reviewStudent = $reviewStudent ?? null;
    $codingStepsData = $codingStepsData ?? [];
    $studentScore = $studentScore ?? null;
    $codingScoreUrl = $codingScoreUrl ?? null;
    $hasBeenGraded = $hasBeenGraded ?? false;
    if (!$isLecturer && !$isMaterial && !$hasBeenGraded && auth()->check() && \Illuminate\Support\Facades\Schema::hasTable('student_assessment_scores')) {
        $hasBeenGraded = \App\Models\StudentAssessmentScore::where('assessment_id', $item['id'] ?? 0)
            ->where('mahasiswa_id', auth()->id())
            ->where(function ($query) {
                $query->whereIn('status', [
                    \App\Models\StudentAssessmentScore::STATUS_PARTIAL,
                    \App\Models\StudentAssessmentScore::STATUS_FINAL,
                    \App\Models\StudentAssessmentScore::STATUS_PUBLISHED,
                ])->orWhereNotNull('score');
            })
            ->exists();
    }
    $isSubmitted = (!$isLecturer && !$isMaterial && (
        !empty($submission?->submitted_at)
        || in_array($submission?->status ?? '', ['submitted', 'pending', 'graded', 'graded_auto'], true)
        || !empty($submission?->answer)
        || $hasBeenGraded
        || $studentScore !== null
    ));
    $isArchived = !empty($course['id']) && (\App\Models\ClassSection::find($course['id'])?->isArchived() ?? false);
    $aiEnabled = ($isLecturer || $isSubmitted) ? false : (bool) ($item['ai_enabled'] ?? true);
    $isEditorReadOnly = ($isSubmitted || $isLecturer);
    $storedLanguage = $item['language'] ?? 'python';
    $requestedLanguage = request()->query('language');
    $language = in_array($requestedLanguage, ['python', 'web'], true) ? $requestedLanguage : $storedLanguage;

    $materialSteps = [];
    if (!empty($item['coding_steps']) && count($item['coding_steps']) >= 1) {
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
                'code' => $item['code'] ?? '',
            ]
        ];
    }
    $totalSteps = count($materialSteps);
    $finishUrl = $isLecturer ? route('dosen.course.show', $course['id']) : route('mahasiswa.course.show', $course['id']);

    $submission = $submission ?? null;
    $savedStepFiles = [];
    $hasSavedSubmission = false;

    $processFilesArray = function ($filesArray) use (&$savedStepFiles, &$hasSavedSubmission, $totalSteps, $language) {
        if (!is_array($filesArray)) return;
        if (isset($filesArray['name']) || isset($filesArray['code'])) {
            $filesArray = [$filesArray];
        }
        foreach ($filesArray as $pf) {
            if (!is_array($pf)) continue;
            $stepIdx = isset($pf['step']) ? ((int)$pf['step'] - 1) : 0;
            if (!isset($pf['step']) && preg_match('/_soal_(\d+)\./', $pf['name'] ?? '', $m)) {
                $stepIdx = ((int)$m[1]) - 1;
            }
            if ($stepIdx < 0 || $stepIdx >= $totalSteps) {
                $stepIdx = 0;
            }
            $cleanName = preg_replace('/_soal_\d+(\.[^.]+)$/', '$1', $pf['name'] ?? '');
            $savedStepFiles[$stepIdx][] = [
                'name' => $cleanName ?: ($language === 'web' ? 'untitled.html' : 'untitled'),
                'code' => $pf['code'] ?? '',
            ];
            $hasSavedSubmission = true;
        }
    };

    if (!empty($submission?->answer)) {
        try {
            $parsedAnswer = json_decode($submission->answer, true);
            if (is_array($parsedAnswer) && !empty($parsedAnswer)) {
                $processFilesArray($parsedAnswer);
            } elseif (is_string($submission->answer) && trim($submission->answer) !== '') {
                $savedStepFiles[0][] = [
                    'name' => ($language === 'web' ? 'untitled.html' : 'untitled'),
                    'code' => $submission->answer,
                ];
                $hasSavedSubmission = true;
            }
        } catch (\Throwable $e) {}
    }

    if (empty($savedStepFiles) && $submission) {
        try {
            $subAnswers = $submission->relationLoaded('answers')
                ? $submission->answers
                : \App\Models\SubmissionAnswer::where('submission_id', $submission->id)->orderBy('id')->get();
            foreach ($subAnswers as $aIdx => $sAns) {
                if (!empty($sAns->answer_text)) {
                    $decodedAns = json_decode($sAns->answer_text, true);
                    if (is_array($decodedAns) && !empty($decodedAns)) {
                        $processFilesArray($decodedAns);
                    } else {
                        $sIdx = $sAns->question_index !== null ? (int)$sAns->question_index : $aIdx;
                        if ($sIdx < 0 || $sIdx >= $totalSteps) $sIdx = 0;
                        $savedStepFiles[$sIdx][] = [
                            'name' => ($language === 'web' ? 'untitled.html' : 'untitled'),
                            'code' => $sAns->answer_text,
                        ];
                        $hasSavedSubmission = true;
                    }
                }
            }
        } catch (\Throwable $e) {}
    }

    if (!empty($savedStepFiles[0])) {
        $defaultFiles = $savedStepFiles[0];
    } elseif ($language === 'web') {
        $defaultFiles = [[
            'name' => 'untitled.html',
            'code' => $materialSteps[0]['code'] ?? '<!DOCTYPE html><html><body><h1>Halo SALE</h1></body></html>',
        ]];
    } elseif ((int)($item['id'] ?? 0) === 1 || $isMaterial) {
        $defaultFiles = [[
            'name' => 'untitled',
            'code' => $materialSteps[0]['code'] ?? 'class Node:',
        ]];
    } else {
        $defaultFiles = [['name' => 'untitled', 'code' => $materialSteps[0]['code'] ?? '']];
    }
@endphp
    @if(!$isMaterial && $hasSavedSubmission && !$isLecturer && !$isSubmitted)
        <script>
            try {
                const draftKey = `sale.code.assignment.{{ $item['id'] }}.{{ $language }}`;
                localStorage.setItem(draftKey, JSON.stringify(@json($defaultFiles)));
            } catch(e) {}
        </script>
    @endif
    <header class="sticky top-0 z-30 bg-white shadow-[0_1px_3px_rgba(29,39,48,0.06)] border-b border-line/60">
        <div class="flex min-h-14 w-full flex-wrap items-center justify-between gap-2 sm:gap-3 px-3 py-2 sm:px-6">
            <div class="flex min-w-0 items-center gap-2 sm:gap-2.5">
                @if($isLecturer)
                    <a href="{{ route('dosen.penilaian.asesmen.nilai', [$course['id'], $item['id']]) }}" class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold transition cursor-pointer shrink-0" title="Kembali ke Penilaian">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                        <span class="hidden sm:inline">Kembali ke Penilaian</span>
                        <span class="sm:hidden">Kembali</span>
                    </a>
                @else
                    <a href="{{ $finishUrl }}" class="inline-flex items-center gap-1.5 px-2 sm:px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-ink text-xs font-semibold transition cursor-pointer shrink-0" aria-label="Kembali ke Halaman Course">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                        <span class="hidden sm:inline">Kembali</span>
                    </a>
                @endif
                <div class="min-w-0 border-l border-line/60 pl-2 sm:pl-3 max-w-[140px] xs:max-w-[180px] sm:max-w-xs md:max-w-md">
                    <h1 class="truncate text-xs sm:text-sm font-bold text-ink leading-tight" title="{{ $item['title'] }}">{{ $item['title'] }}</h1>
                    <p class="truncate text-[10px] sm:text-xs text-muted">{{ $course['code'] }} · {{ $item['module'] }}</p>
                </div>
                @if(! $isLecturer)
                    @if($submission && $submission->submitted_at)
                        <div class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-100 border border-slate-200 text-slate-700 text-xs font-medium" title="Diserahkan pada {{ $submission->submitted_at->translatedFormat('d M Y, H:i') }}">
                            <svg class="h-3.5 w-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                            <span class="font-medium text-ink">Tersimpan di Database</span>
                        </div>
                    @endif
                    @if($studentScore !== null)
                        @php
                            $cpmkThreshold = 65.0;
                            if (isset($assessment) && $assessment && ($assessment->relationLoaded('cpmks') ? $assessment->cpmks->isNotEmpty() : $assessment->cpmks()->exists())) {
                                $cpmkThreshold = (float) $assessment->cpmks->avg('threshold');
                            } elseif (!empty($item['cpmk'])) {
                                $cpmkObj = \App\Models\Cpmk::where('code', $item['cpmk'])->first();
                                if ($cpmkObj && $cpmkObj->threshold !== null) {
                                    $cpmkThreshold = (float) $cpmkObj->threshold;
                                }
                            }
                            $maxPoints = (float)($item['points'] ?? 100);
                            $scorePct = $maxPoints > 0 ? (((float)$studentScore / $maxPoints) * 100) : (float)$studentScore;
                            $isScorePassed = $scorePct >= $cpmkThreshold;
                        @endphp
                        <div class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md {{ $isScorePassed ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-700' }} border text-xs font-semibold" title="Nilai Asesmen (Ambang Batas CPMK: {{ $cpmkThreshold }}%)">
                            <span class="font-normal opacity-80">Nilai:</span>
                            <span class="font-mono font-bold">{{ rtrim(rtrim(number_format((float)$studentScore, 2), '0'), '.') }}/100</span>
                        </div>
                    @endif
                @endif

            </div>

            {{-- Center: Tombol Daftar Bagian (Grid Popover Trigger) & Countdown Timer --}}
            <div class="flex items-center gap-2 ml-auto sm:ml-0">
                @if(!empty($item['duration_enabled']) && !$isMaterial && !$isLecturer && !$isSubmitted && !$isArchived)
                    <div id="code-timer-badge" class="flex items-center gap-1.5 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded text-xs font-mono font-bold text-slate-700">
                        <svg class="h-3.5 w-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <span id="code-countdown"
                              data-duration="{{ ((int)($item['duration_minutes'] ?? 60)) * 60 }}">00:00</span>
                    </div>
                @endif

                <button type="button" id="btn-open-material-modal" class="button-secondary text-xs py-1.5 px-3 font-bold flex items-center gap-1.5 bg-white hover:bg-slate-50 border-slate-300 text-slate-800 shadow-2xs cursor-pointer" title="Buka Daftar Bagian Materi">
                    <svg class="h-3.5 w-3.5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                    <span>Daftar Bagian (<span id="header-cur-step">1</span>/{{ $totalSteps }})</span>
                </button>
            </div>

            {{-- Right: Stepper Navigasi (Sebelumnya & Selanjutnya / Simpan dan Nilai / Serahkan / Selesaikan Course) --}}
            <div class="w-full sm:w-auto flex items-center justify-between sm:justify-end gap-2 pt-1 sm:pt-0">
                <button type="button" id="btn-step-prev" class="button-secondary text-xs py-1.5 px-3 font-semibold inline-flex items-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer" title="Bagian Sebelumnya" disabled>
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                    <span>Sebelumnya</span>
                </button>

                <div class="flex items-center gap-2">
                    @php
                        $isLecturerLastInitial = ($isLecturer && $totalSteps <= 1);
                    @endphp
                    <button type="button" id="btn-step-next" class="button-primary text-xs py-1.5 px-3.5 font-semibold inline-flex items-center gap-1.5 cursor-pointer shadow-xs {{ (!$isLecturer && $totalSteps <= 1) ? '!hidden' : '' }}" style="{{ (!$isLecturer && $totalSteps <= 1) ? 'display: none !important;' : 'display: inline-flex;' }}" title="{{ $isLecturerLastInitial ? 'Simpan dan Nilai' : 'Bagian Selanjutnya' }}">
                        <span id="btn-step-next-text">{{ $isLecturerLastInitial ? 'Simpan dan Nilai' : 'Selanjutnya' }}</span>
                        <span id="btn-step-next-icon" class="inline-flex items-center">
                            @if($isLecturerLastInitial)
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                            @else
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                            @endif
                        </span>
                    </button>

                    @if(!$isLecturer)
                        @if($isMaterial)
                            <a href="{{ $finishUrl }}" id="btn-step-finish" class="button-primary text-xs py-1.5 px-3.5 font-bold inline-flex items-center gap-1.5 shadow-xs cursor-pointer {{ $totalSteps > 1 ? '!hidden' : '' }}" style="{{ $totalSteps > 1 ? 'display: none !important;' : 'display: inline-flex;' }}" title="Kembali ke Kelas">
                                <span>Kembali ke Kelas</span>
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                            </a>
                        @elseif($isSubmitted)
                            <button type="button" disabled id="status-submitted-badge" class="button-primary text-xs py-1.5 px-3.5 font-bold inline-flex items-center gap-1.5 opacity-40 cursor-not-allowed select-none {{ $totalSteps > 1 ? '!hidden' : '' }}" style="{{ $totalSteps > 1 ? 'display: none !important;' : 'display: inline-flex;' }}" title="Tugas coding telah diserahkan dan terkunci">
                                <span>Sudah Diserahkan</span>
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                            </button>
                        @elseif($isArchived)
                            <div id="status-archived-badge" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 border border-slate-200 text-slate-600 text-xs font-medium select-none {{ $totalSteps > 1 ? '!hidden' : '' }}" style="{{ $totalSteps > 1 ? 'display: none !important;' : 'display: inline-flex;' }}" title="Kelas telah diarsipkan. Pengumpulan tugas ditutup.">
                                <span>Kelas Diarsipkan</span>
                            </div>
                        @else
                            <form data-code-submit method="post" action="{{ route('mahasiswa.course.submit', [$course['id'], $item['id']]) }}" id="form-code-submit" class="{{ $totalSteps > 1 ? '!hidden' : '' }}" style="{{ $totalSteps > 1 ? 'display: none !important;' : 'display: inline-block;' }}">
                                @csrf
                                <input type="hidden" name="from_code_editor" value="1">
                                <input type="hidden" name="answer" data-code-answer>
                                <button type="button" id="btn-submit-code-trigger" class="button-primary text-xs py-1.5 px-3.5 font-bold inline-flex items-center gap-1.5 shadow-xs cursor-pointer" title="Serahkan Tugas ke Database">
                                    <span>Serahkan</span>
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </header>

    <main class="w-full p-2.5 sm:p-4 md:p-5">
        @if(isset($errors) && $errors->any())
            <div role="alert" class="mb-4 rounded-lg border border-danger bg-white p-3 text-xs text-danger">{{ $errors->first() }}</div>
        @endif

        {{-- Mobile Segmented Control Tab Bar (Visible on mobile/tablet < 1280px) --}}
        <div id="mobile-workbench-tabs" class="xl:hidden flex items-center justify-between p-1 mb-2.5 bg-slate-200/80 rounded-xl border border-line/60 gap-1 text-xs select-none">
            {{-- 1. KIRI: Soal --}}
            <button type="button" data-mobile-tab="question" class="flex-1 py-2 px-2 rounded-lg font-bold text-center transition flex items-center justify-center gap-1.5 bg-white text-ink shadow-2xs cursor-pointer">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <span>Soal</span>
            </button>
            {{-- 2. TENGAH: Editor Kode --}}
            <button type="button" data-mobile-tab="editor" class="flex-1 py-2 px-2 rounded-lg font-semibold text-center transition flex items-center justify-center gap-1.5 text-slate-600 hover:text-ink cursor-pointer">
                <svg class="h-4 w-4 text-brand shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                <span>Editor Kode</span>
            </button>
            {{-- 3. KANAN: Penilaian / AI Asisten --}}
            @if($isLecturer && $reviewStudent && !$isMaterial)
                <button type="button" data-mobile-tab="grading" class="flex-1 py-2 px-2 rounded-lg font-semibold text-center transition flex items-center justify-center gap-1.5 text-slate-600 hover:text-ink cursor-pointer">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    <span>Penilaian</span>
                </button>
            @elseif($aiEnabled)
                <button type="button" data-mobile-tab="ai" class="flex-1 py-2 px-2 rounded-lg font-semibold text-center transition flex items-center justify-center gap-1.5 text-slate-600 hover:text-ink cursor-pointer">
                    <svg class="h-4 w-4 text-violet-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2 2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/><rect x="4" y="8" width="16" height="12" rx="2"/><circle cx="9" cy="13" r="1"/><circle cx="15" cy="13" r="1"/><path d="M10 17h4"/></svg>
                    <span>AI Asisten</span>
                </button>
            @endif
        </div>

        {{-- 3-Panel Workbench: Left (Soal & Instruksi), Center (Code Editor & Linux Terminal), Right (AI Asisten) --}}
        <div id="workbench-container" class="flex flex-col xl:flex-row items-stretch gap-3 xl:gap-0 h-[calc(100vh-85px)] min-h-[640px] relative">

            {{-- PANEL 1 (KIRI): Soal, Materi & Capaian Pembelajaran ("soalnya di kiri") --}}
            <section id="panel-question" class="surface flex flex-col shrink-0 h-full rounded-xl overflow-hidden shadow-sm border border-line/60 transition-none mobile-panel-active xl:flex" style="width: var(--workbench-left-width, 340px); min-width: 240px; max-width: 600px;" aria-labelledby="question-heading">
                {{-- Header Panel Kiri --}}
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span id="panel-step-badge" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-ink font-bold text-xs shadow-2xs">
                            1
                        </span>
                        <span class="text-xs font-semibold text-ink">
                            {{ $isMaterial ? 'Materi Pemrograman' : 'Praktikum Coding' }}
                        </span>
                    </div>
                    @if(!$isMaterial)
                        <div class="flex items-center gap-2">
                            <span id="panel-step-cpmk" class="font-mono text-xs font-semibold text-brand">
                                {{ $materialSteps[0]['cpmk'] ?? ($item['cpmk'] ?? 'CPMK-01') }}
                            </span>
                        </div>
                    @endif
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
                                <p class="whitespace-pre-line text-xs font-medium leading-relaxed text-ink">{{ $step['body'] ?? '' }}</p>
                            </div>

                            @if(!empty($step['attachment']))
                                @php
                                    $stepFile = \App\Models\Attachment::where('uuid', $step['attachment'])->first();
                                    $stepFileMeta = \App\Support\LearningPreview::fileMeta($step['attachment']);
                                    $stepMime = $stepFile?->mime ?? ($stepFileMeta['mime'] ?? '');
                                    $stepName = $stepFile?->name ?? ($stepFileMeta['name'] ?? 'Berkas Lampiran');
                                    $stepExt = strtolower(pathinfo($stepName, PATHINFO_EXTENSION) ?: (pathinfo($stepFileMeta['path'] ?? '', PATHINFO_EXTENSION) ?: ''));
                                    $isStepImage = str_starts_with($stepMime, 'image/') || in_array($stepExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                                    $isStepPdf = $stepMime === 'application/pdf' || $stepExt === 'pdf' || str_ends_with(strtolower($stepName), '.pdf');
                                    $stepUrl = route('preview.file', ['file' => $step['attachment'], 'inline' => ($isStepPdf || $isStepImage) ? 1 : null], false);
                                    $stepDownloadUrl = route('preview.file', ['file' => $step['attachment'], 'download' => 1], false);
                                @endphp
                                @if($isStepImage)
                                    <div class="rounded-lg border border-line/70 bg-white p-2.5 shadow-2xs space-y-2">
                                        <img class="max-h-48 w-full rounded-lg object-contain border border-line/40 bg-slate-50" src="{{ $stepUrl }}" alt="{{ $stepName }}">
                                        <div class="flex items-center justify-between text-xs pt-1">
                                            <span class="truncate font-medium text-ink" title="{{ $stepName }}">{{ $stepName }}</span>
                                            <a href="{{ $stepDownloadUrl }}" class="button-secondary text-[11px] py-1 px-2.5 font-semibold shrink-0">Unduh</a>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center justify-between gap-2 rounded-lg border border-line/70 bg-white p-2.5 shadow-2xs">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-line font-mono font-bold text-[10px] {{ $isStepPdf ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-700' }}">
                                                {{ strtoupper($stepExt ?: 'FILE') }}
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <span class="truncate block text-xs font-semibold text-ink" title="{{ $stepName }}">{{ $stepName }}</span>
                                                <span class="text-[10px] text-muted">Lampiran tahap pembelajaran</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <a href="{{ $stepUrl }}" target="_blank" rel="noopener" class="button-secondary text-[11px] py-1 px-2.5 font-semibold">Buka</a>
                                            <a href="{{ $stepDownloadUrl }}" class="button-secondary text-[11px] py-1 px-2.5 font-semibold">Unduh</a>
                                        </div>
                                    </div>
                                @endif
                            @endif

                            @if(!empty($step['link']))
                                @php
                                    $ytEmbed = \App\Support\LearningPreview::youtubeEmbedUrl($step['link']);
                                    $isYoutube = !empty($ytEmbed);
                                @endphp
                                <div class="rounded-lg border border-line/70 bg-white p-2.5 shadow-2xs flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        @if($isYoutube)
                                            <svg class="h-4 w-4 shrink-0 text-red-600" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                                            </svg>
                                        @else
                                            <svg class="h-4 w-4 shrink-0 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                                            </svg>
                                        @endif
                                        <span class="truncate text-xs font-medium text-ink" title="{{ $step['link'] }}">{{ preg_replace('#^https?://#', '', $step['link']) }}</span>
                                    </div>
                                    <a class="button-secondary text-[11px] py-1 px-2.5 font-semibold text-brand hover:underline shrink-0 inline-flex items-center gap-1" href="{{ $step['link'] }}" target="_blank" rel="noopener">
                                        <span>{{ $isYoutube ? 'Tonton Video' : 'Buka Tautan' }}</span>
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
                                    $imgUrl = route('preview.file', ['file' => $item['question_image'], 'inline' => 1], false);
                                    $imgDownloadUrl = route('preview.file', ['file' => $item['question_image'], 'download' => 1], false);
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
                                    $fileId = is_array($file) ? ($file['uuid'] ?? $file['id'] ?? $file['path'] ?? '') : (string) $file;
                                    $fileMeta = \App\Support\LearningPreview::fileMeta($file);
                                    $fileMime = $fileMeta['mime'] ?? '';
                                    $fileName = $fileMeta['name'] ?? (is_string($file) && !\Illuminate\Support\Str::isUuid($file) ? basename($file) : 'Berkas lampiran');
                                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION) ?: (pathinfo($fileMeta['path'] ?? '', PATHINFO_EXTENSION) ?: ''));
                                    if (empty($fileExt) && str_contains($fileMime, 'pdf')) {
                                        $fileExt = 'pdf';
                                    } elseif (empty($fileExt)) {
                                        $fileExt = 'file';
                                    }
                                    $isPdf = $fileMime === 'application/pdf' || $fileExt === 'pdf' || str_ends_with(strtolower($fileName), '.pdf');
                                    if ($isPdf) {
                                        $fileExt = 'pdf';
                                    }
                                    $isImage = str_starts_with($fileMime, 'image/') || in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                                    $fileUrl = route('preview.file', ['file' => $fileId, 'inline' => ($isPdf || $isImage) ? 1 : null], false);
                                    $fileDownloadUrl = route('preview.file', ['file' => $fileId, 'download' => 1], false);
                                @endphp
                                @if($isImage)
                                    <div class="rounded-lg border border-line/70 bg-white p-2.5 shadow-2xs space-y-2">
                                        <img src="{{ $fileUrl }}" alt="{{ $fileName }}" class="max-h-48 w-full object-contain rounded border border-line/40 bg-slate-50">
                                        <div class="flex items-center justify-between text-xs pt-1">
                                            <span class="truncate font-medium text-ink" title="{{ $fileName }}">{{ $fileName }}</span>
                                            <a href="{{ $fileDownloadUrl }}" class="button-secondary text-[11px] py-1 px-2.5 font-semibold shrink-0">Unduh</a>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center justify-between gap-2 rounded-lg border border-line/70 bg-white p-2.5 shadow-2xs">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded border border-line bg-canvas font-mono font-bold text-[10px] {{ $isPdf ? 'text-rose-700' : 'text-brand' }}">
                                                {{ $isPdf ? 'PDF' : 'FILE' }}
                                            </span>
                                            <span class="truncate text-xs font-medium text-ink" title="{{ $fileName }}">{{ $fileName }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="button-secondary text-[11px] py-1 px-2">Buka</a>
                                            <a href="{{ $fileDownloadUrl }}" class="button-secondary text-[11px] py-1 px-2">Unduh</a>
                                        </div>
                                    </div>
                                @endif
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
                    <button type="button" data-switch-to-editor class="xl:hidden button-primary text-xs py-1 px-3 font-semibold inline-flex items-center gap-1 shadow-xs cursor-pointer">
                        <span>Editor Kode</span>
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
            </section>

            {{-- Handle Geser Kiri (Soal <-> Editor) --}}
            <div data-resizer="left" class="hidden xl:flex w-3 shrink-0 cursor-col-resize items-center justify-center group relative z-10 select-none py-4 hover:bg-brand/5 active:bg-brand/10 transition-colors" title="Geser untuk mengatur lebar soal">
                <div class="w-1 h-12 rounded-full bg-slate-300 group-hover:bg-brand group-active:bg-brand group-hover:w-1.5 transition-all"></div>
            </div>

            {{-- PANEL 2 (TENGAH): Code Editor & Linux Sandbox Terminal --}}
            <div id="panel-editor" class="flex-1 min-w-0 xl:min-w-[320px] flex flex-col h-full overflow-hidden transition-none mobile-panel-hidden xl:flex">
                {{-- Editor Section --}}
                <section class="flex-1 flex flex-col min-h-0 rounded-xl bg-white shadow-sm border border-line/60 overflow-hidden" aria-labelledby="editor-heading">
                    {{-- Single Integrated Toolbar: Tab Berkas di kiri, Aksi & Terminal Toggle di kanan --}}
                    <div class="flex items-center justify-between border-b border-line/60 bg-slate-50 px-2.5 py-1.5 gap-2 select-none">
                        {{-- File Tabs (Kiri) --}}
                        <div data-file-tabs class="flex items-center gap-1 overflow-x-auto min-w-0 flex-1 scrollbar-none" role="tablist" aria-label="Berkas kode"></div>

                        {{-- Action Buttons (Kanan): Tanyakan Baris | Terminal (Icon) | Play (Icon) --}}
                        <div class="flex items-center gap-1.5 shrink-0 ml-auto">
                            <button type="button" data-insert-html5 hidden class="h-8 !min-h-0 px-2 sm:px-2.5 inline-flex items-center justify-center gap-1.5 rounded-lg border border-[#b9c0ca] bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition hover:border-ink shadow-2xs leading-none shrink-0" title="Sisipkan Template Dasar HTML5">
                                <svg class="h-3.5 w-3.5 text-amber-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4l2 16 6 2 6-2 2-16z"/></svg>
                                <span class="hidden sm:inline">+ Template HTML5</span>
                                <span class="sm:hidden">+ HTML5</span>
                            </button>
                            @if($aiEnabled)
                                <button type="button" data-mention-code disabled class="h-8 !min-h-0 px-2 sm:px-3 inline-flex items-center justify-center rounded-lg border border-[#b9c0ca] bg-white text-xs font-semibold text-ink transition hover:border-ink hover:bg-slate-50 disabled:opacity-40 shadow-2xs leading-none shrink-0" title="Tanyakan baris kode terpilih ke AI Asisten">
                                    <span class="hidden sm:inline">Tanyakan Baris</span>
                                    <span class="sm:hidden">Tanya</span>
                                </button>
                            @endif
                            <button type="button" data-terminal-toggle class="h-8 w-8 !p-0 !min-h-0 inline-flex items-center justify-center rounded-lg border border-[#b9c0ca] bg-white hover:bg-slate-50 text-slate-700 transition hover:border-ink shadow-2xs leading-none shrink-0" title="Buka / Tutup Terminal" aria-label="Terminal">
                                <svg class="h-3.5 w-3.5 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
                            </button>
                            <button type="button" disabled data-run-code class="h-8 w-8 !p-0 !min-h-0 inline-flex items-center justify-center rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs transition disabled:opacity-40 leading-none shrink-0" title="Jalankan kode" aria-label="Jalankan kode">
                                <svg class="h-3.5 w-3.5 fill-current text-white ml-0.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <polygon points="5 3 19 12 5 21 5 3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <textarea data-code-files-json class="hidden" aria-hidden="true">@json($defaultFiles)</textarea>
                    <div data-code-editor data-assignment-id="{{ $item['id'] }}" data-runtime-url="{{ asset('vendor/pyodide') }}/" data-code-language="{{ $language }}" data-has-duration="{{ (!empty($item['duration_enabled']) && !$isMaterial) ? '1' : '0' }}" data-max-files="5" data-max-file-chars="8000" data-max-total-chars="20000" @if($isLecturer) data-is-lecturer="1" @endif @if($isSubmitted) data-is-submitted="1" @endif @if($isEditorReadOnly) data-read-only="1" @endif class="code-editor flex-1 h-full overflow-auto bg-[#282c34] relative" aria-label="Editor kode {{ $language === 'web' ? 'HTML/CSS/JS' : 'Python' }}">
                        <div data-editor-loading class="flex items-center justify-center h-full text-slate-400 text-xs gap-2 py-12">
                            <svg class="animate-spin h-4 w-4 text-brand" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span>Memuat editor kode...</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3 py-1.5 bg-[#20242b] text-[11px] text-[#aeb8c4]">
                        <span data-code-save-status>{{ $isLecturer ? 'Mode Peninjauan Berkas Mahasiswa (Hanya Baca)' : ($isSubmitted ? 'Mode Baca Saja (Tugas Telah Diserahkan - Terkunci)' : (!empty($item['duration_enabled']) && !$isMaterial ? 'Draf tersimpan di browser' : 'Editor siap pakai')) }}</span>
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
                                <span class="text-xs font-mono text-slate-300 font-medium">{{ $language === 'web' ? 'Output Web / Pratinjau Browser' : 'Output Python / Terminal' }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 sm:gap-2">
                                <button type="button" data-stop-code hidden class="text-xs text-rose-300 px-2 py-1 font-semibold hover:bg-rose-950/40 rounded transition cursor-pointer">Hentikan</button>
                                <button type="button" data-clear-terminal class="text-xs font-mono text-slate-400 hover:text-white px-2 py-1 transition cursor-pointer">
                                    Bersihkan
                                </button>
                                <button type="button" data-terminal-fullscreen-toggle class="px-2 py-1 rounded text-slate-300 hover:text-white hover:bg-white/10 transition inline-flex items-center gap-1.5 text-xs font-mono cursor-pointer border border-white/10" title="Layar Penuh (Fullscreen)" aria-label="Layar Penuh">
                                    <svg data-icon-fullscreen class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                                    <svg data-icon-unfullscreen class="h-3.5 w-3.5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"/></svg>
                                    <span data-text-fullscreen class="hidden xs:inline">Layar Penuh</span>
                                </button>
                                <button type="button" data-terminal-close-btn class="p-1 rounded text-slate-400 hover:text-white hover:bg-white/10 transition cursor-pointer" title="Tutup Terminal">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
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

            @if($isLecturer && $reviewStudent && !$isMaterial)
            {{-- Handle Geser Kanan (Editor <-> Penilaian Dosen) --}}
            <div data-resizer="right" class="hidden xl:flex w-3 shrink-0 cursor-col-resize items-center justify-center group relative z-10 select-none py-4 hover:bg-brand/5 active:bg-brand/10 transition-colors" title="Geser untuk mengatur lebar panel penilaian">
                <div class="w-1 h-12 rounded-full bg-slate-300 group-hover:bg-brand group-active:bg-brand group-hover:w-1.5 transition-all"></div>
            </div>

            {{-- PANEL 3 (KANAN): Penilaian & Evaluasi Dosen Langsung di Editor --}}
            <aside id="panel-grading" class="surface flex flex-col shrink-0 h-full rounded-xl overflow-hidden shadow-sm border border-line/60 transition-none mobile-panel-hidden xl:flex" style="width: var(--workbench-right-width, 360px); min-width: 280px; max-width: 600px;" aria-labelledby="grading-heading">
                {{-- Header Panel Penilaian --}}
                <div class="p-4 border-b border-line/60 bg-white flex items-center justify-between shrink-0">
                    <div>
                        <h2 id="grading-heading" class="text-sm font-bold text-ink">Penilaian Tugas Coding</h2>
                        <p class="text-xs text-muted mt-0.5">Nilai langsung per butir soal</p>
                    </div>
                    @if($studentScore !== null)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ (!empty($isScorePassed)) ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' }} border" title="Ambang Batas CPMK: {{ $cpmkThreshold ?? 65 }}%">
                            Total: {{ rtrim(rtrim(number_format((float)$studentScore, 2), '0'), '.') }}/100
                        </span>
                    @endif
                </div>

                @if(session('notice'))
                    <div class="px-4 py-2 bg-emerald-50 border-b border-emerald-100 text-xs font-semibold text-emerald-800 flex items-center gap-1.5 shrink-0">
                        <svg class="h-3.5 w-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                        <span>{{ session('notice') }}</span>
                    </div>
                @endif

                {{-- Informasi Mahasiswa & Pengumpulan --}}
                <div class="px-4 py-3 bg-slate-50/70 border-b border-line/60 text-xs space-y-1 shrink-0">
                    <div class="flex items-center justify-between">
                        <span class="text-muted">Mahasiswa:</span>
                        <span class="font-semibold text-ink">{{ $reviewStudent->name }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted">NIM:</span>
                        <span class="font-mono text-ink">{{ $reviewStudent->nim_nidn ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted">Pengumpulan:</span>
                        <span class="font-medium text-ink">{{ $submission?->submitted_at?->translatedFormat('d M Y, H:i') ?? 'Belum ada pengumpulan' }}</span>
                    </div>
                    @if(!empty($completionTimeString))
                        <div class="flex items-center justify-between">
                            <span class="text-muted">Waktu Selesai:</span>
                            <span class="font-mono font-medium text-ink">{{ $completionTimeString }}</span>
                        </div>
                    @endif
                </div>

                {{-- Form Penilaian (Scrollable) --}}
                <form method="post" action="{{ $codingScoreUrl }}" class="flex-1 flex flex-col min-h-0" id="form-coding-grading">
                    @csrf
                    <input type="hidden" name="return_to" id="grading-return-to" value="list">

                    <div class="flex-1 overflow-y-auto p-4 space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-muted uppercase tracking-wider text-[11px]">Nilai Per Butir Soal</span>
                            <span class="text-[11px] text-muted">Maks. 100 Poin</span>
                        </div>

                        @foreach($codingStepsData as $s)
                            <div class="rounded-xl border border-slate-200 bg-white p-3 space-y-2.5 shadow-2xs">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-slate-100 text-slate-800 font-bold text-[10px] border border-slate-200">
                                            {{ $s['number'] }}
                                        </span>
                                        <span class="text-xs font-semibold text-ink truncate">{{ $s['title'] }}</span>
                                    </div>
                                    @if(!empty($s['cpmk']))
                                        <span class="font-mono text-[10px] font-semibold text-slate-600 px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 shrink-0">
                                            {{ $s['cpmk'] }}
                                        </span>
                                    @endif
                                </div>

                                <div class="flex items-center justify-between gap-3 pt-1 border-t border-slate-100">
                                    <button type="button" onclick="setMaterialStep({{ $s['number'] - 1 }})" class="text-[11px] font-medium text-slate-600 hover:text-ink hover:underline cursor-pointer inline-flex items-center gap-1">
                                        <span>Buka Kode Soal {{ $s['number'] }}</span>
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>

                                    <div class="flex items-center gap-1.5">
                                        <input type="number"
                                               name="scores[{{ $s['answer_id'] }}]"
                                               data-step-score
                                               data-max="{{ $s['max_points'] }}"
                                               min="0"
                                               max="{{ $s['max_points'] }}"
                                               step="0.1"
                                               value="{{ $s['current_score'] }}"
                                               placeholder="0"
                                               class="field w-16 text-right font-mono text-xs py-1 px-2 font-bold"
                                               aria-label="Nilai Soal {{ $s['number'] }}">
                                        <span class="text-xs text-muted font-medium">/ {{ $s['max_points'] }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Kalkulasi Total Otomatis --}}
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-700">Estimasi Total Nilai:</span>
                            <div class="flex items-center gap-1 font-mono font-bold text-ink text-sm">
                                <span id="grading-total-preview">{{ $studentScore !== null ? rtrim(rtrim(number_format((float)$studentScore, 2), '0'), '.') : '0' }}</span>
                                <span class="text-muted text-xs font-normal">/ 100</span>
                            </div>
                        </div>
                    </div>
                </form>
            </aside>
            @elseif($aiEnabled)
            {{-- Handle Geser Kanan (Editor <-> AI Asisten) --}}
            <div data-resizer="right" class="hidden xl:flex w-3 shrink-0 cursor-col-resize items-center justify-center group relative z-10 select-none py-4 hover:bg-brand/5 active:bg-brand/10 transition-colors" title="Geser untuk mengatur lebar AI Asisten">
                <div class="w-1 h-12 rounded-full bg-slate-300 group-hover:bg-brand group-active:bg-brand group-hover:w-1.5 transition-all"></div>
            </div>

            {{-- PANEL 3 (KANAN): AI Asisten ("ai assitennya di kanan") --}}
            <aside id="panel-ai" class="surface flex flex-col shrink-0 h-full rounded-xl overflow-hidden shadow-sm border border-line/60 transition-none mobile-panel-hidden xl:flex" style="width: var(--workbench-right-width, 340px); min-width: 260px; max-width: 600px;" aria-labelledby="assistant-heading">
                <div class="border-b border-line/60 px-4 py-3 bg-white flex items-center justify-between gap-2">
                    <h2 id="assistant-heading" class="text-sm font-bold text-ink">AI Asisten</h2>
                    <span data-ai-token-display class="text-xs text-slate-500 tabular-nums shrink-0" title="Sisa Kuota Token AI Akun Anda">Memuat token...</span>
                </div>

                @if (isset($errors) && $errors->has('ai'))
                    <p class="p-3 text-xs text-red-700">{{ $errors->first('ai') }}</p>
                @endif
                @guest
                    <form method="POST" action="{{ route('ai.login') }}" class="p-3 space-y-2.5 border-b border-line bg-canvas/30">
                        @csrf
                        <input type="hidden" name="assignment" value="{{ $item['id'] }}">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-ink">Akses Asisten AI</span>
                            <button type="button" onclick="document.querySelector('#ai-login-email').value='demo.ai@sale.test';document.querySelector('#ai-login-password').value='password123456';this.closest('form').submit();" class="text-xs font-bold text-brand hover:underline inline-flex items-center gap-1">
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
                <div data-ai-messages class="flex-1 space-y-3 overflow-y-auto p-4 text-xs leading-5 flex flex-col relative" aria-live="polite">
                    {{-- Teks Panduan di Tengah (Hilang saat sudah ada percakapan) --}}
                    <div data-ai-initial-message class="my-auto mx-auto max-w-[270px] text-center select-none pointer-events-none py-6 space-y-1.5">
                        <p class="text-xs font-semibold text-slate-700 tracking-tight">Konsultasi Pemrograman</p>
                        <p class="text-[11.5px] text-slate-500 leading-relaxed">
                            Tanyakan konsep logika, diskusikan kendala kode, atau sorot baris di editor lalu klik <span class="font-medium text-slate-700">Tanyakan Baris</span>.
                        </p>
                    </div>
                </div>

                {{-- Info Batas Penggunaan AI (Hanya tampil jika batas token mahasiswa habis) --}}
                <div data-ai-quota-bar hidden class="px-3 py-1.5 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-[11px] text-slate-600">
                    <div class="flex items-center gap-1.5 font-medium text-slate-700">
                        <svg class="h-3.5 w-3.5 text-slate-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <span data-ai-quota-message>Batas penggunaan AI harian telah habis.</span>
                    </div>
                    <span data-ai-reset-info class="text-slate-500 text-[10.5px]">Akan di-reset pukul 07.00 WIB</span>
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
                        <label for="assistant-message" class="sr-only">Pertanyaan untuk AI Asisten</label>
                        <textarea required maxlength="2000" id="assistant-message" rows="2" class="w-full bg-transparent border-0 p-2.5 pr-10 pb-7 text-xs text-ink placeholder:text-[#737b86] resize-none outline-none focus:outline-none focus:ring-0 leading-relaxed block" placeholder="Tanyakan petunjuk konsep kode..."></textarea>
                        <div class="absolute right-2 bottom-2 flex items-center gap-1.5 z-10">
                            <button type="button" data-ai-stop-btn class="h-7 w-7 rounded-lg bg-slate-900 hover:bg-slate-800 text-white hidden items-center justify-center shadow-xs transition cursor-pointer shrink-0" title="Hentikan balasan AI" aria-label="Hentikan balasan">
                                <svg class="h-3 w-3 fill-current" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="1.5"/></svg>
                            </button>
                            <button disabled type="submit" class="button-primary h-7 w-7 !p-0 !min-h-0 rounded-lg disabled:opacity-30 inline-flex items-center justify-center transition-all duration-150 transform scale-0 opacity-0 pointer-events-none shrink-0 shadow-xs" title="Kirim pertanyaan ke AI Asisten (Enter)" aria-label="Kirim pertanyaan">
                                <svg class="h-3.5 w-3.5 fill-current text-white -mr-0.5 -mt-0.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                </svg>
                            </button>
                        </div>
                        <span data-ai-status class="sr-only" aria-live="polite">Siap</span>
                    </div>
                </form>
            </aside>
            @endif

        </div>
    </main>

    {{-- MODAL POPOVER: DAFTAR BAGIAN MATERI --}}
    <dialog id="material-grid-modal" class="fixed inset-0 m-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl backdrop:bg-slate-900/50 max-w-lg w-[calc(100%-2rem)] h-fit max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Daftar Bagian Materi</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Pilih nomor bagian untuk langsung berpindah materi dan kode.</p>
            </div>
            <button type="button" id="modal-material-close" class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer" aria-label="Tutup">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="space-y-2 max-h-[50vh] overflow-y-auto p-1" id="material-steps-container">
            @foreach($materialSteps as $sIdx => $s)
                <button type="button"
                    data-grid-material-step="{{ $sIdx }}"
                    class="w-full text-left p-3 rounded-xl border text-xs font-medium transition flex items-center justify-between gap-3 cursor-pointer shadow-2xs {{ $sIdx === 0 ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white hover:border-slate-400 text-slate-700' }}">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md {{ $sIdx === 0 ? 'bg-white/20 text-white' : 'bg-slate-100 border border-slate-200 text-slate-800' }} font-bold text-xs" data-badge-icon>
                            {{ $sIdx + 1 }}
                        </span>
                        <span class="truncate font-semibold">{{ $s['title'] }}</span>
                    </div>
                    <span class="text-[11px] shrink-0 opacity-80">{{ $isMaterial ? ('Tahap ' . ($sIdx + 1)) : ($s['cpmk'] ?? 'Soal') }}</span>
                </button>
            @endforeach
        </div>

        <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-500">Total {{ $totalSteps }} Bagian Materi</span>
        </div>
    </dialog>

    @if(!$isLecturer && !$isSubmitted && !$isMaterial)
    {{-- MODAL KONFIRMASI PENGUMPULAN TUGAS CODING --}}
    <dialog id="coding-submit-confirm-modal" class="fixed inset-0 m-auto w-[calc(100%-2rem)] max-w-md overflow-hidden rounded-2xl border border-line bg-white p-0 text-ink shadow-2xl backdrop:bg-slate-900/50 h-fit max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-start gap-2.5 mb-2">
                <svg class="h-5 w-5 text-slate-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Serahkan Tugas Pemrograman?</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Konfirmasi pengumpulan berkas tugas ke dosen.</p>
                </div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed mb-3">
                Pastikan seluruh baris kode dan fungsi telah Anda periksa dan uji. Setelah tugas diserahkan, kode akan <strong>terkunci permanen</strong> dan tidak dapat dikerjakan atau diperbaiki lagi.
            </p>

            <p class="text-xs text-slate-500 mb-6">
                Total Soal: <span class="font-medium text-slate-700">{{ $totalSteps }} Bagian</span> &middot; Status: <span class="font-medium text-slate-700">Terkunci setelah dikirim</span>
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-4 border-t border-line/60">
                <button type="button" id="modal-coding-cancel-btn" class="button-secondary text-xs py-2.5 px-3 font-medium cursor-pointer justify-center text-center">Batal &amp; Periksa Lagi</button>
                <button type="button" id="modal-coding-confirm-btn" class="button-primary text-xs py-2.5 px-3 font-semibold cursor-pointer justify-center text-center">Ya, Serahkan Tugas</button>
            </div>
        </div>
    </dialog>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Mobile Segmented Tabs Navigation (< 1280px / Android / Mobile)
            const mobileTabs = document.querySelectorAll('[data-mobile-tab]');
            const panelQuestion = document.getElementById('panel-question');
            const panelEditor = document.getElementById('panel-editor');
            const panelAi = document.getElementById('panel-ai');
            const panelGrading = document.getElementById('panel-grading');

            window.switchMobileWorkbenchPanel = (tabName) => {
                if (window.innerWidth >= 1280) return;

                mobileTabs.forEach(btn => {
                    const isActive = btn.dataset.mobileTab === tabName;
                    if (isActive) {
                        btn.className = 'flex-1 py-2 px-2 rounded-lg font-bold text-center transition flex items-center justify-center gap-1.5 bg-white text-ink shadow-2xs cursor-pointer';
                    } else {
                        btn.className = 'flex-1 py-2 px-2 rounded-lg font-semibold text-center transition flex items-center justify-center gap-1.5 text-slate-600 hover:text-ink cursor-pointer';
                    }
                });

                const panels = [
                    { name: 'question', el: panelQuestion },
                    { name: 'editor', el: panelEditor },
                    { name: 'ai', el: panelAi },
                    { name: 'grading', el: panelGrading },
                ];

                panels.forEach(p => {
                    if (!p.el) return;
                    if (p.name === tabName) {
                        p.el.classList.remove('mobile-panel-hidden');
                        p.el.classList.add('mobile-panel-active');
                    } else {
                        p.el.classList.add('mobile-panel-hidden');
                        p.el.classList.remove('mobile-panel-active');
                    }
                });

                if (tabName === 'editor') {
                    window.dispatchEvent(new Event('resize'));
                    setTimeout(() => window.dispatchEvent(new Event('resize')), 50);
                    setTimeout(() => window.dispatchEvent(new Event('resize')), 200);
                } else {
                    if (window.exitTerminalFullscreen) {
                        window.exitTerminalFullscreen();
                    }
                }
            };

            mobileTabs.forEach(btn => {
                btn.addEventListener('click', () => {
                    window.switchMobileWorkbenchPanel(btn.dataset.mobileTab);
                });
            });

            document.querySelectorAll('[data-switch-to-editor]').forEach(btn => {
                btn.addEventListener('click', () => {
                    window.switchMobileWorkbenchPanel('editor');
                });
            });

            if (window.innerWidth < 1280) {
                window.switchMobileWorkbenchPanel('question');
            }

            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1280) {
                    [panelQuestion, panelEditor, panelAi, panelGrading].forEach(el => {
                        if (el) {
                            el.classList.remove('mobile-panel-hidden');
                        }
                    });
                }
            });

            const materialSteps = @json($materialSteps);
            const totalSteps = materialSteps.length;
            let currentStep = 0;

            const headerCurStep = document.getElementById('header-cur-step');
            const btnStepPrev = document.getElementById('btn-step-prev');
            const btnStepNext = document.getElementById('btn-step-next');
            const btnStepFinish = document.getElementById('btn-step-finish');
            const formCodeSubmit = document.getElementById('form-code-submit');
            const statusSubmittedBadge = document.getElementById('status-submitted-badge');

            const codingSubmitModal = document.getElementById('coding-submit-confirm-modal');
            const btnSubmitCodeTrigger = document.getElementById('btn-submit-code-trigger');
            const modalCodingCancelBtn = document.getElementById('modal-coding-cancel-btn');
            const modalCodingConfirmBtn = document.getElementById('modal-coding-confirm-btn');

            const panelStepBadge = document.getElementById('panel-step-badge');
            const panelStepCpmk = document.getElementById('panel-step-cpmk');
            const panelStepCounterBottom = document.getElementById('panel-step-counter-bottom');
            const materialCards = document.querySelectorAll('[data-material-card]');

            const gridModal = document.getElementById('material-grid-modal');
            const btnOpenMaterialModal = document.getElementById('btn-open-material-modal');
            const modalMaterialClose = document.getElementById('modal-material-close');
            const gridStepBtns = document.querySelectorAll('[data-grid-material-step]');
            const defaultFileName = '{{ $language === "web" ? "untitled.html" : "untitled" }}';
            const isMaterialItem = {{ ($isMaterial || (int)($item['id'] ?? 0) === 1) ? 'true' : 'false' }};
            const stepFilesMap = {};

            // Inisialisasi awal step 0 dengan defaultFiles yang dirender server
            stepFilesMap[0] = @json($defaultFiles);

            const savedStepFiles = @json($savedStepFiles ?? []);
            if (savedStepFiles && typeof savedStepFiles === 'object') {
                Object.keys(savedStepFiles).forEach(sIdx => {
                    if (savedStepFiles[sIdx] && savedStepFiles[sIdx].length > 0) {
                        stepFilesMap[Number(sIdx)] = savedStepFiles[sIdx];
                    }
                });
            }

            const setMaterialStep = (idx) => {
                if (idx < 0 || idx >= totalSteps) return;

                // 1. Simpan kode/berkas dari step aktif saat ini sebelum berpindah
                if (!{{ $isEditorReadOnly ? 'true' : 'false' }}) {
                    if (typeof window.getWorkbenchFiles === 'function') {
                        stepFilesMap[currentStep] = window.getWorkbenchFiles();
                    } else if (typeof window.getWorkbenchCode === 'function') {
                        const currentCode = window.getWorkbenchCode();
                        if (!stepFilesMap[currentStep] || !stepFilesMap[currentStep].length) {
                            stepFilesMap[currentStep] = [{ name: defaultFileName, code: currentCode }];
                        } else {
                            stepFilesMap[currentStep][0].code = currentCode;
                        }
                    }
                }

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

                // Update stepper button "Sebelumnya"
                if (btnStepPrev) btnStepPrev.disabled = currentStep === 0;

                {{-- Stepper button update di soal terakhir --}}
                const isLast = (currentStep === totalSteps - 1);
                const btnStepNextText = document.getElementById('btn-step-next-text');
                const btnStepNextIcon = document.getElementById('btn-step-next-icon');

                if (btnStepNext) {
                    if (isLast) {
                        @if($isLecturer)
                            btnStepNext.disabled = false;
                            btnStepNext.style.removeProperty('display');
                            btnStepNext.style.display = 'inline-flex';
                            btnStepNext.classList.remove('!hidden', 'hidden');
                            if (btnStepNextText) btnStepNextText.textContent = 'Simpan dan Nilai';
                            if (btnStepNextIcon) btnStepNextIcon.innerHTML = '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>';
                            btnStepNext.setAttribute('title', 'Simpan dan Nilai');
                        @else
                            btnStepNext.style.setProperty('display', 'none', 'important');
                            btnStepNext.classList.add('!hidden');
                        @endif
                    } else {
                        btnStepNext.disabled = false;
                        btnStepNext.style.removeProperty('display');
                        btnStepNext.style.display = 'inline-flex';
                        btnStepNext.classList.remove('!hidden', 'hidden');
                        @if($isLecturer)
                            if (btnStepNextText) btnStepNextText.textContent = 'Selanjutnya';
                            if (btnStepNextIcon) btnStepNextIcon.innerHTML = '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>';
                            btnStepNext.setAttribute('title', 'Bagian Selanjutnya');
                        @endif
                    }
                }
                if (btnStepFinish) {
                    if (isLast) {
                        btnStepFinish.style.removeProperty('display');
                        btnStepFinish.style.display = 'inline-flex';
                        btnStepFinish.classList.remove('!hidden', 'hidden');
                    } else {
                        btnStepFinish.style.setProperty('display', 'none', 'important');
                        btnStepFinish.classList.add('!hidden');
                    }
                }
                if (formCodeSubmit) {
                    if (isLast) {
                        formCodeSubmit.style.removeProperty('display');
                        formCodeSubmit.style.display = 'inline-block';
                        formCodeSubmit.classList.remove('!hidden', 'hidden');
                    } else {
                        formCodeSubmit.style.setProperty('display', 'none', 'important');
                        formCodeSubmit.classList.add('!hidden');
                    }
                }
                if (statusSubmittedBadge) {
                    if (isLast) {
                        statusSubmittedBadge.style.removeProperty('display');
                        statusSubmittedBadge.style.display = 'inline-flex';
                        statusSubmittedBadge.classList.remove('!hidden', 'hidden');
                    } else {
                        statusSubmittedBadge.style.setProperty('display', 'none', 'important');
                        statusSubmittedBadge.classList.add('!hidden');
                    }
                }

                // Update grid buttons style
                gridStepBtns.forEach((btn, i) => {
                    const isActive = (i === currentStep);
                    btn.className = `w-full text-left p-3 rounded-xl border text-xs font-medium transition flex items-center justify-between gap-3 cursor-pointer shadow-2xs ${isActive ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white hover:border-slate-400 text-slate-700'}`;
                    const badge = btn.querySelector('[data-badge-icon]');
                    if (badge) {
                        badge.className = `flex h-6 w-6 shrink-0 items-center justify-center rounded-md ${isActive ? 'bg-white/20 text-white' : 'bg-slate-100 border border-slate-200 text-slate-800'} font-bold text-xs`;
                    }
                });

                // Perbarui kode editor: jika beralih ke soal 2 dst yang belum pernah dikerjakan,
                // berikan paper baru dan berkas kosong (bukan mewarisi atau menampilkan kode html/css/python soal sebelumnya)
                let targetFiles = stepFilesMap[currentStep];
                if (!targetFiles) {
                    const stepDefinedCode = (materialSteps[currentStep] && typeof materialSteps[currentStep].code === 'string')
                        ? materialSteps[currentStep].code
                        : '';
                    const initialCode = (isMaterialItem && stepDefinedCode.trim() !== '') ? stepDefinedCode : (currentStep === 0 ? stepDefinedCode : '');
                    targetFiles = [{ name: defaultFileName, code: initialCode }];
                    stepFilesMap[currentStep] = targetFiles;
                }

                if (typeof window.setWorkbenchFiles === 'function') {
                    window.setWorkbenchFiles(targetFiles);
                } else if (typeof window.setWorkbenchCode === 'function') {
                    window.setWorkbenchCode(targetFiles[0]?.code ?? '');
                }
            };

            // Ekspos ke window agar tombol di panel penilaian dosen bisa mengarahkan ke soal tertentu
            window.setMaterialStep = setMaterialStep;

            // Kalkulator total skor otomatis untuk panel penilaian dosen
            const scoreInputs = document.querySelectorAll('[data-step-score]');
            const totalPreview = document.getElementById('grading-total-preview');
            if (scoreInputs.length > 0 && totalPreview) {
                const calcTotal = () => {
                    let total = 0;
                    scoreInputs.forEach(inp => {
                        const val = parseFloat(inp.value);
                        if (!isNaN(val)) total += val;
                    });
                    totalPreview.textContent = (Math.round(total * 100) / 100).toString();
                };
                scoreInputs.forEach(inp => inp.addEventListener('input', calcTotal));
            }

            // Kolektor berkas seluruh soal/bagian untuk dikirim ke database
            window.getAllWorkbenchFiles = () => {
                if (typeof window.getWorkbenchFiles === 'function') {
                    stepFilesMap[currentStep] = window.getWorkbenchFiles();
                } else if (typeof window.getWorkbenchCode === 'function') {
                    const curCode = window.getWorkbenchCode();
                    if (stepFilesMap[currentStep] && stepFilesMap[currentStep].length) {
                        stepFilesMap[currentStep][0].code = curCode;
                    }
                }
                const allFiles = [];
                const usedNames = new Set();
                Object.keys(stepFilesMap).sort((a, b) => Number(a) - Number(b)).forEach(sIdx => {
                    const fList = stepFilesMap[sIdx] || [];
                    const stepNum = Number(sIdx) + 1;
                    const stepTitle = (materialSteps[sIdx] && materialSteps[sIdx].title) ? materialSteps[sIdx].title : `Soal ${stepNum}`;
                    const stepCpmk = (materialSteps[sIdx] && materialSteps[sIdx].cpmk) ? materialSteps[sIdx].cpmk : '';
                    fList.forEach(f => {
                        let fname = f.name;
                        if (totalSteps > 1) {
                            const parts = fname.split('.');
                            const ext = parts.pop();
                            fname = `${parts.join('.')}_soal_${stepNum}.${ext}`;
                        }
                        usedNames.add(fname);
                        allFiles.push({
                            name: fname,
                            code: f.code ?? '',
                            step: stepNum,
                            step_title: stepTitle,
                            cpmk: stepCpmk,
                        });
                    });
                });
                return allFiles;
            };

            // Konfirmasi pengumpulan berkas tugas koding
            btnSubmitCodeTrigger?.addEventListener('click', () => {
                codingSubmitModal?.showModal();
            });

            modalCodingCancelBtn?.addEventListener('click', () => {
                codingSubmitModal?.close();
            });

            codingSubmitModal?.addEventListener('click', (e) => {
                if (e.target === codingSubmitModal) codingSubmitModal.close();
            });

            modalCodingConfirmBtn?.addEventListener('click', () => {
                modalCodingConfirmBtn.disabled = true;
                modalCodingConfirmBtn.textContent = 'Menyerahkan...';
                localStorage.removeItem(`sale.code.deadline.{{ $item['id'] }}`);
                localStorage.removeItem(`sale.code.assignment.{{ $item['id'] }}.{{ $language }}`);
                const allFiles = window.getAllWorkbenchFiles();
                const ansInput = document.querySelector('[data-code-answer]');
                if (ansInput && allFiles.length > 0) {
                    ansInput.value = JSON.stringify(allFiles);
                }
                formCodeSubmit?.submit();
            });

            btnStepPrev?.addEventListener('click', () => setMaterialStep(currentStep - 1));
            btnStepNext?.addEventListener('click', () => {
                @if($isLecturer)
                    const isLast = (currentStep === totalSteps - 1);
                    if (isLast) {
                        const gradingForm = document.getElementById('form-coding-grading');
                        if (gradingForm) {
                            if (typeof gradingForm.requestSubmit === 'function') {
                                gradingForm.requestSubmit();
                            } else {
                                gradingForm.submit();
                            }
                        }
                        return;
                    }
                @endif
                setMaterialStep(currentStep + 1);
            });

            btnOpenMaterialModal?.addEventListener('click', () => gridModal?.showModal());
            modalMaterialClose?.addEventListener('click', () => gridModal?.close());
            gridModal?.addEventListener('click', (e) => { if (e.target === gridModal) gridModal.close(); });

            gridStepBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const targetStep = Number(btn.dataset.gridMaterialStep);
                    setMaterialStep(targetStep);
                    gridModal?.close();
                });
            });

            // Countdown timer jika batas durasi waktu diaktifkan
            const codeTimerEl = document.getElementById('code-countdown');
            if (codeTimerEl && !isMaterialItem && !{{ $isLecturer ? 'true' : 'false' }} && !{{ $isSubmitted ? 'true' : 'false' }} && !{{ $isArchived ? 'true' : 'false' }}) {
                const totalDurationSeconds = Number(codeTimerEl.dataset.duration || 3600);
                const deadlineKey = `sale.code.deadline.{{ $item['id'] }}`;
                const now = Date.now();
                let deadline = localStorage.getItem(deadlineKey);

                if (!deadline || isNaN(Number(deadline))) {
                    deadline = now + (totalDurationSeconds * 1000);
                    localStorage.setItem(deadlineKey, String(deadline));
                } else {
                    deadline = Number(deadline);
                }

                const formatTime = (seconds) => {
                    const m = Math.floor(seconds / 60);
                    const s = seconds % 60;
                    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
                };

                let hasTriggeredEnd = false;

                const updateCodeTimer = async () => {
                    const currentNow = Date.now();
                    const remainingSeconds = Math.max(0, Math.floor((deadline - currentNow) / 1000));
                    codeTimerEl.textContent = formatTime(remainingSeconds);

                    if (remainingSeconds <= 300) {
                        const badge = document.getElementById('code-timer-badge');
                        if (badge) {
                            badge.classList.add('text-danger', 'font-bold');
                        }
                    }

                    if (remainingSeconds <= 0) {
                        clearInterval(codeTimerInterval);
                        localStorage.removeItem(deadlineKey);
                        localStorage.removeItem(`sale.code.assignment.{{ $item['id'] }}.{{ $language }}`);
                        if (!hasTriggeredEnd) {
                            hasTriggeredEnd = true;
                            if (typeof window.saleNotice === 'function') {
                                await window.saleNotice({ title: 'Waktu pengerjaan berakhir', message: 'Jawaban kode Anda akan otomatis dikumpulkan.' });
                            } else {
                                alert('Waktu pengerjaan berakhir. Jawaban kode Anda akan otomatis dikumpulkan.');
                            }
                            const allFiles = window.getAllWorkbenchFiles();
                            const ansInput = document.querySelector('[data-code-answer]');
                            if (ansInput && allFiles.length > 0) {
                                ansInput.value = JSON.stringify(allFiles);
                            }
                            formCodeSubmit?.submit();
                        }
                    }
                };

                updateCodeTimer();
                const codeTimerInterval = setInterval(updateCodeTimer, 1000);
            }
        });
    </script>
</body>
</html>

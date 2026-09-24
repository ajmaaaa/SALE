@extends('layouts.mahasiswa')

@section('title', request()->has('room') || request()->has('course') ? 'Ruang Penilaian Kelas | SALE' : 'Kelas yang Saya Ajar | SALE')
@section('header', request()->has('room') || request()->has('course') ? 'Ruang Penilaian Kelas' : 'Penilaian Kelas')

@section('content')
<div class="space-y-6">

    {{-- Toast Notification --}}
    <div id="sync-toast" hidden class="surface p-4 border-l-4 border-brand text-xs text-ink shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="font-bold text-brand">✓ Perubahan Tersimpan:</span>
                <span class="text-muted" id="toast-message">Data nilai dan capaian CPMK kelas telah diperbarui.</span>
            </div>
            <button type="button" onclick="document.getElementById('sync-toast').setAttribute('hidden', '')" class="text-xs text-muted hover:text-ink">Tutup</button>
        </div>
    </div>

    @if(request()->has('room') || request()->has('course'))
        {{-- ── RUANG PENILAIAN ── --}}
        @php
            $typeParam = strtolower(request()->query('type', 'uts'));
            $typeMap = [
                'kuis' => 'quiz',
                'quiz' => 'quiz',
                'proyek' => 'project',
                'project' => 'project',
                'pbl' => 'project',
                'case' => 'project',
                'coding' => 'tugas',
                'tugas' => 'tugas',
                'uts' => 'uts',
                'uas' => 'uas',
            ];
            $activeType = $typeMap[$typeParam] ?? 'uts';
            $courseId   = request()->integer('course', 1);
            $courses    = \App\Support\LearningPreview::courses();
            $selectedCourse = $courses[$courseId] ?? $courses[1];
        @endphp

        @include('dosen.partials.grades-room-header')

        @if(view()->exists('dosen.partials.assessment-' . $activeType))
            @include('dosen.partials.assessment-' . $activeType)
        @else
            @include('dosen.partials.assessment-uts')
        @endif
        @include('dosen.partials.submission-preview')
        @include('dosen.partials.grade-import')

    @else
        {{-- ── DAFTAR KELAS ── --}}
        @include('dosen.partials.grades-class-list')
    @endif

</div>

<script>
    function filterClasses() {
        const search = document.getElementById('class-search')?.value.toLowerCase().trim() ?? '';
        const status = document.getElementById('status-filter')?.value ?? 'all';
        let count = 0;
        document.querySelectorAll('.class-row').forEach(row => {
            const show = (!search || row.dataset.name.includes(search) || row.dataset.code.includes(search))
                      && (status === 'all' || row.dataset.status === status);
            row.style.display = show ? '' : 'none';
            if (show) count++;
        });
        const el = document.getElementById('class-count-text');
        if (el) el.innerHTML = `Menampilkan <strong class="text-ink">${count}</strong> dari <strong class="text-ink">${document.querySelectorAll('.class-row').length}</strong> kelas aktif`;
    }

    function filterStudents() {
        const search = document.getElementById('student-search')?.value.toLowerCase().trim() ?? '';
        const status = document.getElementById('student-status-filter')?.value ?? 'all';
        let count = 0;
        document.querySelectorAll('.student-row').forEach(row => {
            const show = (!search || row.dataset.name.includes(search) || row.dataset.number.includes(search))
                      && (status === 'all' || row.dataset.status === status);
            row.style.display = show ? '' : 'none';
            if (show) count++;
        });
        const el = document.getElementById('student-count-text');
        if (el) el.innerHTML = `Menampilkan <strong class="text-ink">${count}</strong> dari <strong class="text-ink">${document.querySelectorAll('.student-row').length}</strong> mahasiswa terdaftar`;
    }

    function triggerOBESync(msg) {
        const toast = document.getElementById('sync-toast');
        const label = document.getElementById('toast-message');
        if (!toast) return;
        if (label && msg) label.textContent = msg;
        toast.removeAttribute('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => toast.setAttribute('hidden', ''), 4000);
    }
</script>
@endsection

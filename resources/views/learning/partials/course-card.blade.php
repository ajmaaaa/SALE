@php
    $contents = collect(\App\Support\LearningPreview::items())->where('course', $course['id']);
    $next = $contents->whereIn('type', ['tugas', 'coding', 'kuis'])->sortBy('due')->first();
    $sks = $course['sks'] ?? '3 SKS';
    $modules = $course['modules'] ?? ($contents->where('type', '!=', 'pengumuman')->pluck('module')->unique()->count() . ' modul');
    $tasks = $course['tasks'] ?? ($contents->whereIn('type', ['tugas', 'coding', 'kuis'])->count() . ' pekerjaan');
    $type = $course['type'] ?? ($next ? \App\Support\LearningPreview::labels()[$next['type']] : 'Materi kelas');
    $work = $course['work'] ?? ($next['title'] ?? 'Belum ada tugas aktif');
    $due = $course['due'] ?? (!empty($next['due']) ? \Carbon\Carbon::parse($next['due'])->translatedFormat('d M, H:i') : '');
    $role = $role ?? (request()->is('dosen*') ? 'dosen' : 'mahasiswa');
    $targetUrl = route($role . '.course.show', $course['id']);
@endphp

<a href="{{ $targetUrl }}" class="group flex min-h-64 flex-col overflow-hidden rounded-xl bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md border border-line/60">
    @if(!empty($course['cover']))
        <img src="{{ route('preview.file', $course['cover']) }}" alt="Sampul {{ $course['title'] }}" class="h-36 w-full object-cover">
        <div class="px-5 pt-4">
            <p class="text-xs font-semibold text-brand">{{ $course['code'] }}</p>
            <h2 class="mt-2 text-lg font-semibold leading-tight text-ink group-hover:text-brand transition">{{ $course['title'] }}</h2>
            <p class="mt-1 text-xs text-muted">{{ $course['lecturer'] }}</p>
        </div>
    @else
        <div class="relative min-h-32 overflow-hidden bg-brand-dark px-5 py-5 text-white">
            @if($course['id'] === 1)
                <svg class="absolute -right-3 -top-3 h-36 w-36 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="60" cy="22" r="10"/><circle cx="31" cy="64" r="10"/><circle cx="89" cy="64" r="10"/><circle cx="17" cy="101" r="8"/><circle cx="47" cy="101" r="8"/><circle cx="75" cy="101" r="8"/><circle cx="104" cy="101" r="8"/><path d="M54 30 36 55M66 30l18 25M27 74l-7 19M35 74l9 19M85 74l-8 19M93 74l8 19"/></svg>
            @elseif($course['id'] === 2)
                <svg class="absolute -right-3 -top-2 h-36 w-36 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="13" y="17" width="94" height="74" rx="7"/><path d="M13 35h94M27 26h1M36 26h1M45 26h1M76 51 54 74l14 3 5 15 10-4-6-14 14-4z"/></svg>
            @elseif($course['id'] === 3)
                <svg class="absolute -right-3 -top-3 h-36 w-36 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="60" cy="60" r="13"/><circle cx="22" cy="28" r="8"/><circle cx="98" cy="26" r="8"/><circle cx="18" cy="93" r="8"/><circle cx="101" cy="94" r="8"/><path d="m29 34 21 18M91 32 70 52M26 88l24-19M94 88 70 69"/></svg>
            @else
                <svg class="absolute -right-2 -top-2 h-36 w-36 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="15" y="20" width="34" height="22" rx="4"/><rect x="70" y="20" width="34" height="22" rx="4"/><rect x="43" y="79" width="34" height="22" rx="4"/><path d="M49 31h21M32 42v24h28v13M87 42v24H60"/></svg>
            @endif
            <div class="relative z-10">
                <div class="flex items-center gap-3 text-xs font-semibold">
                    <span>{{ $course['code'] }}</span>
                    <span>{{ $sks }}</span>
                </div>
                <h2 class="mt-3 text-base sm:text-lg font-semibold leading-snug text-white group-hover:text-slate-100 transition">{{ $course['title'] }}</h2>
                <p class="mt-1 text-xs text-white/90">{{ $course['lecturer'] }}</p>
            </div>
        </div>
    @endif
    <div class="flex flex-1 flex-col px-5 py-4">
        <p class="text-xs font-semibold text-brand">{{ $type }}</p>
        <p class="mt-1 text-sm font-medium text-ink">{{ $work }}</p>
        @if($due)
            <p class="mt-2 text-xs font-medium {{ ($isFirst ?? false) ? 'text-danger' : 'text-muted' }}">{{ $due }}</p>
        @endif
        <div class="mt-auto flex gap-4 pt-5 text-xs font-medium text-muted">
            <span>{{ $modules }}</span>
            <span>{{ $tasks }}</span>
        </div>
    </div>
</a>

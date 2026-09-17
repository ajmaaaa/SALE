<div class="assessment-sheet">
    {{-- Header & Assessment Selector --}}
    <section class="surface assessment-overview p-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <form method="get" action="{{ route('dosen.grades') }}" class="min-w-0 flex-1">
                <input type="hidden" name="course" value="{{ $courseId }}">
                <input type="hidden" name="type" value="{{ $activeType }}">
                <label for="assessment-choice" class="mb-2 block text-xs font-semibold text-muted">Pilih {{ $component['name'] ?? strtoupper($activeType) }} / Penilaian</label>
                <div class="flex gap-2 max-w-md">
                    <select id="assessment-choice" name="assessment" class="field text-sm font-medium" onchange="this.form.submit()" @disabled(!$assessments)>
                        @forelse($assessments as $assessment)
                            @php
                                $cpmkCodes = array_column($assessment['cpmk'], 'code');
                            @endphp
                            @if(!empty($cpmkCodes))
                                <option value="{{ $assessment['id'] }}" @selected(($selectedAssessment['id'] ?? null) === $assessment['id'])>
                                    {{ $assessment['title'] }} ({{ implode(', ', $cpmkCodes) }})
                                </option>
                            @endif
                        @empty
                            <option>Belum ada penilaian dengan pemetaan CPMK</option>
                        @endforelse
                    </select>
                </div>
            </form>
            <a class="button-secondary text-xs" href="{{ route('dosen.item.create', ['course'=>$courseId, 'type'=>$activeType === 'quiz' ? 'kuis' : 'tugas', 'component'=>\App\Support\AcademicPreview::component(['component'=>$activeType])]) }}">Tambah Penilaian</a>
        </div>

        @if($selectedAssessment)
            @php
                $cpmks = array_column($selectedAssessment['cpmk'], 'code');
            @endphp
            <div class="assessment-overview-layout mt-5 pt-4 border-t border-line flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="mb-1.5 flex flex-wrap items-center gap-2 text-xs">
                        <span class="status bg-brand-soft text-brand font-semibold">{{ $component['name'] ?? strtoupper($activeType) }}</span>
                        <span class="text-muted">Bobot Komponen: {{ number_format($selectedAssessment['course_weight'], 2, ',', '.') }}% dari Nilai Mata Kuliah</span>
                        @if(!empty($cpmks))
                            <span class="inline-block text-[11px] font-semibold text-brand bg-brand-soft px-2 py-0.5 rounded">
                                {{ implode(', ', $cpmks) }}
                            </span>
                        @endif
                    </div>
                    <h2 class="text-xl font-bold text-ink">{{ $selectedAssessment['title'] }}</h2>
                    <p class="mt-1 text-xs text-muted">Input nilai per CPMK ({{ implode(', ', $cpmks) }}) per mahasiswa (skala 0–100). Nilai akhir dihitung otomatis.</p>
                </div>
            </div>
        @endif
    </section>

    @if(!$selectedAssessment)
        <section class="surface p-8 text-center mt-5">
            <h2 class="text-lg font-semibold">Belum ada {{ $component['name'] ?? strtoupper($activeType) }} dengan pemetaan CPMK untuk mata kuliah ini</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm text-muted">Klik tombol "Tambah Penilaian" di atas dan pastikan memilih target CPMK.</p>
        </section>
    @else
        @php
            $schema = array_map(function($question, $index) {
                return [
                    'index' => $index,
                    'header' => 'NILAI_'.str_replace('-', '_', $question['cpmk']),
                    'label' => 'Nilai '.$question['cpmk'],
                    'cpmk' => $question['cpmk'],
                    'max' => $question['points'],
                    'weight' => $question['weight'] ?? 100,
                    'course_weight' => $question['course_weight'] ?? 0,
                ];
            }, $selectedAssessment['questions'], array_keys($selectedAssessment['questions']));
        @endphp

        <form method="post" action="{{ route('dosen.assessment.save', [$courseId, $selectedAssessment['id']]) }}" class="assessment-sheet" data-assessment-editor data-assessment-id="{{ $selectedAssessment['id'] }}" data-schema="{{ json_encode($schema) }}" data-outcomes="{{ json_encode($selectedAssessment['cpmk']) }}">
            @csrf
            <input type="hidden" name="schema_token" value="{{ $selectedAssessment['schema_token'] }}">

            <div class="surface assessment-toolbar flex flex-wrap items-center justify-between gap-3 p-4">
                <div class="assessment-filters flex flex-1 flex-wrap items-center gap-3">
                    <div class="relative flex-1 max-w-xs">
                        <input id="student-search" class="field text-xs w-full" placeholder="Cari nama atau NIM..." aria-label="Cari mahasiswa">
                    </div>
                    <select id="statusfilter" class="field text-xs" aria-label="Status penilaian">
                        <option value="all">Semua status</option>
                        <option value="pending">Belum lengkap</option>
                        <option value="memenuhi">Memenuhi target</option>
                        <option value="belum">Belum memenuhi</option>
                    </select>
                </div>
                <div class="assessment-toolbar-actions flex flex-wrap gap-2">
                    <button type="button" class="button-secondary text-xs" data-grade-import-open aria-haspopup="dialog" aria-controls="grade-import">Unggah Nilai CSV/Excel</button>
                    <button type="button" class="button-secondary text-xs" data-grade-export>Ekspor CSV</button>
                </div>
            </div>

            <div class="surface assessment-table-panel overflow-hidden mt-3">
                <div class="overflow-x-auto">
                    <table class="admin-table assessment-table assessment-input-table" id="students-table">
                        <thead>
                            <tr>
                                <th scope="col" class="assessment-student-column min-w-[220px]">Mahasiswa</th>
                                @foreach($selectedAssessment['questions'] as $index => $question)
                                    <th scope="col" class="text-center min-w-[150px]">
                                        <span class="font-bold text-ink block text-xs">Nilai {{ $question['cpmk'] }}</span>
                                        <span class="mt-0.5 inline-block text-[11px] font-semibold text-brand bg-brand-soft px-2 py-0.5 rounded">
                                            Bobot: {{ number_format($question['course_weight'] ?? 0, 1, ',', '.') }}% MK
                                        </span>
                                        <span class="mt-0.5 block text-[10px] text-muted">0–100</span>
                                    </th>
                                @endforeach
                                <th scope="col" class="text-center min-w-[140px]">
                                    <span class="font-bold text-ink block text-xs">Nilai {{ $selectedAssessment['title'] }}</span>
                                    <span class="mt-0.5 inline-block text-[11px] font-semibold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded">
                                        Total: {{ number_format($selectedAssessment['course_weight'], 1, ',', '.') }}% MK
                                    </span>
                                    <span class="mt-0.5 block text-[10px] text-muted">0–100</span>
                                </th>
                                <th scope="col" class="min-w-[170px]">Waktu Pengumpulan</th>
                                <th scope="col" class="text-center min-w-[110px]">Aksi Dokumen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                @php
                                    $record = $studentAssessments[$student['id']];
                                    $submission = \App\Support\LearningPreview::submission($selectedAssessment['id'], $student['id']);
                                    $stu = $student;
                                @endphp
                                <tr class="student-row" data-number="{{ $student['number'] }}" data-student-id="{{ $student['id'] }}" data-name="{{ strtolower($student['name']) }}" data-status="{{ !$record['complete'] ? 'pending' : (in_array(false, array_column($record['cpmk'], 'passed'), true) ? 'belum' : 'memenuhi') }}">
                                    <td class="assessment-student-column">
                                        <p class="font-semibold text-ink">{{ $student['name'] }}</p>
                                        <p class="mt-0.5 font-mono text-xs text-muted">{{ $student['number'] }}</p>
                                    </td>
                                    @foreach($selectedAssessment['questions'] as $index => $question)
                                        @php
                                            $val = $record['questions'][$index]['earned'] ?? null;
                                            $qCourseWeight = $question['course_weight'] ?? 0;
                                        @endphp
                                        <td class="text-center">
                                            <div class="inline-block">
                                                <input type="number" min="0" max="100" step="any"
                                                       name="grades[{{ $student['id'] }}][{{ $index }}]"
                                                       value="{{ old('grades.'.$student['id'].'.'.$index, $val) }}"
                                                       data-question-index="{{ $index }}"
                                                       data-mk-weight="{{ $qCourseWeight }}"
                                                       class="field font-mono text-xs text-center w-24"
                                                       placeholder="0–100"
                                                       aria-label="{{ $student['name'].' - Nilai '.$question['cpmk'].' (0-100)' }}">
                                                <span class="cpmk-contrib block text-[10px] text-muted font-mono mt-0.5">
                                                    Kontribusi: <strong class="text-brand">{{ $val !== null ? number_format(($val / 100) * $qCourseWeight, 2, ',', '.') : '0,00' }}%</strong> MK
                                                </span>
                                            </div>
                                        </td>
                                    @endforeach
                                    <td class="text-center">
                                        <span class="font-mono text-sm font-bold text-ink" data-assessment-score>{{ $record['score'] ?? '-' }}</span>
                                        <span class="block text-[10px] text-muted font-mono" data-assessment-course-contrib data-total-course-weight="{{ $selectedAssessment['course_weight'] }}">
                                            @if($record['score'] !== null)
                                                {{ number_format(($record['score'] / 100) * $selectedAssessment['course_weight'], 2, ',', '.') }}% / {{ number_format($selectedAssessment['course_weight'], 1, ',', '.') }}% MK
                                            @else
                                                - / {{ number_format($selectedAssessment['course_weight'], 1, ',', '.') }}% MK
                                            @endif
                                        </span>
                                        <p class="mt-0.5 text-[11px]" data-assessment-state>
                                            @if(!$record['complete'])
                                                <span class="text-muted">Belum lengkap</span>
                                            @elseif(in_array(false, array_column($record['cpmk'], 'passed'), true))
                                                <span class="text-danger font-semibold">Belum memenuhi</span>
                                            @else
                                                <span class="text-emerald-700 font-semibold">Memenuhi target</span>
                                            @endif
                                        </p>
                                    </td>
                                    <td class="whitespace-nowrap text-xs text-muted">{{ $submission['time'] ?? 'Belum tersedia' }}</td>
                                    <td class="text-center">@include('dosen.partials.submission-button')</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="assessment-count text-xs text-muted p-4 border-t border-line" id="counttext">{{ count($students) }} mahasiswa</div>
            </div>

            <div class="surface assessment-savebar flex flex-wrap items-center justify-between gap-4 p-4 mt-4">
                <div class="max-w-xl text-xs leading-relaxed text-muted">
                    <p class="font-semibold text-ink">Input Nilai {{ $selectedAssessment['title'] }}</p>
                    <p>Masukkan nilai untuk masing-masing CPMK ({{ implode(', ', $cpmks) }}). Rata-rata nilai penilaian dan capaian pada Rekap CPMK dihitung secara real-time.</p>
                </div>
                <div class="assessment-save-actions flex flex-col items-end gap-2">
                    <span hidden data-grading-dirty class="text-xs text-amber-600 font-semibold">Ada perubahan yang belum disimpan</span>
                    <div class="flex gap-3">
                        <a class="button-secondary text-xs" href="{{ route('dosen.gradebook', ['course'=>$courseId]) }}">Lihat Rekap CPMK</a>
                        <button class="button-primary text-xs" @disabled(!$selectedAssessment['mapping_valid'] || !$students)>Simpan Nilai</button>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>

<section class="surface p-6 rounded-2xl border border-line/70 shadow-xs space-y-4">
    <div class="border-b border-line/50 pb-3">
        <h2 class="text-base font-bold text-ink">Telusuri Penilaian</h2>
        <p class="mt-0.5 text-xs text-muted">Pilih jenis penilaian dan tugas/ujian untuk melihat kontribusi soal ke ketercapaian CPMK.</p>
    </div>
    <form method="get" action="{{ route('dosen.gradebook') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto] items-end">
        <input type="hidden" name="course" value="{{ $course['id'] }}">
        <div class="space-y-1.5">
            <label for="filter-component" class="form-label text-xs font-semibold text-muted mb-0">Jenis Penilaian</label>
            <select id="filter-component" name="component" class="field text-xs py-2 font-medium bg-white shadow-2xs" onchange="this.form.elements.assessment.value='0'; this.form.submit()">
                <option value="">Semua komponen (rekap kelas)</option>
                @foreach($config['components'] as $component)
                    <option value="{{ $component['code'] }}" @selected($componentFilter === $component['code'])>{{ $component['name'] }} ({{ $component['weight'] }}%)</option>
                @endforeach
            </select>
        </div>
        <div class="space-y-1.5">
            <label for="filter-assessment" class="form-label text-xs font-semibold text-muted mb-0">Tugas / Ujian</label>
            <select id="filter-assessment" name="assessment" class="field text-xs py-2 font-medium bg-white shadow-2xs">
                <option value="0">Semua penilaian pada jenis ini</option>
                @foreach($assessments as $assessment)
                    <option value="{{ $assessment['id'] }}" @selected($assessmentFilter === $assessment['id'])>{{ $assessment['title'] }}</option>
                @endforeach
            </select>
        </div>
        <button class="button-primary text-xs py-2.5 px-6 font-bold shadow-2xs h-[42px] shrink-0 w-full sm:w-auto">
            Tampilkan
        </button>
    </form>
</section>

@if($componentFilter !== '')
    @forelse($assessments as $assessment)
        @continue($assessmentFilter && $assessmentFilter !== $assessment['id'])
        <section class="surface overflow-hidden">
            <header class="p-5 border-b border-line/60 flex flex-wrap justify-between gap-3">
                <div><p class="text-xs text-muted mb-1">{{ collect($config['components'])->firstWhere('code', $componentFilter)['name'] }}</p><h2 class="section-heading">{{ $assessment['title'] }}</h2></div>
                <span class="text-xs text-muted">{{ count($assessment['questions']) }} soal / kriteria · {{ array_sum(array_column($assessment['questions'], 'points')) }} poin</span>
            </header>
            <details class="p-5 border-b border-line/60" @if($assessmentFilter) open @endif>
                <summary class="text-sm font-semibold text-brand cursor-pointer">Pemetaan soal &amp; bobot penilaian</summary>
                <p class="mt-3 text-xs text-muted">Bobot soal = poin maksimum soal / total poin penilaian. Soal dengan CPMK yang sama digabung berdasarkan poin, bukan rata-rata persentase.</p>
                <div class="overflow-x-auto mt-3"><table class="admin-table w-full"><thead><tr><th>Soal / kriteria</th><th>CPMK</th><th>Poin maks.</th><th>Bobot dalam penilaian</th></tr></thead><tbody>
                    @foreach($assessment['questions'] as $index => $question)
                        <tr><td class="min-w-[240px]"><span class="font-semibold">{{ $index + 1 }}.</span> {{ $question['prompt'] }}</td><td class="whitespace-nowrap">{{ $question['cpmk'] ?? 'Belum dipetakan' }}</td><td>{{ $question['points'] }}</td><td>{{ number_format($question['weight'], 1, ',', '.') }}%</td></tr>
                    @endforeach
                </tbody></table></div>
            </details>
            <div class="overflow-x-auto"><table class="admin-table w-full"><thead><tr><th>Mahasiswa</th><th>Nilai / 100</th><th>Rincian per soal</th></tr></thead><tbody>
                @foreach($students as $student)
                    @php($studentAssessment = collect(\App\Support\AcademicPreview::breakdown($course['id'], $student['id'])['items'])->firstWhere('id', $assessment['id']))
                    <tr>
                        <td class="min-w-[200px]"><p class="font-semibold text-ink">{{ $student['name'] }}</p><p class="text-xs text-muted">{{ $student['number'] }}</p></td>
                        <td class="whitespace-nowrap"><span class="font-semibold">{{ $studentAssessment['score'] === null ? 'Belum lengkap' : number_format($studentAssessment['score'], 1, ',', '.') }}</span></td>
                        <td class="min-w-[280px]"><details><summary class="cursor-pointer text-xs font-semibold text-brand">Lihat nilai &amp; CPMK</summary><div class="mt-3 space-y-2">
                            @foreach($studentAssessment['questions'] as $index => $question)
                                <div class="flex flex-wrap justify-between gap-2 text-xs border-b border-line/40 pb-2"><span>Soal {{ $index + 1 }} · {{ $question['cpmk'] ?? 'Tanpa CPMK' }}</span><span class="font-semibold">{{ $question['earned'] === null ? 'Belum dinilai' : $question['earned'].' / '.$question['points'].' poin' }}</span></div>
                            @endforeach
                        </div></details></td>
                    </tr>
                @endforeach
            </tbody></table></div>
        </section>
    @empty
        <section class="surface p-8 text-center"><h2 class="section-heading">Belum ada penilaian pada komponen ini</h2><p class="mt-2 text-sm text-muted">Nilai komponen manual tetap tersedia di rekap kelas. Rincian CPMK memerlukan penilaian dengan pemetaan soal.</p><a class="button-secondary mt-4" href="{{ route('dosen.course.show', $course['id']) }}">Buka mata kuliah</a></section>
    @endforelse
    <aside class="text-xs text-muted leading-relaxed space-y-1"><p>Nilai penilaian = total poin diperoleh / total poin maksimum x 100. Nilai komponen menggunakan rata-rata penilaian yang telah lengkap dinilai, dengan bobot setara antarpenilaian.</p><p>Nilai CPMK = total poin diperoleh pada CPMK / total poin maksimum pada CPMK x 100. Seluruh CPMK wajib mencapai batas masing-masing; data yang belum dinilai tidak dianggap nol atau lulus.</p></aside>
@endif

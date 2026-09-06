{{-- Desain course dashboard asli. Disimpan untuk dipakai kembali bila diperlukan. --}}
            <section aria-labelledby="course-heading">
                <div class="mb-4 flex min-h-[92px] items-start justify-between gap-4">
                    <div><h2 id="course-heading" class="section-heading">Course semester ini</h2><p class="mt-1 text-sm text-muted">Kelas aktif dari program studi.</p></div>
                    <a href="{{ route('mahasiswa.course.index') }}" class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-brand hover:text-brand-dark">Lihat semua <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </div>

                <div class="grid grid-cols-[repeat(auto-fill,minmax(min(210px,100%),1fr))] gap-3">
                    @foreach ([
                        ['id' => 1, 'code' => 'IF204', 'sks' => '3 SKS', 'title' => 'Struktur Data dan Algoritma', 'type' => 'Tugas coding', 'work' => 'Praktikum Binary Tree', 'due' => 'Hari ini, 23.59', 'lecturer' => 'Dr. Budi Santoso, M.Kom.'],
                        ['id' => 2, 'code' => 'IF218', 'sks' => '3 SKS', 'title' => 'Interaksi Manusia dan Komputer', 'type' => 'Tugas dokumen', 'work' => 'Laporan Evaluasi Usability', 'due' => '3 September', 'lecturer' => 'Dr. Ratna Prameswari, M.Ds.'],
                        ['id' => 3, 'code' => 'IF221', 'sks' => '3 SKS', 'title' => 'Kecerdasan Buatan Terapan', 'type' => 'Kuis', 'work' => 'Kuis Evaluasi Model', 'due' => '7 September', 'lecturer' => 'Prof. Nadia Rahman, Ph.D.'],
                    ] as $course)
                        <a href="{{ route('mahasiswa.course.show', $course['id']) }}" class="group flex h-full flex-col overflow-hidden rounded-xl bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                            <div class="relative min-h-28 overflow-hidden bg-brand-dark px-5 py-5 text-white">
                                @if($course['id'] === 1)
                                    <svg class="absolute -right-3 -top-3 h-32 w-32 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="60" cy="22" r="10"/><circle cx="31" cy="64" r="10"/><circle cx="89" cy="64" r="10"/><circle cx="17" cy="101" r="8"/><circle cx="47" cy="101" r="8"/><circle cx="75" cy="101" r="8"/><circle cx="104" cy="101" r="8"/><path d="M54 30 36 55M66 30l18 25M27 74l-7 19M35 74l9 19M85 74l-8 19M93 74l8 19"/></svg>
                                @elseif($course['id'] === 2)
                                    <svg class="absolute -right-3 -top-2 h-32 w-32 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="13" y="17" width="94" height="74" rx="7"/><path d="M13 35h94M27 26h1M36 26h1M45 26h1M76 51 54 74l14 3 5 15 10-4-6-14 14-4z"/></svg>
                                @else
                                    <svg class="absolute -right-3 -top-3 h-32 w-32 text-white opacity-[0.14]" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="60" cy="60" r="13"/><circle cx="22" cy="28" r="8"/><circle cx="98" cy="26" r="8"/><circle cx="18" cy="93" r="8"/><circle cx="101" cy="94" r="8"/><path d="m29 34 21 18M91 32 70 52M26 88l24-19M94 88 70 69"/></svg>
                                @endif
                                <div class="relative z-10"><div class="flex items-center gap-3 text-xs font-semibold text-white"><span>{{ $course['code'] }}</span><span>{{ $course['sks'] }}</span></div><h3 class="mt-3 text-lg font-semibold leading-6 text-white">{{ $course['title'] }}</h3><p class="mt-1 text-xs text-white">{{ $course['lecturer'] }}</p></div>
                            </div>
                            <div class="flex flex-1 flex-col px-5 py-4"><p class="text-xs font-semibold text-brand">{{ $course['type'] }}</p><p class="mt-1 text-sm font-semibold text-ink">{{ $course['work'] }}</p><p class="mt-3 text-xs font-medium {{ $loop->first ? 'text-danger' : 'text-muted' }}">{{ $course['due'] }}</p></div>
                        </a>
                    @endforeach
                </div>
            </section>


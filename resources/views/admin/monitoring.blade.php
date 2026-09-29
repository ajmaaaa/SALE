@extends('layouts.mahasiswa')
@section('header', 'Monitoring sistem')
@section('title', 'Monitoring sistem | SALE')

@section('content')
@php
    $detail = in_array(request('detail'), ['ai', 'backup'], true) ? request('detail') : null;
    $monitorUrl = fn (?string $target = null) => route('admin.page', ['section' => 'monitoring', 'detail' => $target]);
@endphp
<div class="space-y-6">
    <header><h1 class="page-heading">Monitoring sistem</h1><p class="page-description">Data penggunaan yang telah tercatat oleh layanan SALE.</p></header>
    <div class="grid gap-4 md:grid-cols-2">
        @php
            $cards = [
                [
                    'target' => 'ai',
                    'label' => 'Pemakaian AI',
                    'value' => number_format((int) $aiMetrics['total_tokens'], 0, ',', '.').' token',
                    'description' => number_format((int) $aiMetrics['requests'], 0, ',', '.').' permintaan bulan ini',
                ],
                [
                    'target' => 'backup',
                    'label' => 'Backup & pemulihan',
                    'value' => $latestBackup['created_at'] ?? 'Belum ada cadangan',
                    'description' => count($backupList ?? []).' cadangan tersimpan di server · Siap unduh & pulihkan',
                ],
            ];
        @endphp
        @foreach($cards as $c)
            <a href="{{ $monitorUrl($detail === $c['target'] ? null : $c['target']) }}" @if($detail === $c['target']) aria-current="true" @endif class="surface group border p-5 transition hover:border-brand hover:shadow-md {{ $detail === $c['target'] ? 'border-brand ring-1 ring-brand' : 'border-transparent' }}">
                <h2 class="text-sm text-muted">{{ $c['label'] }}</h2>
                <p class="mt-3 text-xl sm:text-2xl font-semibold tracking-tight">{{ $c['value'] }}</p>
                <p class="mt-2 text-xs leading-5 text-muted">{{ $c['description'] }}</p>
                <span class="mt-5 block text-xs font-semibold text-brand">{{ $detail === $c['target'] ? 'Tutup detail' : 'Lihat detail' }}</span>
            </a>
        @endforeach
    </div>
    @if($detail === 'ai')
        <section class="surface overflow-hidden" aria-labelledby="ai-heading">
            <div class="flex flex-col gap-3 border-b border-line/60 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div>
                    <h2 id="ai-heading" class="section-heading">Pemakaian AI bulan berjalan</h2>
                    <p class="mt-1 text-sm text-muted">Agregat token berasal dari tabel pencatatan panggilan API.</p>
                </div>
                <a href="{{ route('admin.export.ai') }}" class="button-primary text-xs inline-flex items-center gap-2 self-start sm:self-auto shadow-xs">
                    <svg class="h-4 w-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Unduh Rekap AI (Excel)
                </a>
            </div>
            <dl class="grid gap-4 p-5 sm:grid-cols-4 sm:p-6">
                @foreach(['Permintaan' => $aiMetrics['requests'], 'Token input' => $aiMetrics['input_tokens'], 'Token output' => $aiMetrics['output_tokens'], 'Total token' => $aiMetrics['total_tokens']] as $label => $value)
                    <div class="rounded-xl border border-line/50 bg-canvas/50 p-4"><dt class="text-xs text-muted">{{ $label }}</dt><dd class="mt-1 text-xl font-bold text-ink">{{ number_format((int) $value, 0, ',', '.') }}</dd></div>
                @endforeach
            </dl>
            <div class="border-t border-line/60 p-5 sm:p-6">@include('admin.partials.monitoring-ai-requests', ['demo' => false])</div>
        </section>
    @elseif($detail === 'backup')
        <section class="surface overflow-hidden" aria-labelledby="backup-heading">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-line/60 p-5 sm:p-6">
                <div>
                    <h2 id="backup-heading" class="section-heading">Backup & pemulihan</h2>
                    <p class="mt-1 text-sm text-muted">Simpan cadangan ke server dan pulihkan data dari riwayat yang tersedia.</p>
                </div>
                <form method="POST" action="{{ route('admin.backup.create') }}">
                    @csrf
                    <button type="submit" class="button-primary">Buat backup server</button>
                </form>
            </div>
            <div class="p-5 sm:p-6 space-y-6">
                @if(session('status'))
                    <div class="rounded-xl border border-line bg-brand-soft px-4 py-3 text-xs font-semibold text-brand-dark">
                        {{ session('status') }}
                    </div>
                @endif

                @php
                    $currentPath = $settings['backup_path'] ?? 'storage/app/backups';
                    $currentSched = $settings['backup_schedule'] ?? 'daily';
                    $currentTime = $settings['backup_time'] ?? '02:00';
                    $schedLabel = match($currentSched) {
                        'daily' => 'Harian (' . $currentTime . ' WIB)',
                        'weekly' => 'Mingguan (Minggu, ' . $currentTime . ' WIB)',
                        'monthly' => 'Bulanan (Tgl 1, ' . $currentTime . ' WIB)',
                        'manual' => 'Manual (Nonaktif)',
                        default => 'Harian (' . $currentTime . ' WIB)',
                    };
                @endphp

                <div class="grid gap-6 rounded-xl bg-canvas/60 p-5 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold text-muted">BACKUP TERAKHIR</p>
                        <p class="mt-2 text-xl font-semibold">{{ $latestBackup['created_at'] ?? 'Belum ada cadangan' }}</p>
                        <p class="mt-1 text-sm text-muted">{{ $latestBackup ? $latestBackup['size'].' · Tersimpan di server' : 'Cadangan server akan muncul di sini.' }}</p>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-muted">Cakupan</dt><dd class="text-right font-medium">Database lengkap (.sql)</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-muted">Penyimpanan server</dt><dd class="text-right font-medium font-mono text-xs">{{ $currentPath }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-muted">Jadwal otomatis</dt><dd class="text-right font-medium">{{ $schedLabel }}</dd></div>
                    </dl>
                </div>

                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold">Riwayat cadangan di server</h3>
                        <span class="text-xs text-muted">{{ count($backupList) }} cadangan tersedia</span>
                    </div>
                    <div class="divide-y divide-line/60 rounded-xl border border-line/60">
                        @forelse($backupList as $backup)
                            <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 text-muted shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 4h16v5H4zM6 9v11h12V9M10 13h4"/></svg>
                                    <div>
                                        <p class="text-sm font-semibold">{{ $backup['filename'] }}</p>
                                        <p class="mt-0.5 text-xs text-muted">{{ $backup['created_at'] }} · {{ $backup['size'] }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.backup.download', ['file' => $backup['filename']]) }}" class="button-secondary text-xs">Unduh .sql</a>
                                    <form method="POST" action="{{ route('admin.backup.restore') }}" onsubmit="return confirm('Apakah Anda yakin ingin memulihkan database dari berkas {{ $backup['filename'] }}? Seluruh data saat ini akan ditimpa dengan data cadangan ini.');">
                                        @csrf
                                        <input type="hidden" name="filename" value="{{ $backup['filename'] }}">
                                        <button type="submit" class="button-secondary text-xs text-brand hover:text-brand-dark">Pulihkan</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.backup.destroy') }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus berkas cadangan {{ $backup['filename'] }} dari server? Tindakan ini tidak dapat dibatalkan.');">
                                        @csrf
                                        <input type="hidden" name="filename" value="{{ $backup['filename'] }}">
                                        <button type="submit" class="button-secondary text-xs text-rose-600 hover:text-rose-700 hover:border-rose-300">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-center text-xs text-muted">Belum ada berkas cadangan di server. Klik &ldquo;Buat backup server&rdquo; di atas untuk mencadangkan database.</div>
                        @endforelse
                    </div>
                </div>

                <div class="border-t border-line/60 pt-5">
                    <h3 class="text-sm font-semibold">Pengaturan Jadwal &amp; Path Backup Otomatis</h3>
                    <p class="mt-1 text-xs text-muted">Tentukan lokasi direktori penyimpanan berkas di server serta waktu eksekusi backup otomatis.</p>
                    <form method="POST" action="{{ route('admin.backup.settings') }}" class="mt-4 space-y-4">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="form-label text-xs" for="backup_path">Path Penyimpanan di Server</label>
                                <input type="text" name="backup_path" id="backup_path" required value="{{ old('backup_path', $currentPath) }}" class="field font-mono text-xs" placeholder="storage/app/backups">
                                <p class="mt-1 text-[11px] text-muted">Contoh: storage/app/backups atau path absolut</p>
                            </div>
                            <div>
                                <label class="form-label text-xs" for="backup_schedule">Frekuensi Backup Otomatis</label>
                                <select name="backup_schedule" id="backup_schedule" class="field text-xs">
                                    <option value="daily" @selected(old('backup_schedule', $currentSched) === 'daily')>Harian (Setiap Hari)</option>
                                    <option value="weekly" @selected(old('backup_schedule', $currentSched) === 'weekly')>Mingguan (Setiap Minggu)</option>
                                    <option value="monthly" @selected(old('backup_schedule', $currentSched) === 'monthly')>Bulanan (Setiap Bulan)</option>
                                    <option value="manual" @selected(old('backup_schedule', $currentSched) === 'manual')>Manual Saja (Nonaktifkan)</option>
                                </select>
                                <p class="mt-1 text-[11px] text-muted">Dijalankan oleh scheduler sistem</p>
                            </div>
                            <div>
                                <label class="form-label text-xs" for="backup_time">Jam Pelaksanaan (WIB)</label>
                                <input type="time" name="backup_time" id="backup_time" required value="{{ old('backup_time', $currentTime) }}" class="field font-mono text-xs cursor-pointer w-full" onclick="try { this.showPicker(); } catch(e) {}">
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[11px] text-muted">
                                    <span>Pilihan cepat:</span>
                                    <button type="button" onclick="document.getElementById('backup_time').value='00:00'" class="hover:text-brand underline decoration-line/80">00:00</button>
                                    <span>·</span>
                                    <button type="button" onclick="document.getElementById('backup_time').value='01:00'" class="hover:text-brand underline decoration-line/80">01:00</button>
                                    <span>·</span>
                                    <button type="button" onclick="document.getElementById('backup_time').value='02:00'" class="hover:text-brand underline decoration-line/80">02:00</button>
                                    <span>·</span>
                                    <button type="button" onclick="document.getElementById('backup_time').value='03:00'" class="hover:text-brand underline decoration-line/80">03:00</button>
                                    <span>·</span>
                                    <button type="button" onclick="document.getElementById('backup_time').value='04:00'" class="hover:text-brand underline decoration-line/80">04:00</button>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end pt-1">
                            <button type="submit" class="button-primary text-xs">Simpan Pengaturan Backup</button>
                        </div>
                    </form>
                </div>

                <div class="border-t border-line/60 pt-5">
                    <h3 class="text-sm font-semibold">Pulihkan dari Berkas Cadangan Eksternal (.sql)</h3>
                    <p class="mt-1 text-xs text-muted">Unggah berkas database .sql untuk mengembalikan seluruh data jika server mengalami kerusakan atau pemulihan bencana.</p>
                    <form method="POST" action="{{ route('admin.backup.restore') }}" enctype="multipart/form-data" class="mt-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3" onsubmit="return confirm('Apakah Anda yakin ingin memulihkan database dari berkas SQL yang diunggah? Seluruh data saat ini akan ditimpa.');">
                        @csrf
                        <input type="file" name="sql_file" accept=".sql" required class="text-xs text-muted file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-soft file:text-brand hover:file:bg-brand/20">
                        <button type="submit" class="button-secondary text-xs shrink-0">Upload &amp; Pulihkan</button>
                    </form>
                </div>
            </div>
        </section>
    @endif
</div>
@endsection

<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ScanAttachmentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sale:scan-attachments
                            {--dry-run : Hanya laporkan temuan tanpa melakukan penghapusan}
                            {--grace-hours=24 : Batas waktu masa tenggang (grace period) dalam jam}
                            {--delete-orphans : Lakukan pembersihan berkas yatim (orphan) yang telah melewati masa tenggang}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pindai berkas lampiran, identifikasi file yatim (orphan) atau row tanpa fisik, dan bersihkan file lama secara idempoten.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run') || ! $this->option('delete-orphans');
        $graceHours = max(1, (int) $this->option('grace-hours'));
        $graceThreshold = now()->subHours($graceHours)->timestamp;

        $this->info('=== Pindai Lampiran SALE ===');
        $this->info('Mode: '.($isDryRun ? 'DRY-RUN (Simulasi - berkas TIDAK dihapus)' : 'CLEANUP (Penghapusan aktif)'));
        $this->info("Masa tenggang: {$graceHours} jam (file sebelum ".date('Y-m-d H:i:s', $graceThreshold).')');

        $disk = Storage::disk('local');

        $stalePending = Attachment::where('status', Attachment::STATUS_PENDING)
            ->where('created_at', '<', now()->subHours($graceHours))
            ->get();
        if (! $isDryRun) {
            foreach ($stalePending as $attachment) {
                if ($attachment->path && $disk->exists($attachment->path)) {
                    $disk->delete($attachment->path);
                }
                $attachment->delete();
            }
        }

        $allPhysicalFiles = collect($disk->allFiles('learning-preview'));

        // Ambil seluruh path yang terdaftar di database
        $dbPaths = Attachment::where('status', Attachment::STATUS_ATTACHED)
            ->pluck('path')->filter()->unique()->values()->all();
        $dbPathLookup = array_flip($dbPaths);

        $orphans = [];
        $activeCount = 0;
        $missingFiles = [];
        $reclaimedBytes = 0;

        // 1. Periksa berkas fisik
        foreach ($allPhysicalFiles as $file) {
            if (isset($dbPathLookup[$file])) {
                $activeCount++;

                continue;
            }

            $mtime = $disk->lastModified($file);
            $size = $disk->size($file);

            if ($mtime < $graceThreshold) {
                $orphans[] = [
                    'path' => $file,
                    'size' => $size,
                    'mtime' => $mtime,
                ];
                $reclaimedBytes += $size;

                if (! $isDryRun) {
                    $disk->delete($file);
                }
            }
        }

        // 2. Periksa baris database yang berkas fisiknya hilang
        foreach ($dbPaths as $path) {
            if (! $disk->exists($path)) {
                $missingFiles[] = $path;
            }
        }

        $this->newLine();
        $this->table(['Metrik', 'Jumlah'], [
            ['Berkas fisik terpindai', $allPhysicalFiles->count()],
            ['Berkas fisik aktif terhubung', $activeCount],
            ['Berkas orphan melewati grace period', count($orphans)],
            ['Row database dengan berkas fisik hilang', count($missingFiles)],
            ['Upload pending melewati grace period', $stalePending->count()],
            ['Kapasitas '.($isDryRun ? 'potensial bebas' : 'berhasil dibebaskan'), number_format($reclaimedBytes / 1024, 2).' KB'],
        ]);

        if (count($orphans) > 0 && $this->getOutput()->isVerbose()) {
            $this->newLine();
            $this->info('Daftar berkas orphan:');
            foreach ($orphans as $orphan) {
                $this->line(" - {$orphan['path']} (".number_format($orphan['size'] / 1024, 1).' KB)');
            }
        }

        if ($isDryRun && count($orphans) > 0) {
            $this->comment('Gunakan opsi --delete-orphans tanpa --dry-run untuk mengeksekusi pembersihan nyata.');
        } else {
            $this->info('Pemeriksaan selesai.');
        }

        return 0;
    }
}

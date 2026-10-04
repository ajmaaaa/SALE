<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class BackupService
{
    /**
     * Dapatkan direktori root penyimpanan cadangan (selalu di luar public).
     */
    public static function getBackupRoot(): string
    {
        $root = (string) (config('backup.root') ?: storage_path('app/private/backups'));
        $root = rtrim(str_replace('\\', '/', $root), '/');

        if ($root === '' || ! str_starts_with($root, '/')) {
            $root = storage_path('app/private/backups');
        }

        // Pastikan bukan di dalam public
        $publicPath = rtrim(str_replace('\\', '/', public_path()), '/');
        $publicStorage = rtrim(str_replace('\\', '/', storage_path('app/public')), '/');
        if (self::isWithin($root, $publicPath) || self::isWithin($root, $publicStorage)) {
            $root = storage_path('app/private/backups');
        }

        if (! is_dir($root)) {
            @mkdir($root, 0700, true);
        }
        @chmod($root, 0700);

        return $root;
    }

    /**
     * Dapatkan direktori aktif untuk backup berdasarkan pengaturan sistem,
     * dibatasi secara ketat di dalam getBackupRoot().
     */
    public static function getBackupDirectory(): string
    {
        $root = self::getBackupRoot();
        $custom = SystemSetting::where('key', 'backup_path')->value('value');

        if ($custom && is_string($custom) && trim($custom) !== '') {
            $trimmed = trim(str_replace('\\', '/', $custom));

            // Cegah null-byte dan path traversal
            if (
                ! str_contains($trimmed, "\0")
                && ! str_contains($trimmed, '..')
                && ! str_starts_with($trimmed, '/')
                && preg_match('#^[A-Za-z0-9._/-]+$#', $trimmed)
            ) {
                // Hanya izinkan subpath relatif di bawah root
                $trimmed = ltrim($trimmed, '/');
                $candidate = $root.($trimmed !== '' ? '/'.$trimmed : '');

                if (! is_dir($candidate)) {
                    @mkdir($candidate, 0700, true);
                }

                $realCandidate = realpath($candidate);
                $realRoot = realpath($root);

                if ($realCandidate && $realRoot && self::isWithin($realCandidate, $realRoot)) {
                    @chmod($realCandidate, 0700);

                    return $realCandidate;
                }
            }
        }

        return $root;
    }

    /**
     * Validasi nama file backup hanya karakter aman dan berekstensi .sql.
     */
    public static function isValidFilename(string $filename): bool
    {
        if ($filename === '' || str_contains($filename, "\0") || str_contains($filename, '..')) {
            return false;
        }

        if (str_contains($filename, '/') || str_contains($filename, '\\')) {
            return false;
        }

        return (bool) preg_match('/^[a-zA-Z0-9_\-\.]+\.sql$/i', $filename);
    }

    /**
     * Dapatkan path lengkap file backup yang valid dan terverifikasi di dalam direktori backup.
     */
    public static function getVerifiedFilePath(string $filename): ?string
    {
        if (! self::isValidFilename($filename)) {
            return null;
        }

        $dir = self::getBackupDirectory();
        $path = $dir.'/'.$filename;

        if (! file_exists($path)) {
            // Cek root fallback
            $rootPath = self::getBackupRoot().'/'.$filename;
            if (file_exists($rootPath)) {
                $path = $rootPath;
            } else {
                return null;
            }
        }

        $realPath = realpath($path);
        $realRoot = realpath(self::getBackupRoot());

        if (! $realPath || ! $realRoot || is_link($path) || ! self::isWithin($realPath, $realRoot)) {
            return null;
        }

        return $realPath;
    }

    /**
     * Daftar seluruh file backup yang sah.
     */
    public static function listBackups(): Collection
    {
        $dir = self::getBackupDirectory();
        $root = self::getBackupRoot();
        $dirsToScan = array_unique([$dir, $root]);

        $backups = collect();
        $seen = [];

        foreach ($dirsToScan as $d) {
            if (! is_dir($d)) {
                continue;
            }

            $files = scandir($d);
            if (! is_array($files)) {
                continue;
            }

            foreach ($files as $f) {
                if ($f === '.' || $f === '..' || ! str_ends_with(strtolower($f), '.sql')) {
                    continue;
                }
                if (! self::isValidFilename($f) || isset($seen[$f])) {
                    continue;
                }

                $fullPath = $d.'/'.$f;
                if (! is_file($fullPath)) {
                    continue;
                }

                $seen[$f] = true;
                $mtime = filemtime($fullPath) ?: time();
                $size = filesize($fullPath) ?: 0;

                $backups->push([
                    'filename' => $f,
                    'path' => $fullPath,
                    'size' => self::formatSize($size),
                    'size_bytes' => $size,
                    'size_formatted' => self::formatSize($size),
                    'timestamp' => $mtime,
                    'created_at' => date('d M Y H:i:s', $mtime),
                    'date_formatted' => date('d M Y H:i:s', $mtime),
                ]);
            }
        }

        return $backups->sortByDesc('timestamp')->values();
    }

    /**
     * Jalankan pembuatan cadangan database (dump) secara aman.
     * Tidak membocorkan credential ke process list.
     */
    public static function createBackup(?string $customFilename = null): array
    {
        $dir = self::getBackupDirectory();
        if (! is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        $filename = $customFilename ?: ('sale-backup-'.now()->format('Y-m-d_His').'.sql');
        if (! self::isValidFilename($filename)) {
            return ['success' => false, 'error' => 'Nama file backup tidak valid.'];
        }

        $destPath = $dir.'/'.$filename;
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $sqlitePath = (string) config('database.connections.sqlite.database');
            $binary = self::resolveBinary('dump_binary', ['/usr/bin/sqlite3']);
            if ($sqlitePath === '' || ! is_file($sqlitePath) || ! $binary) {
                return ['success' => false, 'error' => 'SQLite database atau binary sqlite3 tidak tersedia.'];
            }

            return self::runDumpProcess(
                new Process([$binary, $sqlitePath, '.dump']),
                $destPath,
                '',
                (int) config('backup.dump_timeout', 300),
                $filename
            );
        }

        if ($driver === 'mysql') {
            $dbConfig = config('database.connections.mysql');
            $host = (string) ($dbConfig['host'] ?? '127.0.0.1');
            $port = (string) ($dbConfig['port'] ?? 3306);
            $database = (string) ($dbConfig['database'] ?? 'sale');
            $username = (string) ($dbConfig['username'] ?? 'root');
            $password = (string) ($dbConfig['password'] ?? '');

            $binary = self::resolveBinary('dump_binary', ['/usr/bin/mariadb-dump', '/usr/bin/mysqldump']);

            if (! $binary) {
                return ['success' => false, 'error' => 'Database dump binary tidak ditemukan di server.'];
            }

            // Buat temporary cnf file berizin 0600 agar password tidak muncul di process list
            $tempCnf = tempnam(sys_get_temp_dir(), 'sale_my_');
            if ($tempCnf === false) {
                return ['success' => false, 'error' => 'Gagal membuat berkas konfigurasi database sementara.'];
            }
            @chmod($tempCnf, 0600);
            $cnfContent = "[client]\nuser=".addcslashes($username, "\"\\\n\r")."\npassword=\"".addcslashes($password, "\"\\\n\r")."\"\nhost=".addcslashes($host, "\"\\\n\r")."\nport=".(int) $port."\n";
            file_put_contents($tempCnf, $cnfContent);

            try {
                $process = new Process([
                    $binary,
                    "--defaults-extra-file={$tempCnf}",
                    '--single-transaction',
                    '--quick',
                    '--skip-lock-tables',
                    $database,
                ]);

                return self::runDumpProcess(
                    $process,
                    $destPath,
                    $password,
                    (int) config('backup.dump_timeout', 300),
                    $filename
                );
            } finally {
                if (file_exists($tempCnf)) {
                    @unlink($tempCnf);
                }
            }
        }

        return ['success' => false, 'error' => "Driver database {$driver} tidak didukung untuk backup."];
    }

    /**
     * Pulihkan database dari file .sql secara aman dengan preflight & auto-backup.
     */
    public static function restoreBackup(string $sourcePath, ?string $displayName = null): array
    {
        if (! file_exists($sourcePath) || ! is_readable($sourcePath)) {
            return ['success' => false, 'error' => 'Berkas cadangan tidak dapat dibaca atau tidak ditemukan.'];
        }

        $size = filesize($sourcePath);
        if ($size === 0) {
            return ['success' => false, 'error' => 'Berkas cadangan kosong (0 byte).'];
        }

        $freeSpace = @disk_free_space(dirname($sourcePath));
        if (is_float($freeSpace) && $freeSpace < max(10 * 1024 * 1024, $size * 2)) {
            return ['success' => false, 'error' => 'Ruang disk tidak cukup untuk preflight dan backup sebelum restore.'];
        }

        // Preflight: Periksa struktur file apakah merupakan SQL yang masuk akal
        $handle = fopen($sourcePath, 'rb');
        $headerSample = fread($handle, 4096);
        fclose($handle);

        $sqlMarkers = ['--', '/*', 'create', 'insert', 'drop', 'set', 'table', 'database', 'use'];
        $hasSqlMarker = false;
        $lowerSample = strtolower($headerSample);
        foreach ($sqlMarkers as $marker) {
            if (str_contains($lowerSample, $marker)) {
                $hasSqlMarker = true;
                break;
            }
        }

        if (! $hasSqlMarker) {
            return ['success' => false, 'error' => 'Format berkas cadangan tidak valid (bukan SQL).'];
        }

        // Preflight: Cek koneksi DB tersedia
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Koneksi database tidak tersedia untuk proses restore.'];
        }

        // Buat backup otomatis sebelum restore untuk keamanan
        $safetyBackup = self::createBackup('auto-backup-before-restore-'.now()->format('Y-m-d_His').'.sql');
        if (! ($safetyBackup['success'] ?? false)) {
            return ['success' => false, 'error' => 'Restore dibatalkan karena backup pengaman gagal dibuat.'];
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return ['success' => false, 'error' => 'Restore SQLite in-place dinonaktifkan; gunakan database disposable dan prosedur operator.'];
        }

        if ($driver === 'mysql') {
            $dbConfig = config('database.connections.mysql');
            $host = (string) ($dbConfig['host'] ?? '127.0.0.1');
            $port = (string) ($dbConfig['port'] ?? 3306);
            $database = (string) ($dbConfig['database'] ?? 'sale');
            $username = (string) ($dbConfig['username'] ?? 'root');
            $password = (string) ($dbConfig['password'] ?? '');

            $binary = self::resolveBinary('restore_binary', ['/usr/bin/mariadb', '/usr/bin/mysql']);

            if (! $binary) {
                return ['success' => false, 'error' => 'MySQL client binary tidak ditemukan di server.'];
            }

            $tempCnf = tempnam(sys_get_temp_dir(), 'sale_my_');
            if ($tempCnf === false) {
                return ['success' => false, 'error' => 'Gagal membuat berkas konfigurasi database sementara.'];
            }
            @chmod($tempCnf, 0600);
            $cnfContent = "[client]\nuser=".addcslashes($username, "\"\\\n\r")."\npassword=\"".addcslashes($password, "\"\\\n\r")."\"\nhost=".addcslashes($host, "\"\\\n\r")."\nport=".(int) $port."\n";
            file_put_contents($tempCnf, $cnfContent);

            try {
                $process = new Process([
                    $binary,
                    "--defaults-extra-file={$tempCnf}",
                    $database,
                ]);
                $process->setInput(fopen($sourcePath, 'rb'));
                $process->setTimeout((int) config('backup.restore_timeout', 600));

                $process->run();

                if (! $process->isSuccessful()) {
                    $err = self::sanitizeError($process->getErrorOutput(), $password);

                    return ['success' => false, 'error' => 'Gagal memulihkan database: '.($err ?: 'Exit code '.$process->getExitCode())];
                }

                // Pascarestore: verifikasi koneksi query masih bekerja
                DB::select('SELECT 1');

                return ['success' => true, 'message' => "Basis data berhasil dipulihkan dari cadangan: {$displayName}."];
            } catch (\Throwable $e) {
                return ['success' => false, 'error' => 'Kesalahan saat proses restore: '.self::sanitizeError($e->getMessage(), $password)];
            } finally {
                if (file_exists($tempCnf)) {
                    @unlink($tempCnf);
                }
            }
        }

        return ['success' => false, 'error' => "Driver database {$driver} tidak didukung untuk restore."];
    }

    /**
     * Hapus berkas cadangan secara aman.
     */
    public static function deleteBackup(string $filename): bool
    {
        $verifiedPath = self::getVerifiedFilePath($filename);
        if ($verifiedPath && file_exists($verifiedPath)) {
            return @unlink($verifiedPath);
        }

        return false;
    }

    /**
     * Bersihkan credential atau string sensitif dari pesan error.
     */
    protected static function sanitizeError(string $error, string $secret): string
    {
        if ($secret !== '') {
            $error = str_replace($secret, '********', $error);
        }

        return trim(preg_replace('/password=[^\s]+/i', 'password=********', $error));
    }

    protected static function isWithin(string $path, string $root): bool
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $root = rtrim(str_replace('\\', '/', $root), '/');

        return $path === $root || str_starts_with($path, $root.'/');
    }

    protected static function resolveBinary(string $configKey, array $candidates): ?string
    {
        $configured = config('backup.'.$configKey);
        if (is_string($configured) && $configured !== '') {
            return is_executable($configured) ? $configured : null;
        }

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected static function runDumpProcess(Process $process, string $destPath, string $secret, int $timeout, string $filename): array
    {
        $handle = @fopen($destPath, 'wb');
        if (! is_resource($handle)) {
            return ['success' => false, 'error' => 'Gagal membuka file tujuan untuk menulis backup.'];
        }

        try {
            $process->setTimeout(max(1, $timeout));
            $process->run(function ($type, $buffer) use ($handle) {
                if ($type === Process::OUT) {
                    fwrite($handle, $buffer);
                }
            });
        } catch (\Throwable $exception) {
            @unlink($destPath);

            return ['success' => false, 'error' => 'Proses backup gagal: '.self::sanitizeError($exception->getMessage(), $secret)];
        } finally {
            fclose($handle);
        }

        $size = is_file($destPath) ? (int) filesize($destPath) : 0;
        if (! $process->isSuccessful() || $size === 0) {
            @unlink($destPath);
            $stderr = self::sanitizeError($process->getErrorOutput(), $secret);

            return ['success' => false, 'error' => 'Gagal membuat backup database: '.($stderr ?: 'Exit code '.$process->getExitCode())];
        }

        @chmod($destPath, 0600);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $destPath,
            'size' => $size,
            'checksum' => hash_file('sha256', $destPath),
            'error' => null,
        ];
    }

    protected static function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}

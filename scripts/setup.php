<?php

$projectRoot = dirname(__DIR__);
$environmentFile = $projectRoot.'/.env';
$exampleEnvironmentFile = $projectRoot.'/.env.example';

if (PHP_VERSION_ID < 80200) {
    fwrite(STDERR, "Project memerlukan PHP 8.2 atau lebih baru.\n");
    exit(1);
}

if (! extension_loaded('pdo_mysql')) {
    fwrite(STDERR, "Extension PHP pdo_mysql belum aktif. Aktifkan extension tersebut lalu ulangi setup.\n");
    exit(1);
}

if (! file_exists($environmentFile)) {
    if (! copy($exampleEnvironmentFile, $environmentFile)) {
        fwrite(STDERR, "Gagal membuat .env dari .env.example.\n");
        exit(1);
    }

    fwrite(STDOUT, ".env dibuat dari .env.example.\n");
}

$environment = file_get_contents($environmentFile);

if ($environment === false) {
    fwrite(STDERR, "Gagal membaca .env.\n");
    exit(1);
}

if (preg_match('/^APP_KEY=\s*$/m', $environment) === 1) {
    $key = 'base64:'.base64_encode(random_bytes(32));
    $environment = preg_replace('/^APP_KEY=\s*$/m', 'APP_KEY='.$key, $environment, 1);

    if ($environment === null || file_put_contents($environmentFile, $environment) === false) {
        fwrite(STDERR, "Gagal menyimpan APP_KEY ke .env.\n");
        exit(1);
    }

    fwrite(STDOUT, "APP_KEY dibuat.\n");
} else {
    fwrite(STDOUT, ".env dan APP_KEY sudah tersedia.\n");
}

/**
 * Read a simple scalar value from Laravel's environment file.
 */
function environmentValue(string $contents, string $name, ?string $default = null): ?string
{
    if (preg_match('/^'.preg_quote($name, '/').'=(.*)$/m', $contents, $matches) !== 1) {
        return $default;
    }

    $value = trim($matches[1]);

    if (strlen($value) >= 2 && (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'"))) {
        return substr($value, 1, -1);
    }

    return $value;
}

$connection = environmentValue($environment, 'DB_CONNECTION', 'mysql');

if ($connection !== 'mysql' && $connection !== 'mariadb') {
    fwrite(STDERR, "DB_CONNECTION harus mysql atau mariadb untuk setup ini.\n");
    exit(1);
}

$host = environmentValue($environment, 'DB_HOST', '127.0.0.1');
$port = environmentValue($environment, 'DB_PORT', '3306');
$database = environmentValue($environment, 'DB_DATABASE', 'sale');
$username = environmentValue($environment, 'DB_USERNAME', 'root');
$password = environmentValue($environment, 'DB_PASSWORD', '');

if (! preg_match('/^[A-Za-z0-9_-]+$/', (string) $database)) {
    fwrite(STDERR, "DB_DATABASE hanya boleh berisi huruf, angka, garis bawah, atau tanda hubung.\n");
    exit(1);
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 5,
];

try {
    new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        $options,
    );

    fwrite(STDOUT, "Database MySQL `{$database}` sudah tersedia.\n");
} catch (PDOException) {
    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};charset=utf8mb4",
            $username,
            $password,
            $options,
        );
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        fwrite(STDOUT, "Database MySQL `{$database}` berhasil dibuat.\n");
    } catch (PDOException $exception) {
        fwrite(STDERR, "Gagal terhubung atau membuat database MySQL `{$database}`.\n");
        fwrite(STDERR, "Pastikan service MySQL aktif dan DB_HOST, DB_PORT, DB_USERNAME, serta DB_PASSWORD di .env sudah benar.\n");
        fwrite(STDERR, "Detail: {$exception->getMessage()}\n");
        exit(1);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SystemSetting extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function valueFor(string $key, ?string $default = null): ?string
    {
        return static::query()->whereKey($key)->value('value') ?? $default;
    }

    /**
     * Kembalikan logo sebagai data URI base64 (untuk PDF/print).
     * Prioritas: logo yang di-upload admin → fallback ke logo default UMRAH.
     */
    public static function logoBase64(): string
    {
        $storedPath = static::valueFor('app_logo_path');
        if ($storedPath && Storage::disk('public')->exists($storedPath)) {
            $content = Storage::disk('public')->get($storedPath);
            $mime = Storage::disk('public')->mimeType($storedPath) ?: 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode($content);
        }
        // Fallback ke logo default di public/images/
        $defaultPath = public_path('images/logo-umrah.png');
        if (file_exists($defaultPath)) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($defaultPath));
        }
        return '';
    }

    /**
     * Kembalikan URL publik logo (untuk tampilan UI/sidebar).
     */
    public static function logoUrl(): string
    {
        $storedPath = static::valueFor('app_logo_path');
        if ($storedPath && Storage::disk('public')->exists($storedPath)) {
            return Storage::disk('public')->url($storedPath);
        }
        return asset('images/logo-umrah.png');
    }

    /**
     * Kembalikan nama aplikasi dari pengaturan admin.
     */
    public static function appName(): string
    {
        return static::valueFor('app_name', 'SALE');
    }
}

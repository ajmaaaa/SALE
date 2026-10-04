<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Role;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $preferences = array_merge([
            'notif_submission' => true,
            'notif_deadline' => true,
            'notif_forum' => true,
            'notif_rekap' => true,
        ], $user?->notification_preferences ?? []);

        $activeSemester = Semester::where('is_active', true)->first()?->name
            ?? $user?->classSectionsTeaching()
                ->whereHas('semester')
                ->with('semester')
                ->first()?->semester?->name
            ?? Semester::latest('id')->first()?->name;

        $totalClasses = $user
            ? ClassSection::where(function ($query) use ($user) {
                $query->where('dosen_id', $user->id)
                    ->orWhere('dosen_pendamping_id', $user->id)
                    ->orWhereHas('dosenAnggota', fn ($sub) => $sub->where('users.id', $user->id));
            })->count()
            : 0;

        return view('dosen.profil', [
            'user' => $user,
            'preferences' => $preferences,
            'settingsWritable' => $user?->hasRole(Role::DOSEN) ?? false,
            'profilePhotoUrl' => $user?->profile_photo_url,
            'activeSemester' => $activeSemester,
            'totalClasses' => $totalClasses,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole(Role::DOSEN), 403);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini yang Anda masukkan salah.',
            'new_password.required' => 'Kata sandi baru wajib diisi.',
            'new_password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'new_password.min' => 'Kata sandi baru minimal 8 karakter.',
            'new_password.letters' => 'Kata sandi baru harus mengandung setidaknya satu huruf.',
            'new_password.numbers' => 'Kata sandi baru harus mengandung setidaknya satu angka.',
        ]);

        $user->forceFill([
            'password' => $validated['new_password'],
            'must_change_password' => false,
        ])->save();

        return redirect()->to(route('dosen.profile.index').'#keamanan')
            ->with('status', 'password-updated');
    }

    public function updateNotificationPreferences(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole(Role::DOSEN), 403);

        $preferenceKeys = ['notif_submission', 'notif_deadline', 'notif_forum', 'notif_rekap'];
        $rules = [];
        foreach ($preferenceKeys as $key) {
            $rules['preferences.'.$key] = ['sometimes', 'boolean'];
        }
        $request->validate($rules);

        $user->forceFill([
            'notification_preferences' => collect($preferenceKeys)
                ->mapWithKeys(fn (string $key) => [$key => $request->boolean('preferences.'.$key)])
                ->all(),
        ])->save();

        return redirect()->to(route('dosen.profile.index').'#notifikasi')
            ->with('status', 'notification-preferences-updated')
            ->with('notice', 'Preferensi notifikasi berhasil disimpan.');
    }

    public function uploadPhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole(Role::DOSEN), 403);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'photo.max' => 'Ukuran foto profil tidak boleh melebihi batas maksimal 2 MB.',
            'photo.image' => 'Berkas harus berupa gambar/foto.',
            'photo.mimes' => 'Format foto profil harus berupa JPG, PNG, atau WebP.',
        ]);

        if ($user->profile_photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profile_photo_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $validated['photo']->storePublicly('profile-photos/'.$user->id, 'public');
        if (! $path) {
            return back()->withErrors(['photo' => 'Foto gagal disimpan. Coba lagi.'])->withFragment('profil');
        }

        self::optimizeAvatar(\Illuminate\Support\Facades\Storage::disk('public')->path($path));

        $user->forceFill(['profile_photo_path' => $path])->save();

        return redirect()->to(route('dosen.profile.index').'#profil')
            ->with('status', 'profile-photo-uploaded');
    }

    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole(Role::DOSEN), 403);

        if ($user->profile_photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profile_photo_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->forceFill(['profile_photo_path' => null])->save();

        return redirect()->to(route('dosen.profile.index').'#profil')
            ->with('status', 'profile-photo-deleted');
    }

    public static function optimizeAvatar(string $fullPath): void
    {
        if (! file_exists($fullPath) || ! extension_loaded('gd')) {
            return;
        }

        $info = @getimagesize($fullPath);
        if (! $info) {
            return;
        }

        [$width, $height] = $info;
        if ($width <= 300 && $height <= 300 && filesize($fullPath) <= 80000) {
            return;
        }

        $mime = $info['mime'] ?? '';
        $srcImg = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($fullPath),
            'image/png' => @imagecreatefrompng($fullPath),
            'image/webp' => @imagecreatefromwebp($fullPath),
            default => null,
        };

        if (! $srcImg) {
            return;
        }

        $min = min($width, $height);
        $srcX = (int) (($width - $min) / 2);
        $srcY = (int) (($height - $min) / 2);
        $targetSize = min(300, $min);

        $dstImg = imagecreatetruecolor($targetSize, $targetSize);
        if ($mime === 'image/png') {
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
        }

        imagecopyresampled($dstImg, $srcImg, 0, 0, $srcX, $srcY, $targetSize, $targetSize, $min, $min);

        if ($mime === 'image/png') {
            imagepng($dstImg, $fullPath, 7);
        } elseif ($mime === 'image/webp') {
            imagewebp($dstImg, $fullPath, 85);
        } else {
            imagejpeg($dstImg, $fullPath, 85);
        }

        imagedestroy($srcImg);
        imagedestroy($dstImg);
        clearstatcache(true, $fullPath);
    }
}

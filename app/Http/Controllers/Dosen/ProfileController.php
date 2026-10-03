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
            'notif_forum' => false,
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
        ]);

        $user->forceFill(['password' => $validated['new_password']])->save();

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
            ->with('status', 'notification-preferences-updated');
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
}

<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $preferences = array_merge([
            'announcement' => true,
            'deadline' => true,
            'grade' => true,
            'forum' => false,
        ], $user?->notification_preferences ?? []);
        $activeSemester = Semester::where('is_active', true)->first()?->name
            ?? $user?->classSectionsEnrolled()
                ->whereHas('semester')
                ->with('semester')
                ->first()?->semester?->name
            ?? Semester::latest('id')->first()?->name;

        $totalClasses = $user ? $user->classSectionsEnrolled()->count() : 0;

        return view('mahasiswa.profile', [
            'user' => $user,
            'preferences' => $preferences,
            'settingsWritable' => $user?->hasRole(Role::MAHASISWA) ?? false,
            'profilePhotoUrl' => $user?->profile_photo_url,
            'activeSemester' => $activeSemester,
            'totalClasses' => $totalClasses,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole(Role::MAHASISWA), 403);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user->forceFill(['password' => $validated['new_password']])->save();

        return redirect()->to(route('mahasiswa.profile.index').'#keamanan')
            ->with('status', 'password-updated');
    }

    public function updateNotificationPreferences(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole(Role::MAHASISWA), 403);

        $preferenceKeys = ['announcement', 'deadline', 'grade', 'forum'];
        $rules = [];
        foreach ($preferenceKeys as $key) {
            $rules['preferences.'.$key] = ['sometimes', 'boolean'];
        }
        $request->validate($rules);

        $preferences = array_merge(
            $user->notification_preferences ?? [],
            collect($preferenceKeys)
                ->mapWithKeys(fn (string $key) => [$key => $request->boolean('preferences.'.$key)])
                ->all()
        );

        $user->forceFill(['notification_preferences' => $preferences])->save();

        return redirect()->to(route('mahasiswa.profile.index').'#notifikasi')
            ->with('status', 'notification-preferences-updated');
    }

    public function uploadPhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole(Role::MAHASISWA), 403);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'photo.max' => 'Ukuran foto profil tidak boleh melebihi batas maksimal 2 MB.',
            'photo.image' => 'Berkas harus berupa gambar/foto.',
            'photo.mimes' => 'Format foto profil harus berupa JPG, PNG, atau WebP.',
        ]);

        if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $validated['photo']->storePublicly('profile-photos/'.$user->id, 'public');
        if (! $path) {
            return back()->withErrors(['photo' => 'Foto gagal disimpan. Coba lagi.'])->withFragment('profil');
        }

        $user->forceFill(['profile_photo_path' => $path])->save();

        return redirect()->to(route('mahasiswa.profile.index').'#profil')
            ->with('status', 'profile-photo-uploaded');
    }

    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole(Role::MAHASISWA), 403);

        if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->forceFill(['profile_photo_path' => null])->save();

        return redirect()->to(route('mahasiswa.profile.index').'#profil')
            ->with('status', 'profile-photo-deleted');
    }
}

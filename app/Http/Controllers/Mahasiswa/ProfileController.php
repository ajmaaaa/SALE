<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
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
        $activeSemester = $user
            ? $user->classSectionsEnrolled()
                ->whereHas('semester', fn ($query) => $query->where('is_active', true))
                ->with('semester')
                ->first()?->semester?->name
            : null;

        return view('mahasiswa.profile', [
            'user' => $user,
            'preferences' => $preferences,
            'settingsWritable' => $user?->hasRole(Role::MAHASISWA) ?? false,
            'profilePhotoUrl' => $user?->profile_photo_path
                ? Storage::disk('public')->url($user->profile_photo_path)
                : null,
            'activeSemester' => $activeSemester,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole(Role::MAHASISWA), 403);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
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

        if ($user->profile_photo_path) {
            return back()->withErrors(['photo' => 'Foto profil hanya dapat diunggah satu kali.'])->withFragment('profil');
        }

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $path = $validated['photo']->storePublicly('profile-photos/'.$user->id, 'public');
        if (! $path) {
            return back()->withErrors(['photo' => 'Foto gagal disimpan. Coba lagi.'])->withFragment('profil');
        }

        $user->forceFill(['profile_photo_path' => $path])->save();

        return redirect()->to(route('mahasiswa.profile.index').'#profil')
            ->with('status', 'profile-photo-uploaded');
    }
}

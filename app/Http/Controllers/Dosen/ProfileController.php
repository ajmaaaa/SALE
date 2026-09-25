<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Role;
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

        return view('dosen.profil', [
            'preferences' => $preferences,
            'settingsWritable' => $user?->hasRole(Role::DOSEN) ?? false,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole(Role::DOSEN), 403);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
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
}

<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Prodi;
use App\Models\Semester;
use App\Models\SystemSetting;
use App\Models\User;

/** Database-backed compatibility adapter for the existing admin views. */
class AdminPreview
{
    public const FACULTY_ID = 1;
    public const PRODI_OFFSET = 1_000_000;
    public const SEMESTER_OFFSET = 2_000_000;

    public static function users(): array
    {
        return User::query()->with(['role', 'roles', 'prodi', 'managingProdi'])->orderBy('name')->get()
            ->mapWithKeys(function (User $user) {
                $roles = $user->roles->pluck('name')->whenEmpty(fn ($collection) => $collection->push($user->role?->name ?? 'mahasiswa'))->unique()->values()->all();
                $role = $user->role?->name ?? $roles[0];
                $prodiName = $user->managingProdi?->name ?? ($user->prodi?->name ?? null);
                return [$user->id => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'number' => $user->nim_nidn,
                    'role' => $role,
                    'roles' => $roles,
                    'prodi_id' => $user->managing_prodi_id ?? $user->prodi_id,
                    'prodi_name' => $prodiName,
                    'status' => $user->is_active ? 'aktif' : 'nonaktif',
                ]];
            })->all();
    }

    public static function hasRole(array $user, string $role): bool
    {
        return ($user['role'] ?? null) === $role || in_array($role, $user['roles'] ?? [], true);
    }

    public static function academic(): array
    {
        $records = [];
        $facultyName = SystemSetting::valueFor('faculty_name');
        $facultyCode = SystemSetting::valueFor('faculty_code');
        if ($facultyName || $facultyCode) {
            $records[self::FACULTY_ID] = [
                'id' => self::FACULTY_ID,
                'type' => 'fakultas',
                'code' => $facultyCode ?: 'FAK',
                'name' => $facultyName ?: 'Fakultas',
                'parent' => null,
                'course' => null,
                'students' => [],
                'status' => SystemSetting::valueFor('faculty_status', 'aktif'),
            ];
        }

        foreach (Prodi::query()->orderBy('code')->get() as $prodi) {
            $id = self::PRODI_OFFSET + $prodi->id;
            $records[$id] = [
                'id' => $id,
                'type' => 'prodi',
                'code' => $prodi->code,
                'name' => $prodi->name,
                'parent' => $facultyName || $facultyCode ? self::FACULTY_ID : null,
                'course' => null,
                'students' => [],
                'status' => 'aktif',
            ];
        }

        foreach (Semester::query()->orderByDesc('code')->get() as $semester) {
            $id = self::SEMESTER_OFFSET + $semester->id;
            $records[$id] = [
                'id' => $id,
                'type' => 'semester',
                'code' => $semester->code,
                'name' => $semester->name,
                'parent' => null,
                'course' => null,
                'students' => [],
                'status' => $semester->is_active ? 'aktif' : 'nonaktif',
            ];
        }
        return $records;
    }

    public static function settings(): array
    {
        return SystemSetting::query()->pluck('value', 'key')->all();
    }

    public static function logs(): array
    {
        return ActivityLog::query()->with('actor:id,name')->latest('created_at')->limit(100)->get()
            ->map(fn (ActivityLog $log) => [
                'time' => $log->created_at->format('d M Y, H:i:s'),
                'actor' => $log->actor?->name ?? 'Sistem',
                'action' => $log->action,
            ])->all();
    }

    public static function log(string $action, array $context = []): void
    {
        ActivityLog::create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'context' => $context ?: null,
        ]);
    }
}

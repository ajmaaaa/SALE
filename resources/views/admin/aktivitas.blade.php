@extends('layouts.mahasiswa')
@section('header','Activity log')
@section('content')
<h1 class="page-heading">Activity log</h1><p class="page-description">Riwayat perubahan data pengguna, akademik, dan pengaturan pada sesi ini.</p><div class="surface mt-7 overflow-x-auto"><table class="admin-table"><thead><tr><th>Waktu</th><th>Pelaku</th><th>Aktivitas</th></tr></thead><tbody>@forelse(session('admin.logs',[]) as $log)<tr><td class="whitespace-nowrap">{{ $log['time'] }}</td><td>{{ $log['actor'] }}</td><td>{{ $log['action'] }}</td></tr>@empty<tr><td colspan="3">Belum ada aktivitas administratif.</td></tr>@endforelse</tbody></table></div>
@endsection

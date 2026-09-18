@extends('layouts.mahasiswa')

@section('title', 'Notifikasi | SALE')
@section('header', 'Notifikasi')

@section('content')
<div class="space-y-7">
	<header class="flex flex-col gap-2 border-b border-line/60 pb-6 sm:flex-row sm:items-end sm:justify-between">
		<div>
			<p class="text-xs font-semibold uppercase tracking-wider text-brand">Pusat informasi</p>
			<h1 class="page-heading mt-1">Notifikasi</h1>
			<p class="page-description">Pembaruan penting tentang tugas, kuis, materi, dan aktivitas course Anda.</p>
		</div>
		<span class="status bg-brand-soft text-brand">{{ count($notifications) }} pembaruan</span>
	</header>

	<section class="grid gap-4 sm:grid-cols-3" aria-label="Ringkasan notifikasi">
		<div class="surface p-4"><p class="text-xs text-muted">Perlu perhatian</p><p class="mt-1 text-lg font-bold text-ink">{{ collect($notifications)->where('type', 'deadline')->count() }}</p></div>
		<div class="surface p-4"><p class="text-xs text-muted">Materi &amp; pengumuman</p><p class="mt-1 text-lg font-bold text-ink">{{ collect($notifications)->whereIn('type', ['material', 'announcement'])->count() }}</p></div>
		<div class="surface p-4"><p class="text-xs text-muted">Jawaban tersimpan</p><p class="mt-1 text-lg font-bold text-ink">{{ collect($notifications)->where('type', 'submission')->count() }}</p></div>
	</section>

	<section class="surface overflow-hidden" aria-labelledby="notification-list-heading">
		<div class="border-b border-line/60 px-5 py-4 sm:px-6"><h2 id="notification-list-heading" class="text-sm font-bold text-ink">Aktivitas terbaru</h2><p class="mt-1 text-xs text-muted">Notifikasi diurutkan berdasarkan urgensi agar tidak ada tenggat yang terlewat.</p></div>
		@forelse($notifications as $notification)
			@php
				$icon = match($notification['type']) {
					'deadline' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'path' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
					'material' => ['bg' => 'bg-brand-soft', 'text' => 'text-brand', 'path' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8"/>'],
					'announcement' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'path' => '<path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/>'],
					'discussion' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'path' => '<path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/>'],
					default => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'path' => '<path d="m5 12 4 4L19 6"/>'],
				};
			@endphp
			<a href="{{ $notification['url'] }}" class="group flex gap-4 border-b border-line/50 px-5 py-4 transition last:border-b-0 hover:bg-canvas sm:px-6 {{ $notification['type'] === 'deadline' ? 'border-l-4 border-l-rose-400 bg-rose-50/40' : '' }}">
				<span class="min-w-0 flex-1"><span class="text-[11px] font-bold uppercase tracking-wider {{ $notification['type'] === 'deadline' ? 'text-rose-700' : $icon['text'] }}">{{ $notification['label'] }}</span><strong class="mt-1 block text-sm text-ink group-hover:text-brand">{{ $notification['title'] }}</strong><span class="mt-1 block text-xs leading-relaxed text-muted">{{ $notification['description'] }}</span><span class="mt-2 block text-[11px] text-slate-400">{{ $notification['meta'] }}</span></span>
			</a>
		@empty
			<div class="px-6 py-12 text-center"><p class="text-sm font-semibold text-ink">Belum ada notifikasi baru</p><p class="mt-1 text-xs text-muted">Pembaruan tugas, materi, dan aktivitas course akan muncul di sini.</p></div>
		@endforelse
	</section>
</div>
@endsection

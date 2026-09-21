@extends('layouts.mahasiswa')
@section('header', 'Notifikasi')
@section('content')
<h1 class="page-heading">Notifikasi</h1><p class="page-description">Pembaruan pekerjaan dalam sesi ini.</p><section class="surface mt-6 space-y-2">@forelse(session('learning.submissions', []) as $id=>$submission)@php($item = \App\Support\LearningPreview::items()[$id])<a class="block p-5 hover:bg-canvas" href="{{ route('mahasiswa.course.item', [$item['course'], $id]) }}"><h2 class="text-sm font-semibold">Jawaban {{ $item['title'] }} dikumpulkan</h2><p class="mt-1 text-xs text-muted">{{ $submission['time'] }} - Menunggu penilaian</p></a>@empty<p class="p-6 text-sm text-muted">Belum ada notifikasi baru.</p>@endforelse</section>
@endsection

@extends('layouts.mahasiswa')
@section('header', 'Forum Diskusi')
@section('title', 'Forum Diskusi | SALE')
@section('content')
<div class="space-y-6"><header><h1 class="page-heading">Forum Diskusi</h1><p class="page-description">Temukan ruang diskusi dari materi dan tugas di setiap course.</p></header>
@foreach($courses as $course)<section class="surface overflow-hidden"><h2 class="border-b border-line px-5 py-4 font-semibold">{{ $course['code'] }} · {{ $course['title'] }}</h2><div class="divide-y divide-line">@forelse(array_filter($items, fn($i) => $i['course'] === $course['id'] && $i['type'] !== 'pengumuman') as $item)<a class="flex items-center justify-between gap-4 p-5 hover:bg-canvas" href="{{ route('mahasiswa.course.item', [$course['id'], $item['id']]) }}#diskusi"><div><p class="text-xs text-muted">{{ $item['module'] }} · {{ \App\Support\LearningPreview::labels()[$item['type']] }}</p><h3 class="mt-1 text-sm font-semibold">{{ $item['title'] }}</h3></div><span class="shrink-0 text-xs text-muted">{{ count(session('learning.discussions.'.$item['id'], [])) }} pesan →</span></a>@empty<p class="p-5 text-sm text-muted">Belum ada materi untuk didiskusikan.</p>@endforelse</div></section>@endforeach</div>
@endsection

@extends('layouts.mahasiswa')
@section('header','Pengaturan sistem')
@section('content')
<div class="max-w-3xl"><h1 class="page-heading">Pengaturan sistem</h1><p class="page-description">Identitas institusi dan semester yang digunakan pada antarmuka.</p><form class="surface mt-7 space-y-5 p-6" method="post" action="{{ route('admin.settings.store') }}">@csrf @foreach(['institution'=>['Nama institusi','Universitas Contoh'],'semester'=>['Semester aktif','Ganjil 2026/2027'],'support'=>['Email bantuan','akademik@example.test']] as $key=>[$label,$default])<div><label class="form-label" for="{{ $key }}">{{ $label }}</label><input required class="field" name="{{ $key }}" id="{{ $key }}" type="{{ $key==='support' ? 'email' : 'text' }}" value="{{ old($key,session('admin.settings.'.$key,$default)) }}"></div>@endforeach<button class="button-primary">Simpan pengaturan</button></form><p class="mt-4 text-xs text-muted">Pengaturan pratinjau berlaku pada sesi ini.</p></div>
@endsection

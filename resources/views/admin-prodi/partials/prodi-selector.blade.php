{{-- Partial untuk daftar pilihan program studi mengikuti pola desain list mata kuliah SALE --}}
<div class="space-y-4 w-full">
    @if(!isset($hideHeader) || !$hideHeader)
    <header class="flex flex-col gap-4 pb-1 sm:flex-row sm:items-center sm:justify-between w-full">
        <div class="min-w-0 flex-1">
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500 mb-1">
                <a class="flex items-center gap-1.5 font-medium text-slate-500 hover:text-brand transition" href="{{ route('admin-prodi.dashboard') }}">
                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                    <span>Admin Prodi</span>
                </a>
                <svg class="h-4 w-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="font-semibold text-slate-800" aria-current="page">
                    {{ $menuTitle }}
                </span>
            </nav>
            <h1 class="page-heading">Pilih Program Studi</h1>
            <p class="page-description">{{ $description }}</p>
        </div>
    </header>
    @endif

    <div class="surface p-5 space-y-3 border border-line rounded-2xl">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-line">
            <div>
                <h2 class="text-sm font-bold text-ink">Pilih Program Studi</h2>
                <p class="text-xs text-muted mt-0.5">Silakan pilih program studi terlebih dahulu untuk mengelola data {{ strtolower($menuTitle ?? 'program studi') }}.</p>
            </div>
            <span class="text-xs text-muted font-medium shrink-0">{{ $prodis->count() }} Program Studi Terdaftar</span>
        </div>

        @if($prodis->isEmpty())
            <div class="py-10 text-center text-xs text-muted">
                Belum ada data program studi yang terdaftar atau dapat diakses saat ini.
            </div>
        @else
            <div class="space-y-2.5 pt-1">
                @foreach($prodis as $p)
                    @php
                        $url = route($targetRoute, array_merge($targetParams ?? [], ['prodi_id' => $p->id]));
                    @endphp
                    <div class="rounded-xl border border-line bg-white shadow-2xs overflow-hidden">
                        <div class="flex items-center justify-between gap-3 px-5 py-3.5 bg-canvas/40 hover:bg-canvas/60 transition-colors">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <span class="font-bold text-sm text-ink truncate">{{ $p->name }}</span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ $url }}" class="button-secondary text-xs py-1 px-3">
                                    Pilih Program Studi
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

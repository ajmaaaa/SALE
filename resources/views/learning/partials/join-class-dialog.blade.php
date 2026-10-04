@php
    $joinAsDosen = $joinAsDosen ?? request()->is('dosen*');
    $joinDialogId = $joinDialogId ?? 'join-class-modal';
    $joinInputId = $joinInputId ?? 'input-join-code';
@endphp

<dialog id="{{ $joinDialogId }}" class="fixed inset-0 m-auto rounded-2xl border border-line bg-white p-0 shadow-2xl backdrop:bg-slate-900/50 w-full max-w-md overflow-hidden h-fit">
    <div class="px-5 py-4 border-b border-line/60 flex items-center justify-between bg-canvas/30">
        <div>
            <h2 class="text-sm font-bold text-ink">Gabung Kelas Perkuliahan</h2>
            <p class="text-xs text-muted mt-0.5">Masukkan kode akses dari pengampu kelas</p>
        </div>
        <button type="button" onclick="document.getElementById('{{ $joinDialogId }}').close()" class="flex h-7 w-7 items-center justify-center rounded-lg text-muted hover:text-ink hover:bg-canvas transition cursor-pointer" aria-label="Tutup">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <form
        method="POST"
        action="{{ route('mahasiswa.join-kelas.direct') }}"
        class="p-5 space-y-4"
    >
        @csrf
        @if(session('join_error'))
            <div role="alert" class="rounded-lg border border-danger/30 bg-danger/5 px-3 py-2 text-xs font-medium text-danger">
                {{ session('join_error') }}
            </div>
        @endif
        <div>
            <label for="{{ $joinInputId }}" class="block text-xs font-semibold text-ink mb-1.5">Kode Masuk Kelas</label>
            <input
                id="{{ $joinInputId }}"
                name="code"
                type="text"
                placeholder="Contoh: A7K9M2QX"
                required
                autocomplete="off"
                autocapitalize="characters"
                value="{{ old('code') }}"
                class="field w-full font-mono uppercase text-sm tracking-wider"
            >
            <p class="mt-1.5 text-[11px] text-muted leading-relaxed">
                Masukkan kode acak 8 karakter atau tempel tautan masuk kelas dari Admin Prodi / Dosen.
            </p>
        </div>
        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-line/60">
            <button type="button" onclick="document.getElementById('{{ $joinDialogId }}').close()" class="button-secondary text-xs py-1.5 px-3.5 cursor-pointer">Batal</button>
            <button type="submit" class="button-primary text-xs py-1.5 px-4 font-semibold cursor-pointer">Gabung Kelas</button>
        </div>
    </form>
</dialog>

<script nonce="{{ $cspNonce }}">
    document.getElementById(@js($joinDialogId))?.addEventListener('click', function(e) {
        if (e.target === this) this.close();
    });
</script>

@if(session('join_error'))
    <script nonce="{{ $cspNonce }}">
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById(@js($joinDialogId))?.showModal();
        });
    </script>
@endif

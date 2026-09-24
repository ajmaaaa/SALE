@php
    $joinAsDosen = $joinAsDosen ?? request()->is('dosen*');
    $joinDialogId = $joinDialogId ?? 'join-class-modal';
    $joinInputId = $joinInputId ?? 'input-join-code';
@endphp

<dialog id="{{ $joinDialogId }}" class="backdrop:bg-black/40 rounded-xl p-0 shadow-lg border border-line/60 w-full max-w-md overflow-hidden m-auto">
    <div class="p-5 border-b border-line/60 flex items-center justify-between">
        <h2 class="text-sm font-bold text-ink">Gabung Kelas Perkuliahan</h2>
        <button type="button" onclick="document.getElementById('{{ $joinDialogId }}').close()" class="text-muted hover:text-ink text-sm p-1" aria-label="Tutup">✕</button>
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
            <label for="{{ $joinInputId }}" class="block text-xs font-semibold text-ink mb-1">Kode Masuk Kelas</label>
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
            <p class="mt-1 text-[11px] text-muted">
                Masukkan kode acak 8 karakter atau tempel tautan masuk kelas dari Admin Prodi/Kaprodi.
            </p>
        </div>
        <div class="flex items-center justify-end gap-2 pt-2">
            <button type="button" onclick="document.getElementById('{{ $joinDialogId }}').close()" class="button-secondary text-xs">Batal</button>
            <button type="submit" class="button-primary text-xs font-semibold">Gabung Kelas</button>
        </div>
    </form>
</dialog>

@if(session('join_error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById(@js($joinDialogId))?.showModal();
        });
    </script>
@endif

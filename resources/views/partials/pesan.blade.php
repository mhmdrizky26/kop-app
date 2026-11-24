{{-- Pesan berhasil atau gagal setelah menyimpan, tampil sebagai popup di tengah layar. --}}
@php
    $berhasil = session('sukses');
    $gagal = session('galat') || $errors->any();
@endphp

@if ($berhasil || $gagal)
    <div x-data="{ tampil: true }" x-init="$nextTick(() => $refs.tombol.focus()); @if ($berhasil && ! $gagal) setTimeout(() => tampil = false, 4000) @endif"
        x-show="tampil" x-cloak @keydown.escape.window="tampil = false"
        class="fixed inset-0 z-50 flex items-center justify-center p-4" role="alertdialog" aria-modal="true" aria-labelledby="pesan-judul">
        <div class="absolute inset-0 bg-slate-900/40" @click="tampil = false"
            x-show="tampil" x-transition.opacity></div>

        <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl"
            x-show="tampil" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-95 opacity-0">
            @if ($gagal)
                <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-rose-100 text-rose-600"><x-icon name="x" class="size-6" /></span>
                <h2 id="pesan-judul" class="mt-4 text-base font-semibold text-slate-900">{{ session('galat') ? 'Gagal' : 'Belum bisa disimpan' }}</h2>
                @if (session('galat'))
                    <p class="mt-1.5 text-sm text-slate-600">{{ session('galat') }}</p>
                @else
                    <p class="mt-1.5 text-sm text-slate-600">Periksa isian berikut:</p>
                    <ul class="mt-2 space-y-1 rounded-lg bg-rose-50 px-4 py-3 text-left text-sm text-rose-800">
                        @foreach ($errors->all() as $pesan)
                            <li class="flex gap-2"><span aria-hidden="true">•</span> {{ $pesan }}</li>
                        @endforeach
                    </ul>
                @endif
            @else
                <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><x-icon name="check" class="size-6" /></span>
                <h2 id="pesan-judul" class="mt-4 text-base font-semibold text-slate-900">Berhasil</h2>
                <p class="mt-1.5 text-sm text-slate-600">{{ $berhasil }}</p>
            @endif

            <button type="button" x-ref="tombol" @click="tampil = false" @class(['btn mt-5 w-full', 'btn-primary' => ! $gagal, 'btn-danger' => $gagal])>OK</button>
        </div>
    </div>
@endif

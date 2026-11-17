{{-- Navigasi bulan: panah kiri/kanan dan pemilih bulan. Butuh variabel $periode dan nama rute di $rute. --}}
@php
    $sebelum = $periode->copy()->subMonth();
    $sesudah = $periode->copy()->addMonth();
    $bisaMaju = $sesudah->lte(now());
@endphp
<div class="flex items-center justify-between gap-1 rounded-xl border border-slate-200 bg-white p-1">
    <a href="{{ route($rute, ['bulan' => $sebelum->format('Y-m')]) }}" class="flex size-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 sm:size-8" aria-label="Bulan sebelumnya">
        <x-icon name="left" class="size-4" />
    </a>
    <form method="GET" action="{{ route($rute) }}" class="min-w-0 flex-1 sm:flex-none">
        <label class="sr-only" for="pilih-bulan">Pilih bulan</label>
        <input id="pilih-bulan" type="month" name="bulan" value="{{ $periode->format('Y-m') }}" max="{{ now()->format('Y-m') }}" onchange="this.form.submit()"
            class="w-full cursor-pointer rounded-lg border-0 bg-transparent px-2 py-1 text-center text-base font-semibold sm:w-auto sm:text-left sm:text-sm text-slate-900 focus:ring-2 focus:ring-blue-200 focus:outline-none">
    </form>
    @if ($bisaMaju)
        <a href="{{ route($rute, ['bulan' => $sesudah->format('Y-m')]) }}" class="flex size-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 sm:size-8" aria-label="Bulan berikutnya">
            <x-icon name="right" class="size-4" />
        </a>
    @else
        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg text-slate-300 sm:size-8"><x-icon name="right" class="size-4" /></span>
    @endif
</div>

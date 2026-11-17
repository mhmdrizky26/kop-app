{{-- Navigasi bulan: panah kiri/kanan dan pemilih bulan. Butuh variabel $periode dan nama rute di $rute. --}}
@php
    $sebelum = $periode->copy()->subMonth();
    $sesudah = $periode->copy()->addMonth();
    $bisaMaju = $sesudah->lte(now());
@endphp
<div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1">
    <a href="{{ route($rute, ['bulan' => $sebelum->format('Y-m')]) }}" class="flex size-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900" aria-label="Bulan sebelumnya">
        <x-icon name="left" class="size-4" />
    </a>
    <form method="GET" action="{{ route($rute) }}">
        <label class="sr-only" for="pilih-bulan">Pilih bulan</label>
        <input id="pilih-bulan" type="month" name="bulan" value="{{ $periode->format('Y-m') }}" max="{{ now()->format('Y-m') }}" onchange="this.form.submit()"
            class="cursor-pointer rounded-lg border-0 bg-transparent px-2 py-1 text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-blue-200 focus:outline-none">
    </form>
    @if ($bisaMaju)
        <a href="{{ route($rute, ['bulan' => $sesudah->format('Y-m')]) }}" class="flex size-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900" aria-label="Bulan berikutnya">
            <x-icon name="right" class="size-4" />
        </a>
    @else
        <span class="flex size-8 items-center justify-center rounded-lg text-slate-300"><x-icon name="right" class="size-4" /></span>
    @endif
</div>

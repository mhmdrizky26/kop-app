@props(['anggota', 'name' => 'anggota_id', 'selected' => null])
@php
    $daftar = $anggota->map(fn ($a) => ['id' => $a->id, 'nomor' => $a->nomor, 'nama' => $a->nama])->values();
@endphp
<div x-data="pilihAnggota(@js($daftar), @js(old($name, $selected)))" @click.outside="buka = false" {{ $attributes->merge(['class' => 'relative']) }}>
    <input type="hidden" name="{{ $name }}" :value="terpilih ? terpilih.id : ''">
    <div class="relative">
        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"><x-icon name="search" class="size-4" /></span>
        <input type="text" class="input pl-9 pr-9" placeholder="Ketik nama atau nomor anggota" autocomplete="off"
            x-model="cari" @focus="buka = true; $event.target.select()" @input="terpilih = null; buka = true; $dispatch('anggota', null)"
            @keydown.enter.prevent="pilih(hasil[0])" @keydown.escape="buka = false">
        <button type="button" x-show="terpilih" x-cloak @click="cari = ''; kosongkan(); $nextTick(() => $el.previousElementSibling.focus())"
            class="absolute inset-y-0 right-2 flex items-center text-slate-400 hover:text-slate-600" aria-label="Ganti anggota">
            <x-icon name="x" class="size-4" />
        </button>
    </div>
    <ul x-show="buka" x-cloak class="absolute z-20 mt-1.5 max-h-64 w-full overflow-auto rounded-xl border border-slate-200 bg-white p-1 shadow-lg shadow-slate-200/60">
        <template x-for="a in hasil" :key="a.id">
            <li>
                <button type="button" @click="pilih(a)" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm hover:bg-blue-50">
                    <span class="w-7 text-xs text-slate-400 tabular-nums" x-text="a.nomor"></span>
                    <span class="text-slate-800" x-text="a.nama"></span>
                </button>
            </li>
        </template>
        <li x-show="hasil.length === 0" class="px-3 py-2 text-sm text-slate-500">Anggota tidak ditemukan.</li>
    </ul>
</div>

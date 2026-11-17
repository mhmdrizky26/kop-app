{{-- Empat kartu saldo satu anggota. Butuh $saldo (pokok, wajib, manasuka, simpanan, pinjaman). --}}
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat utama label="Total simpanan" :nilai="rupiah($saldo['simpanan'])" ikon="book" :catatan="'Termasuk simpanan pokok '.rupiah($saldo['pokok'])" />
    <x-stat label="Simpanan wajib" :nilai="rupiah($saldo['wajib'])" ikon="down-left" />
    <x-stat label="Simpanan manasuka" :nilai="rupiah($saldo['manasuka'])" ikon="wallet" catatan="Bisa ditarik" />
    <x-stat label="Sisa pinjaman" :nilai="rupiah($saldo['pinjaman'])" ikon="banknote" :catatan="$saldo['pinjaman'] > 0 ? 'Masih berjalan' : 'Tidak ada pinjaman'" />
</div>

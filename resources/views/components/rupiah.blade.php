@props(['name', 'value' => null, 'placeholder' => '0', 'required' => false, 'ringkas' => false])
@php
    // old() memakai tanda titik untuk nama berbentuk array, misalnya angsuran[3] menjadi angsuran.3
    $kunci = str_replace(['[', ']'], ['.', ''], $name);
    $awal = old($kunci, $value);
@endphp
<div x-data="isianRupiah(@js($awal === null || $awal === '' ? '' : (int) $awal))" {{ $attributes->merge(['class' => 'relative']) }}>
    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-slate-400">Rp</span>
    <input type="text" inputmode="numeric" autocomplete="off" placeholder="{{ $placeholder }}" @required($required)
        :value="format(nilai)" @input="ketik($event)"
        class="input pl-9 text-right tabular-nums {{ $ringkas ? 'py-1.5' : '' }}">
    <input type="hidden" name="{{ $name }}" :value="nilai">
</div>

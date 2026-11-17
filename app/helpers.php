<?php

if (! function_exists('rupiah')) {
    /** 1250000 menjadi "Rp 1.250.000". */
    function rupiah(int|float|string|null $nilai, bool $simbol = true): string
    {
        $nilai = (float) $nilai;
        $teks = number_format(abs($nilai), 0, ',', '.');

        return ($nilai < 0 ? '-' : '').($simbol ? 'Rp ' : '').$teks;
    }
}

if (! function_exists('rupiah_singkat')) {
    /** 1250000 menjadi "Rp 1,25 jt", dipakai di kartu ringkasan. */
    function rupiah_singkat(int|float|string|null $nilai): string
    {
        $nilai = (float) $nilai;
        $abs = abs($nilai);
        $tanda = $nilai < 0 ? '-' : '';

        if ($abs >= 1_000_000_000) {
            return $tanda.'Rp '.rtrim(rtrim(number_format($abs / 1_000_000_000, 2, ',', '.'), '0'), ',').' M';
        }
        if ($abs >= 1_000_000) {
            return $tanda.'Rp '.rtrim(rtrim(number_format($abs / 1_000_000, 2, ',', '.'), '0'), ',').' jt';
        }

        return rupiah($nilai);
    }
}

if (! function_exists('angka')) {
    /** Angka tanpa simbol; nol ditampilkan sebagai tanda strip supaya tabel mudah dibaca. */
    function angka(int|float|string|null $nilai): string
    {
        return (float) $nilai == 0.0 ? '–' : rupiah($nilai, false);
    }
}

if (! function_exists('persen')) {
    function persen(int|float|string|null $nilai): string
    {
        return rtrim(rtrim(number_format((float) $nilai, 2, ',', '.'), '0'), ',').'%';
    }
}

<?php

/*
 * Identitas koperasi untuk kop laporan cetak, PDF, dan Excel.
 */
return [
    'nama' => env('KOPERASI_NAMA', env('APP_NAME', 'Koperasi')),
    'alamat' => env('KOPERASI_ALAMAT', ''),
    'kota' => env('KOPERASI_KOTA', ''),
];

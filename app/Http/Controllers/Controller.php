<?php

namespace App\Http\Controllers;

use App\Models\TahunBuku;
use Carbon\Carbon;
use Illuminate\Http\Request;

abstract class Controller
{
    /** Membaca parameter ?bulan=2026-11 menjadi tanggal 1 bulan itu; bawaan bulan ini, tidak boleh bulan depan. */
    protected function periode(Request $request): Carbon
    {
        $bulan = $request->input('bulan');
        $periode = now()->startOfMonth();

        if (is_string($bulan) && preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $periode = Carbon::createFromFormat('Y-m-d', $bulan.'-01')->startOfDay();
        }

        return $periode->gt(now()) ? now()->startOfMonth() : $periode;
    }

    protected function tahun(): TahunBuku
    {
        return TahunBuku::aktif();
    }
}

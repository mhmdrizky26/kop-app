<?php

namespace App\Services;

use App\Models\TahunBuku;
use Carbon\Carbon;

/**
 * Menyusun laporan menjadi dokumen yang sama untuk cetak, PDF, dan Excel:
 * judul, periode, dan beberapa bagian berupa tabel. Tipe kolom: no, kode, teks, uang, persen.
 *
 * Bentuk dokumen:
 * ['judul' => string, 'periode' => string, 'orientasi' => 'portrait'|'landscape', 'nama_file' => string,
 *  'tahun' => TahunBuku, 'bagian' => [['judul' => string, 'kolom' => [[label, tipe], ...], 'baris' => [[...]], 'jumlah' => [[...]]]]]
 */
class DokumenLaporan
{
    public static function rekap(Carbon $periode, TahunBuku $tahun): array
    {
        $rekap = Keuangan::rekapBulanan($periode);
        $j = $rekap['jumlah'];

        $baris = [];
        foreach ($rekap['baris'] as $i => $b) {
            $baris[] = [
                $i + 1, $b['anggota']->kode, $b['anggota']->nama, $b['wajib'], $b['manasuka'], $b['tarik'],
                $b['pinjaman_baru'], $b['angsuran'], $b['jasa'], $b['simpanan'], $b['pinjaman'],
            ];
        }

        return [
            'judul' => 'Rekapitulasi Bulanan Simpanan dan Pinjaman Anggota',
            'periode' => 'Bulan '.$periode->translatedFormat('F Y'),
            'orientasi' => 'landscape',
            'nama_file' => 'rekap-bulanan-'.$periode->format('Y-m'),
            'tahun' => $tahun,
            'bagian' => [[
                'judul' => null,
                'kolom' => [
                    ['No', 'no'], ['ID', 'kode'], ['Nama Anggota', 'teks'], ['Simpanan Wajib', 'uang'], ['Simpanan Manasuka', 'uang'],
                    ['Penarikan', 'uang'], ['Pinjaman Baru', 'uang'], ['Angsuran', 'uang'], ['Jasa', 'uang'],
                    ['Saldo Simpanan', 'uang'], ['Sisa Pinjaman', 'uang'],
                ],
                'baris' => $baris,
                'jumlah' => [[null, null, 'Jumlah', $j['wajib'], $j['manasuka'], $j['tarik'], $j['pinjaman_baru'], $j['angsuran'], $j['jasa'], $j['simpanan'], $j['pinjaman']]],
            ]],
        ];
    }

    public static function shu(TahunBuku $tahun): array
    {
        $shu = Keuangan::shu($tahun);
        // Pendapatan selalu berisi jasa dan provisi, biaya selalu berisi uang duduk, jadi daftar tidak pernah kosong.
        $daftar = fn (array $pos): array => array_map(null, range(1, count($pos)), array_keys($pos), array_values($pos));

        $pembagian = [];
        foreach (array_values($shu['pembagian']) as $i => $p) {
            $pembagian[] = [$i + 1, $p['nama'], $p['persen'], $p['jumlah']];
        }

        $anggota = [];
        foreach (array_values($shu['perAnggota']) as $i => $b) {
            $anggota[] = [$i + 1, $b['anggota']->kode, $b['anggota']->nama, $b['simpanan'], $b['jasa_dibayar'], $b['dari_simpanan'], $b['dari_pinjaman'], $b['total']];
        }
        $per = collect($shu['perAnggota']);

        return [
            'judul' => 'Laporan Perhitungan Sisa Hasil Usaha',
            'periode' => self::periodeTahun($tahun),
            'orientasi' => 'portrait',
            'nama_file' => 'laporan-shu-'.str_replace('/', '-', $tahun->nama),
            'tahun' => $tahun,
            'bagian' => [
                [
                    'judul' => 'I. Pendapatan',
                    'kolom' => [['No', 'no'], ['Uraian', 'teks'], ['Jumlah (Rp)', 'uang']],
                    'baris' => $daftar($shu['pendapatan']),
                    'jumlah' => [[null, 'Jumlah Pendapatan', $shu['totalPendapatan']]],
                ],
                [
                    'judul' => 'II. Biaya',
                    'kolom' => [['No', 'no'], ['Uraian', 'teks'], ['Jumlah (Rp)', 'uang']],
                    'baris' => $daftar($shu['biaya']),
                    'jumlah' => [
                        [null, 'Jumlah Biaya', $shu['totalBiaya']],
                        [null, 'Sisa Hasil Usaha Bersih (I - II)', $shu['bersih']],
                    ],
                ],
                [
                    'judul' => 'III. Pembagian Sisa Hasil Usaha',
                    'kolom' => [['No', 'no'], ['Pos Pembagian', 'teks'], ['Persentase', 'persen'], ['Jumlah (Rp)', 'uang']],
                    'baris' => $pembagian,
                    'jumlah' => [[null, 'Jumlah', collect($shu['pembagian'])->sum('persen'), collect($shu['pembagian'])->sum('jumlah')]],
                ],
                [
                    'judul' => 'IV. Bagian Sisa Hasil Usaha Tiap Anggota',
                    'kolom' => [
                        ['No', 'no'], ['ID', 'kode'], ['Nama Anggota', 'teks'], ['Saldo Simpanan', 'uang'], ['Jasa Dibayar', 'uang'],
                        ['Dari Jasa Simpanan', 'uang'], ['Dari Jasa Pinjaman', 'uang'], ['Bagian SHU', 'uang'],
                    ],
                    'baris' => $anggota,
                    'jumlah' => [[null, null, 'Jumlah', $shu['totalSimpanan'], $shu['totalJasa'], $per->sum('dari_simpanan'), $per->sum('dari_pinjaman'), $per->sum('total')]],
                ],
            ],
        ];
    }

    public static function neraca(TahunBuku $tahun): array
    {
        $n = Keuangan::neraca($tahun);
        $kolomHarta = fn (string $judul): array => [['No', 'no'], [$judul, 'teks'], ['Saldo Awal', 'uang'], ['Masuk', 'uang'], ['Keluar', 'uang'], ['Saldo Akhir', 'uang']];
        $baris = function (array $pos): array {
            $hasil = [];
            foreach (array_keys($pos) as $i => $nama) {
                $hasil[] = [$i + 1, $nama, $pos[$nama]['awal'], $pos[$nama]['masuk'], $pos[$nama]['keluar'], $pos[$nama]['akhir']];
            }

            return $hasil;
        };
        $jumlahHarta = fn (array $j): array => [null, 'Jumlah', $j['awal'], $j['masuk'], $j['keluar'], $j['akhir']];
        $daftar = fn (array $pos): array => array_map(null, range(1, count($pos)), array_keys($pos), array_values($pos));

        $pembagian = [];
        foreach (array_values($n['pembagian']) as $i => $p) {
            $pembagian[] = [$i + 1, $p['nama'], $p['persen'], $p['jumlah']];
        }
        $kekayaan = [];
        foreach ($n['kekayaan'] as $nama => $k) {
            $kekayaan[] = [$nama, $k['jumlah'], $k['laba'], $k['jumlah'] + $k['laba']];
        }
        $kk = collect($n['kekayaan']);

        return [
            'judul' => 'Neraca',
            'periode' => self::periodeTahun($tahun),
            'orientasi' => 'portrait',
            'nama_file' => 'neraca-'.str_replace('/', '-', $tahun->nama),
            'tahun' => $tahun,
            'bagian' => [
                [
                    'judul' => 'I. Keadaan Harta Kekayaan',
                    'kolom' => $kolomHarta('Sumber Harta'),
                    'baris' => $baris($n['sumber']),
                    'jumlah' => [$jumlahHarta($n['jumlahSumber'])],
                ],
                [
                    'judul' => 'II. Posisi Harta',
                    'kolom' => $kolomHarta('Posisi Harta'),
                    'baris' => $baris($n['posisi']),
                    'jumlah' => [
                        $jumlahHarta($n['jumlahPosisi']),
                        [null, 'Jumlah Kekayaan di Luar Kas', null, null, null, $n['jumlahPosisi']['akhir']],
                        [null, 'Jumlah Kekayaan di Kas', null, null, null, $n['diKas']],
                    ],
                ],
                [
                    'judul' => 'III. Laba 1 Tahun',
                    'kolom' => [['No', 'no'], ['Uraian', 'teks'], ['Jumlah (Rp)', 'uang']],
                    'baris' => $daftar($n['laba']),
                    'jumlah' => [[null, 'Jumlah', array_sum($n['laba'])]],
                ],
                [
                    'judul' => 'IV. Pengeluaran Biaya',
                    'kolom' => [['No', 'no'], ['Uraian', 'teks'], ['Jumlah (Rp)', 'uang']],
                    'baris' => $daftar($n['biaya']),
                    'jumlah' => [
                        [null, 'Jumlah', array_sum($n['biaya'])],
                        [null, 'SHU Bersih (III - IV)', $n['shuBersih']],
                    ],
                ],
                [
                    'judul' => 'V. Pembagian Keuntungan Koperasi',
                    'kolom' => [['No', 'no'], ['Pos Pembagian', 'teks'], ['Persentase', 'persen'], ['Jumlah (Rp)', 'uang']],
                    'baris' => $pembagian,
                    'jumlah' => [[null, 'Jumlah', collect($n['pembagian'])->sum('persen'), collect($n['pembagian'])->sum('jumlah')]],
                ],
                [
                    'judul' => 'VI. Posisi Kekayaan',
                    'kolom' => [['Posisi', 'teks'], ['Jumlah', 'uang'], ['Laba Usaha', 'uang'], ['Total', 'uang']],
                    'baris' => $kekayaan,
                    'jumlah' => [['Jumlah Harta', $kk->sum('jumlah'), $kk->sum('laba'), $kk->sum(fn ($k) => $k['jumlah'] + $k['laba'])]],
                ],
            ],
        ];
    }

    private static function periodeTahun(TahunBuku $tahun): string
    {
        return 'Tahun Buku '.$tahun->nama.' ('.$tahun->mulai->translatedFormat('j F Y').' s.d. '.$tahun->selesai->translatedFormat('j F Y').')';
    }
}

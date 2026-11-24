<?php

namespace Database\Seeders;

use App\Models\Anggota;
use App\Models\Inventaris;
use App\Models\KasLain;
use App\Models\Pinjaman;
use App\Models\TahunBuku;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\Keuangan;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data contoh untuk demo. Semua nama dan angka fiktif.
 * Login pengurus: admin / password (akun dan pengaturan), bendahara / password (editor),
 * ketua, pemeriksa1, pemeriksa2 / password (lihat saja).
 * Login anggota: nama pengguna dari nama, misalnya rina.kartika.
 */
class DatabaseSeeder extends Seeder
{
    private const NAMA = [
        'Rina Kartika, S.Pd.', 'Bambang Sutrisno', 'Sri Wahyuni, S.Pd.', 'Dedi Kurniawan, S.Pd.', 'Maya Puspitasari',
        'Agus Setiawan, S.Pd.', 'Fitri Handayani, S.Pd.', 'Hendra Gunawan', 'Novi Anggraeni, S.Pd.', 'Yusuf Hidayat, S.Ag.',
        'Lestari Dewi, S.Pd.', 'Rudi Hartono', 'Wulan Sari, S.Pd.', 'Eko Prasetyo, S.Kom.', 'Indri Astuti, S.Pd.',
        'Fajar Nugroho, S.Pd.', 'Sinta Maharani', 'Dimas Saputra, S.Pd.', 'Ratih Permata, S.Pd.', 'Andi Wijaya',
        'Nurul Aini, S.Pd.', 'Budi Santoso, S.Pd.', 'Citra Lestari, S.Pd.', 'Gilang Ramadhan',
    ];

    public function run(): void
    {
        mt_srand(2026);

        // Tahun buku berjalan April sampai Maret, mengikuti buku koperasi.
        $mulai = now()->month >= 4 ? Carbon::create(now()->year, 4, 1) : Carbon::create(now()->year - 1, 4, 1);
        $tahun = TahunBuku::create([
            'nama' => $mulai->year.'/'.($mulai->year + 1),
            'mulai' => $mulai->toDateString(),
            'selesai' => $mulai->copy()->addYear()->subDay()->toDateString(),
            'aktif' => true,
            'awal_dana_sosial' => 15_000_000, 'awal_cadangan' => 32_000_000, 'awal_modal_warung' => 108_000_000,
            'awal_pinjaman_luar' => 34_000_000, 'awal_paket_munggahan' => 21_990_000,
            'nama_ketua' => 'Agus Setiawan, S.Pd.', 'nama_bendahara' => 'Rina Kartika, S.Pd.',
            'nama_pemeriksa_1' => 'Yusuf Hidayat, S.Ag.', 'nama_pemeriksa_2' => 'Lestari Dewi, S.Pd.',
        ])->fresh(); // fresh() memuat nilai bawaan tarif dari basis data

        // Inventaris yang dimiliki sebelum tahun buku ini dimulai, dengan umur pakai untuk penyusutan.
        foreach ([
            ['Laptop', 1, 'unit', '2022-07-12', 7_500_000, 4, 'baik', 'Ruang koperasi', 'Untuk pembukuan'],
            ['Printer', 1, 'unit', '2021-03-05', 2_500_000, 4, 'rusak_ringan', 'Ruang koperasi', 'Tinta sering macet'],
            ['Lemari arsip', 2, 'unit', '2019-08-20', 3_000_000, 8, 'baik', 'Ruang koperasi', null],
            ['Brankas', 1, 'unit', '2018-01-15', 2_000_000, 8, 'baik', 'Ruang koperasi', 'Penyimpanan uang kas'],
            ['Meja kerja', 2, 'unit', '2019-08-20', 1_600_000, 8, 'baik', 'Ruang koperasi', null],
            ['Kursi kerja', 4, 'unit', '2023-08-20', 1_200_000, 4, 'baik', 'Ruang koperasi', null],
            ['Kalkulator', 2, 'unit', '2023-02-10', 400_000, 4, 'baik', 'Ruang koperasi', null],
            ['Rak barang warung', 3, 'unit', '2020-06-01', 2_100_000, 8, 'baik', 'Warung KGB', null],
            ['Lemari pendingin', 1, 'unit', '2020-06-01', 4_500_000, 8, 'baik', 'Warung KGB', 'Untuk minuman warung'],
        ] as [$namaBarang, $jumlah, $satuan, $tanggalBeli, $nilai, $umur, $kondisi, $lokasi, $catatan]) {
            Inventaris::create([
                'nama' => $namaBarang, 'jumlah' => $jumlah, 'satuan' => $satuan, 'tanggal_perolehan' => $tanggalBeli,
                'nilai' => $nilai, 'umur_tahun' => $umur, 'kondisi' => $kondisi, 'lokasi' => $lokasi, 'keterangan' => $catatan,
            ]);
        }
        // Barang yang rusak dan dihapuskan pada tahun buku ini, saat nilai bukunya belum habis.
        Inventaris::create([
            'nama' => 'Dispenser', 'jumlah' => 1, 'satuan' => 'unit', 'tanggal_perolehan' => '2023-09-01', 'nilai' => 800_000, 'umur_tahun' => 4,
            'kondisi' => 'rusak_berat', 'lokasi' => 'Ruang koperasi', 'keterangan' => 'Rusak, tidak bisa diperbaiki',
            'tanggal_dihapus' => $mulai->copy()->addMonths(2)->day(10)->toDateString(),
        ]);

        User::create(['name' => 'Admin', 'username' => 'admin', 'password' => 'password', 'role' => 'admin']);
        $pengurus = User::create(['name' => 'Bendahara', 'username' => 'bendahara', 'password' => 'password', 'role' => 'pengurus']);
        // Akun lihat saja untuk ketua dan badan pemeriksa.
        foreach (['ketua' => $tahun->nama_ketua, 'pemeriksa1' => $tahun->nama_pemeriksa_1, 'pemeriksa2' => $tahun->nama_pemeriksa_2] as $username => $nama) {
            User::create(['name' => $nama, 'username' => $username, 'password' => 'password', 'role' => 'pengawas']);
        }

        $awal = $mulai->copy()->subDay();
        $anggota = collect();
        foreach (self::NAMA as $i => $nama) {
            // Sebagian besar potong gaji, satu dari lima campuran (manasuka disetor langsung), sisanya setor langsung.
            $cara = match ($i % 5) {
                0, 1, 2 => ['potong_gaji', 'potong_gaji', 'potong_gaji'],
                3 => ['potong_gaji', 'setor', 'potong_gaji'],
                default => ['setor', 'setor', 'setor'],
            };
            $a = Anggota::create([
                'nomor' => $i + 1,
                'nama' => $nama,
                'jabatan' => $i % 6 === 4 ? 'Tata Usaha' : 'Guru',
                'no_hp' => '08'.mt_rand(11, 99).'-'.mt_rand(1000, 9999).'-'.mt_rand(1000, 9999),
                'tanggal_masuk' => Carbon::create(mt_rand(2014, 2024), mt_rand(1, 12), 1)->toDateString(),
                'bayar_wajib' => $cara[0], 'bayar_manasuka' => $cara[1], 'bayar_pinjaman' => $cara[2],
                'setoran_manasuka' => $i % 5 === 3 ? 100000 : [0, 0, 50000, 100000, 100000, 200000, 250000, 500000][mt_rand(0, 7)],
            ]);
            User::create([
                'name' => $nama, 'username' => User::namaPenggunaUnik($nama),
                'password' => Str::random(40), 'role' => 'anggota', 'anggota_id' => $a->id,
            ]);

            $this->catat($a, $awal, 'simpanan_pokok', 5000, 'saldo_awal', 'Saldo awal tahun buku');
            $this->catat($a, $awal, 'simpanan_wajib', mt_rand(20, 190) * 100000, 'saldo_awal', 'Saldo awal tahun buku');
            if ($i % 4 !== 3) {
                $this->catat($a, $awal, 'simpanan_manasuka', mt_rand(5, 220) * 100000, 'saldo_awal', 'Saldo awal tahun buku');
            }
            $anggota->push($a);
        }

        // Pinjaman yang sudah berjalan sebelum tahun buku dimulai.
        foreach ([1 => [20, 20], 3 => [10, 10], 6 => [30, 24], 8 => [15, 12], 11 => [5, 10], 14 => [25, 20], 17 => [12, 12], 20 => [8, 10]] as $idx => [$juta, $tenor]) {
            $umur = mt_rand(2, 6);
            $pinjaman = $this->pinjaman($anggota[$idx], $mulai->copy()->subMonths($umur)->day(mt_rand(3, 25)), $juta * 1_000_000, $tenor, $tahun, 'saldo_awal');
            $this->catat($anggota[$idx], $awal, 'angsuran', $pinjaman->angsuran_pokok * ($umur - 1), 'saldo_awal', 'Angsuran sebelum tahun buku', null, $pinjaman->id);
        }

        // Bulan-bulan yang sudah lewat: semua tagihan dibayar, ditambah beberapa transaksi lain.
        $bulanIni = now()->startOfMonth();
        $pinjamanBaru = [2 => [5, [10, 10]], 4 => [12, [15, 12]], 6 => [22, [6, 10]]];
        foreach ($tahun->bulan() as $n => $bulan) {
            if ($bulan->gt($bulanIni)) {
                break;
            }
            $berjalan = $bulan->equalTo($bulanIni);

            foreach ($anggota as $i => $a) {
                $hariGaji = $bulan->copy()->day(min(5, $berjalan ? now()->day : 28));
                $hariSetor = $bulan->copy()->day(min(mt_rand(6, 20), $berjalan ? now()->day : 28));

                // Bulan berjalan: potong gaji belum ditandai, sebagian penyetor langsung sudah bayar
                // (anggota campuran jadi berstatus "dibayar sebagian").
                if ($berjalan) {
                    if ($i % 2 !== 0) {
                        Keuangan::bayarSisaTagihan($a, $tahun, $bulan, 'setor', $hariSetor, $pengurus->id);
                    }

                    continue;
                }
                Keuangan::bayarSisaTagihan($a, $tahun, $bulan, 'potong_gaji', $hariGaji, $pengurus->id);
                Keuangan::bayarSisaTagihan($a, $tahun, $bulan, 'setor', $hariSetor, $pengurus->id);
            }

            if ($berjalan) {
                break;
            }

            // Setoran tambahan manasuka (lewat tagihan) dan penarikan simpanan manasuka.
            foreach (range(1, mt_rand(2, 4)) as $_) {
                $a = $anggota[mt_rand(0, $anggota->count() - 1)];
                Keuangan::bayarTagihan($a, $tahun, $bulan, ['manasuka' => mt_rand(2, 30) * 100000], 'setor', $bulan->copy()->day(mt_rand(21, 28)), $pengurus->id);
            }
            $a = $anggota[mt_rand(0, $anggota->count() - 1)];
            $saldo = Keuangan::saldoAnggota($a->id)['manasuka'];
            if ($saldo >= 1_000_000) {
                $this->catat($a, $bulan->copy()->day(mt_rand(10, 27)), 'tarik_manasuka', (int) (floor($saldo * mt_rand(10, 40) / 100 / 100000) * 100000), 'manual', 'Penarikan simpanan');
            }

            if (isset($pinjamanBaru[$n])) {
                [$idx, [$juta, $tenor]] = $pinjamanBaru[$n];
                $this->pinjaman($anggota[$idx], $bulan->copy()->day(mt_rand(10, 22)), $juta * 1_000_000, $tenor, $tahun, 'manual');
            }

            $kas = fn (int $hari, string $tipe, string $kategori, string $keterangan, int $jumlah) => KasLain::create([
                'tanggal' => $bulan->copy()->day($hari)->toDateString(), 'tipe' => $tipe, 'kategori' => $kategori,
                'keterangan' => $keterangan, 'jumlah' => $jumlah,
            ]);

            // Pendapatan usaha setiap bulan.
            $kas(28, 'pendapatan', 'Laba warung KGB', 'Laba bulan '.$bulan->translatedFormat('F'), mt_rand(22, 36) * 100000);
            $kas(25, 'pendapatan', 'Fotokopi', 'Jasa fotokopi bulan '.$bulan->translatedFormat('F'), mt_rand(15, 45) * 10000);
            if ($n % 3 === 0) {
                $kas(20, 'pendapatan', 'Unit jual beli', 'Penjualan seragam dan ATK', mt_rand(5, 15) * 100000);
            }
            if (in_array($n, [2, 5], true)) {
                $kas(18, 'pendapatan', 'PSAS kelas 7', 'Penyediaan soal PSAS kelas 7', 1_250_000);
                $kas(18, 'pendapatan', 'PSAS kelas 8-9', 'Penyediaan soal PSAS kelas 8 dan 9', 2_400_000);
            }
            if ($n === 4) {
                $kas(12, 'pendapatan', 'Pendapatan lain-lain', 'Bunga tabungan bank', 185_000);
            }

            // Biaya usaha.
            if ($n % 2 === 0) {
                $kas(mt_rand(10, 20), 'biaya', 'Konsumsi rapat', 'Rapat pengurus', mt_rand(15, 40) * 10000);
            }
            if ($n % 3 === 1) {
                $kas(mt_rand(3, 25), 'biaya', 'Alat tulis dan fotokopi', 'Kertas dan tinta printer', mt_rand(10, 25) * 10000);
            }
            match ($n) {
                1 => $kas(14, 'biaya', 'Servis dan perawatan inventaris', 'Servis laptop', 250_000),
                3 => $kas(11, 'biaya', 'Transportasi', 'Belanja barang warung ke pasar', 150_000),
                4 => $kas(9, 'biaya', 'Perlengkapan kantor', 'Charger laptop', 250_000),
                5 => $kas(2, 'biaya', 'Rekap buku kas', 'Honor rekap buku kas', 500_000),
                default => null,
            };

            // Perpindahan modal dan dana (di luar SHU). Paket munggahan tahun lalu dibebankan ke SHU otomatis.
            match ($n) {
                1 => $kas(10, 'biaya', 'Modal warung KGB', 'Tambahan modal belanja warung', 5_000_000),
                2 => $kas(16, 'biaya', 'Pinjaman di luar anggota', 'Pinjaman ke kantin sekolah', 13_000_000),
                3 => $kas(15, 'pendapatan', 'Pengembalian modal warung KGB', 'Setoran modal dari warung', 10_000_000),
                4 => $kas(22, 'biaya', 'Dana sosial', 'Santunan anggota sakit', 1_500_000),
                5 => $kas(19, 'pendapatan', 'Pengembalian pinjaman di luar anggota', 'Cicilan pinjaman kantin', 4_000_000),
                6 => $kas(7, 'biaya', 'Pinjaman paket munggahan', 'Paket munggahan anggota', 22_680_000),
                default => null,
            };
            if ($n === 3) {
                // Barang baru tahun ini, dicatat di inventaris dan di kas.
                Inventaris::create([
                    'nama' => 'Proyektor', 'jumlah' => 1, 'satuan' => 'unit', 'tanggal_perolehan' => $bulan->copy()->day(21)->toDateString(),
                    'nilai' => 4_200_000, 'umur_tahun' => 4, 'kondisi' => 'baik', 'lokasi' => 'Ruang rapat', 'keterangan' => 'Untuk rapat dan RAT',
                ]);
                $kas(21, 'biaya', 'Pembelian inventaris', 'Proyektor (1 unit)', 4_200_000);
            }
        }
    }

    private function catat(Anggota $a, Carbon $tanggal, string $jenis, int $jumlah, string $sumber, ?string $keterangan = null, ?string $metode = null, ?int $pinjamanId = null): void
    {
        if ($jumlah <= 0) {
            return;
        }
        Transaksi::create([
            'anggota_id' => $a->id, 'pinjaman_id' => $pinjamanId, 'tanggal' => $tanggal->toDateString(),
            'periode' => $tanggal->copy()->startOfMonth()->toDateString(), 'jenis' => $jenis, 'metode' => $metode,
            'sumber' => $sumber, 'jumlah' => $jumlah, 'keterangan' => $keterangan,
        ]);
    }

    private function pinjaman(Anggota $a, Carbon $tanggal, int $pokok, int $tenor, TahunBuku $tahun, string $sumber): Pinjaman
    {
        $provisi = $sumber === 'manual' ? (int) round($pokok * $tahun->provisi_persen / 100) : 0;
        $pinjaman = Pinjaman::create([
            'anggota_id' => $a->id, 'tanggal' => $tanggal->toDateString(), 'pokok' => $pokok, 'tenor' => $tenor,
            'angsuran_pokok' => (int) (ceil($pokok / $tenor / 1000) * 1000),
            'jasa_persen' => $tahun->jasa_persen, 'jasa_metode' => $tahun->jasa_metode, 'provisi' => $provisi,
        ]);
        $this->catat($a, $tanggal, 'pinjaman', $pokok, $sumber, null, null, $pinjaman->id);
        $this->catat($a, $tanggal, 'provisi', $provisi, $sumber, null, 'setor', $pinjaman->id);

        return $pinjaman;
    }
}

<?php

namespace Database\Seeders;

use App\Models\Anggota;
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
 * Login pengurus: bendahara / password. Login anggota: ID anggota, misalnya KGB001.
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
        ])->fresh(); // fresh() memuat nilai bawaan tarif dari basis data

        $pengurus = User::create(['name' => 'Bendahara', 'username' => 'bendahara', 'password' => 'password', 'role' => 'pengurus']);

        $awal = $mulai->copy()->subDay();
        $anggota = collect();
        foreach (self::NAMA as $i => $nama) {
            $a = Anggota::create([
                'nomor' => $i + 1,
                'nama' => $nama,
                'jabatan' => $i % 6 === 4 ? 'Tata Usaha' : 'Guru',
                'no_hp' => '08'.mt_rand(11, 99).'-'.mt_rand(1000, 9999).'-'.mt_rand(1000, 9999),
                'tanggal_masuk' => Carbon::create(mt_rand(2014, 2024), mt_rand(1, 12), 1)->toDateString(),
                'potong_gaji' => $i % 5 < 3,
                'setoran_manasuka' => [0, 0, 50000, 100000, 100000, 200000, 250000, 500000][mt_rand(0, 7)],
            ]);
            User::create([
                'name' => $nama, 'username' => $a->kode,
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
                // Bulan berjalan: potong gaji belum ditandai, sebagian penyetor langsung sudah bayar.
                if ($berjalan && ($a->potong_gaji || $i % 2 === 0)) {
                    continue;
                }
                $a->load('pinjamanAktif');
                $hari = $a->potong_gaji ? 5 : mt_rand(6, 20);
                $tanggal = $bulan->copy()->day(min($hari, $berjalan ? now()->day : 28));
                Keuangan::bayarTagihan($a, $bulan, Keuangan::tagihan($a, $tahun, $bulan), $a->potong_gaji ? 'potong_gaji' : 'setor', $tanggal, $pengurus->id);
            }

            if ($berjalan) {
                break;
            }

            // Setoran tambahan dan penarikan simpanan manasuka.
            foreach (range(1, mt_rand(2, 4)) as $_) {
                $a = $anggota[mt_rand(0, $anggota->count() - 1)];
                $this->catat($a, $bulan->copy()->day(mt_rand(8, 26)), 'simpanan_manasuka', mt_rand(2, 30) * 100000, 'manual', 'Setoran tambahan', 'setor');
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

            KasLain::create(['tanggal' => $bulan->copy()->day(28)->toDateString(), 'tipe' => 'pendapatan', 'kategori' => 'Laba warung koperasi', 'keterangan' => 'Laba bulan '.$bulan->translatedFormat('F'), 'jumlah' => mt_rand(22, 36) * 100000]);
            if ($n % 2 === 0) {
                KasLain::create(['tanggal' => $bulan->copy()->day(mt_rand(10, 20))->toDateString(), 'tipe' => 'biaya', 'kategori' => 'Konsumsi rapat', 'keterangan' => 'Rapat pengurus', 'jumlah' => mt_rand(15, 40) * 10000]);
            }
            if ($n % 3 === 1) {
                KasLain::create(['tanggal' => $bulan->copy()->day(mt_rand(3, 25))->toDateString(), 'tipe' => 'biaya', 'kategori' => 'Alat tulis dan fotokopi', 'keterangan' => 'Kertas dan tinta printer', 'jumlah' => mt_rand(10, 25) * 10000]);
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

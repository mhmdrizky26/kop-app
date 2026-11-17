<?php

namespace App\Services;

use App\Models\Anggota;
use App\Models\KasLain;
use App\Models\Pinjaman;
use App\Models\TahunBuku;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Semua hitungan keuangan koperasi ada di sini.
 * Sumber datanya hanya tabel transaksi (dan kas_lain), sehingga angka di setiap halaman selalu sama.
 */
class Keuangan
{
    private const SALDO_KOSONG = [
        'pokok' => 0, 'wajib' => 0, 'manasuka' => 0, 'simpanan' => 0, 'pinjaman' => 0, 'bersih' => 0,
    ];

    /** Mengubah total per jenis transaksi menjadi saldo. */
    private static function susunSaldo(array $j): array
    {
        $pokok = ($j['simpanan_pokok'] ?? 0) - ($j['tarik_pokok'] ?? 0);
        $wajib = ($j['simpanan_wajib'] ?? 0) - ($j['tarik_wajib'] ?? 0);
        $manasuka = ($j['simpanan_manasuka'] ?? 0) - ($j['tarik_manasuka'] ?? 0);
        $pinjaman = ($j['pinjaman'] ?? 0) - ($j['angsuran'] ?? 0);
        $simpanan = $pokok + $wajib + $manasuka;

        return compact('pokok', 'wajib', 'manasuka', 'simpanan', 'pinjaman') + ['bersih' => $simpanan - $pinjaman];
    }

    /** Total per jenis transaksi untuk tiap anggota: [anggota_id => [jenis => total]]. */
    public static function mutasi(?Carbon $dari = null, ?Carbon $sampai = null, ?int $anggotaId = null): array
    {
        $baris = Transaksi::query()
            ->selectRaw('anggota_id, jenis, SUM(jumlah) as total')
            ->when($dari, fn ($q) => $q->whereDate('tanggal', '>=', $dari))
            ->when($sampai, fn ($q) => $q->whereDate('tanggal', '<=', $sampai))
            ->when($anggotaId, fn ($q) => $q->where('anggota_id', $anggotaId))
            ->groupBy('anggota_id', 'jenis')
            ->toBase()->get();

        $hasil = [];
        foreach ($baris as $b) {
            $hasil[$b->anggota_id][$b->jenis] = (int) $b->total;
        }

        return $hasil;
    }

    /** Saldo tiap anggota sampai tanggal tertentu: [anggota_id => saldo]. */
    public static function saldo(?Carbon $sampai = null): Collection
    {
        return collect(self::mutasi(null, $sampai))->map(fn ($j) => self::susunSaldo($j));
    }

    public static function saldoAnggota(int $anggotaId, ?Carbon $sampai = null): array
    {
        $mutasi = self::mutasi(null, $sampai, $anggotaId);

        return isset($mutasi[$anggotaId]) ? self::susunSaldo($mutasi[$anggotaId]) : self::SALDO_KOSONG;
    }

    /** Jumlah saldo seluruh anggota. */
    public static function ringkasan(?Carbon $sampai = null): array
    {
        $total = self::SALDO_KOSONG;
        foreach (self::saldo($sampai) as $s) {
            foreach ($total as $k => $v) {
                $total[$k] += $s[$k];
            }
        }

        return $total;
    }

    /** Perkiraan uang kas: semua uang masuk dikurangi semua uang keluar. */
    public static function kas(): int
    {
        $masuk = (int) Transaksi::whereIn('jenis', Transaksi::jenisArah('masuk'))->sum('jumlah');
        $keluar = (int) Transaksi::whereIn('jenis', Transaksi::jenisArah('keluar'))->sum('jumlah');
        $lain = (int) KasLain::where('tipe', 'pendapatan')->sum('jumlah') - (int) KasLain::where('tipe', 'biaya')->sum('jumlah');

        return $masuk - $keluar + $lain;
    }

    // ------------------------------------------------------------------ Tagihan bulanan

    /**
     * Tagihan satu anggota untuk satu bulan, dihitung dari pengaturan dan pinjaman aktifnya.
     * Relasi pinjamanAktif sebaiknya sudah dimuat.
     */
    public static function tagihan(Anggota $anggota, TahunBuku $tahun, Carbon $periode): array
    {
        $pinjaman = [];
        foreach ($anggota->pinjamanAktif as $p) {
            // Pinjaman yang cair pada bulan ini baru mulai diangsur bulan berikutnya.
            if ($p->tanggal->gte($periode) || $p->sisa <= 0) {
                continue;
            }
            $pinjaman[$p->id] = ['angsuran' => $p->tagihan_angsuran, 'jasa' => $p->tagihan_jasa, 'sisa' => $p->sisa];
        }

        $wajib = (int) $tahun->simpanan_wajib;
        $manasuka = (int) $anggota->setoran_manasuka;
        $angsuran = array_sum(array_column($pinjaman, 'angsuran'));
        $jasa = array_sum(array_column($pinjaman, 'jasa'));

        return compact('wajib', 'manasuka', 'pinjaman', 'angsuran', 'jasa') + ['total' => $wajib + $manasuka + $angsuran + $jasa];
    }

    /** Pembayaran tagihan yang sudah tercatat pada satu bulan: [anggota_id => rincian]. */
    public static function pembayaran(Carbon $periode): Collection
    {
        return Transaksi::where('sumber', 'tagihan')->whereDate('periode', $periode)->get()
            ->groupBy('anggota_id')
            ->map(function ($grup) {
                $per = $grup->groupBy('jenis')->map->sum('jumlah');

                return [
                    'wajib' => (int) ($per['simpanan_wajib'] ?? 0),
                    'manasuka' => (int) ($per['simpanan_manasuka'] ?? 0),
                    'angsuran' => (int) ($per['angsuran'] ?? 0),
                    'jasa' => (int) ($per['jasa'] ?? 0),
                    'total' => (int) $grup->sum('jumlah'),
                    'metode' => $grup->first()->metode,
                    'tanggal' => $grup->first()->tanggal,
                ];
            });
    }

    /**
     * Mencatat pembayaran tagihan satu anggota. Mengembalikan false jika bulan itu sudah dibayar.
     * $tagihan memakai bentuk yang sama dengan hasil tagihan().
     */
    public static function bayarTagihan(Anggota $anggota, Carbon $periode, array $tagihan, string $metode, ?Carbon $tanggal = null, ?int $userId = null): bool
    {
        $periode = $periode->copy()->startOfMonth();
        $sudah = Transaksi::where('anggota_id', $anggota->id)->where('sumber', 'tagihan')->whereDate('periode', $periode)->exists();
        if ($sudah) {
            return false;
        }

        $tanggal ??= $periode->isSameMonth(now()) ? today() : $periode->copy()->endOfMonth();
        $dasar = [
            'anggota_id' => $anggota->id, 'tanggal' => $tanggal->toDateString(), 'periode' => $periode->toDateString(),
            'metode' => $metode, 'sumber' => 'tagihan', 'user_id' => $userId,
        ];

        DB::transaction(function () use ($anggota, $tagihan, $dasar) {
            if (($tagihan['wajib'] ?? 0) > 0) {
                Transaksi::create($dasar + ['jenis' => 'simpanan_wajib', 'jumlah' => $tagihan['wajib']]);
            }
            if (($tagihan['manasuka'] ?? 0) > 0) {
                Transaksi::create($dasar + ['jenis' => 'simpanan_manasuka', 'jumlah' => $tagihan['manasuka']]);
            }
            foreach ($tagihan['pinjaman'] ?? [] as $pinjamanId => $bayar) {
                $pinjaman = Pinjaman::where('anggota_id', $anggota->id)->find($pinjamanId);
                if (! $pinjaman) {
                    continue;
                }
                $angsuran = min((int) ($bayar['angsuran'] ?? 0), $pinjaman->sisa);
                if ($angsuran > 0) {
                    Transaksi::create($dasar + ['jenis' => 'angsuran', 'jumlah' => $angsuran, 'pinjaman_id' => $pinjaman->id]);
                }
                if (($bayar['jasa'] ?? 0) > 0) {
                    Transaksi::create($dasar + ['jenis' => 'jasa', 'jumlah' => (int) $bayar['jasa'], 'pinjaman_id' => $pinjaman->id]);
                }
                $pinjaman->segarkanStatus();
            }
        });

        return true;
    }

    /** Membatalkan pembayaran tagihan satu anggota pada satu bulan. */
    public static function batalTagihan(int $anggotaId, Carbon $periode): int
    {
        $transaksi = Transaksi::where('anggota_id', $anggotaId)->where('sumber', 'tagihan')
            ->whereDate('periode', $periode->copy()->startOfMonth())->get();

        DB::transaction(function () use ($transaksi) {
            Transaksi::whereIn('id', $transaksi->pluck('id'))->delete();
            Pinjaman::whereIn('id', $transaksi->pluck('pinjaman_id')->filter()->unique())->get()->each->segarkanStatus();
        });

        return $transaksi->count();
    }

    // ------------------------------------------------------------------ Buku tabungan

    /** Buku tabungan satu anggota: satu baris per bulan dalam tahun buku, lengkap dengan saldo berjalan. */
    public static function bukuTabungan(Anggota $anggota, TahunBuku $tahun): array
    {
        $awal = self::saldoAnggota($anggota->id, $tahun->mulai->copy()->subDay());

        $mutasi = [];
        $baris = Transaksi::where('anggota_id', $anggota->id)
            ->whereDate('tanggal', '>=', $tahun->mulai)->whereDate('tanggal', '<=', $tahun->selesai)
            ->selectRaw('periode, jenis, SUM(jumlah) as total')->groupBy('periode', 'jenis')->toBase()->get();
        foreach ($baris as $b) {
            $mutasi[substr((string) $b->periode, 0, 7)][$b->jenis] = (int) $b->total;
        }

        $saldo = $awal;
        $hasil = [];
        $jumlah = ['wajib_masuk' => 0, 'wajib_keluar' => 0, 'manasuka_masuk' => 0, 'manasuka_keluar' => 0, 'pinjaman_baru' => 0, 'angsuran' => 0, 'jasa' => 0];
        foreach ($tahun->bulan() as $bulan) {
            if ($bulan->gt(now())) {
                break;
            }
            $m = $mutasi[$bulan->format('Y-m')] ?? [];
            $b = [
                'wajib_masuk' => $m['simpanan_wajib'] ?? 0,
                'wajib_keluar' => $m['tarik_wajib'] ?? 0,
                'manasuka_masuk' => $m['simpanan_manasuka'] ?? 0,
                'manasuka_keluar' => $m['tarik_manasuka'] ?? 0,
                'pinjaman_baru' => $m['pinjaman'] ?? 0,
                'angsuran' => $m['angsuran'] ?? 0,
                'jasa' => $m['jasa'] ?? 0,
            ];
            foreach ($jumlah as $k => $v) {
                $jumlah[$k] += $b[$k];
            }
            $saldo['pokok'] += ($m['simpanan_pokok'] ?? 0) - ($m['tarik_pokok'] ?? 0);
            $saldo['wajib'] += $b['wajib_masuk'] - $b['wajib_keluar'];
            $saldo['manasuka'] += $b['manasuka_masuk'] - $b['manasuka_keluar'];
            $saldo['pinjaman'] += $b['pinjaman_baru'] - $b['angsuran'];

            $hasil[] = $b + [
                'bulan' => $bulan, 'ada' => $m !== [],
                'wajib' => $saldo['wajib'], 'manasuka' => $saldo['manasuka'], 'pinjaman' => $saldo['pinjaman'],
            ];
        }
        $saldo['simpanan'] = $saldo['pokok'] + $saldo['wajib'] + $saldo['manasuka'];
        $saldo['bersih'] = $saldo['simpanan'] - $saldo['pinjaman'];

        return ['awal' => $awal, 'baris' => $hasil, 'jumlah' => $jumlah, 'akhir' => $saldo];
    }

    // ------------------------------------------------------------------ Uang masuk dan keluar per bulan

    /** Uang masuk dan keluar tiap bulan dalam tahun buku, untuk grafik di beranda. */
    public static function arusBulanan(TahunBuku $tahun): array
    {
        $baris = Transaksi::whereDate('tanggal', '>=', $tahun->mulai)->whereDate('tanggal', '<=', $tahun->selesai)
            ->selectRaw('periode, jenis, SUM(jumlah) as total')->groupBy('periode', 'jenis')->toBase()->get();

        $per = [];
        foreach ($baris as $b) {
            $arah = Transaksi::JENIS[$b->jenis]['arah'] ?? 'masuk';
            $kunci = substr((string) $b->periode, 0, 7);
            $per[$kunci][$arah] = ($per[$kunci][$arah] ?? 0) + (int) $b->total;
        }

        return array_map(fn ($bulan) => [
            'bulan' => $bulan,
            'masuk' => $per[$bulan->format('Y-m')]['masuk'] ?? 0,
            'keluar' => $per[$bulan->format('Y-m')]['keluar'] ?? 0,
        ], $tahun->bulan());
    }

    // ------------------------------------------------------------------ SHU

    /** Sisa Hasil Usaha tahun buku dan pembagiannya menurut persentase di pengaturan. */
    public static function shu(TahunBuku $tahun): array
    {
        $sampai = $tahun->selesai->lt(today()) ? $tahun->selesai : today();
        $mutasi = self::mutasi($tahun->mulai, $tahun->selesai);

        $jasa = array_sum(array_column($mutasi, 'jasa'));
        $provisi = array_sum(array_column($mutasi, 'provisi'));

        $kas = KasLain::whereDate('tanggal', '>=', $tahun->mulai)->whereDate('tanggal', '<=', $tahun->selesai)
            ->selectRaw('tipe, kategori, SUM(jumlah) as total')->groupBy('tipe', 'kategori')->toBase()->get();
        $pendapatanLain = $kas->where('tipe', 'pendapatan')->mapWithKeys(fn ($k) => [$k->kategori => (int) $k->total])->all();
        $biaya = $kas->where('tipe', 'biaya')->mapWithKeys(fn ($k) => [$k->kategori => (int) $k->total])->all();

        $pendapatan = ['Jasa pinjaman' => $jasa, 'Provisi' => $provisi] + $pendapatanLain;
        $totalPendapatan = array_sum($pendapatan);
        $totalBiaya = array_sum($biaya);
        $bersih = $totalPendapatan - $totalBiaya;

        $pembagian = [];
        foreach (TahunBuku::POS_SHU as $kolom => $nama) {
            $pembagian[$kolom] = ['nama' => $nama, 'persen' => $tahun->$kolom, 'jumlah' => (int) round(max(0, $bersih) * $tahun->$kolom / 100)];
        }

        // Bagian anggota: jasa simpanan sebanding saldo simpanan, jasa pinjaman sebanding jasa yang dibayar.
        $saldo = self::saldo($sampai);
        $totalSimpanan = max(0, $saldo->sum('simpanan'));
        $perAnggota = [];
        foreach (Anggota::orderBy('nomor')->get() as $a) {
            $simpanan = max(0, $saldo[$a->id]['simpanan'] ?? 0);
            $jasaDibayar = $mutasi[$a->id]['jasa'] ?? 0;
            if ($simpanan === 0 && $jasaDibayar === 0) {
                continue;
            }
            $dariSimpanan = $totalSimpanan > 0 ? (int) round($pembagian['shu_jasa_simpanan']['jumlah'] * $simpanan / $totalSimpanan) : 0;
            $dariPinjaman = $jasa > 0 ? (int) round($pembagian['shu_jasa_pinjaman']['jumlah'] * $jasaDibayar / $jasa) : 0;
            $perAnggota[$a->id] = [
                'anggota' => $a, 'simpanan' => $simpanan, 'jasa_dibayar' => $jasaDibayar,
                'dari_simpanan' => $dariSimpanan, 'dari_pinjaman' => $dariPinjaman, 'total' => $dariSimpanan + $dariPinjaman,
            ];
        }

        return compact('pendapatan', 'biaya', 'totalPendapatan', 'totalBiaya', 'bersih', 'pembagian', 'perAnggota', 'totalSimpanan') + ['totalJasa' => $jasa];
    }
}

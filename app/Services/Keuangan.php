<?php

namespace App\Services;

use App\Models\Anggota;
use App\Models\Inventaris;
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

    /** Nama pos biaya SHU untuk provisi yang dikembalikan ke anggota saat RAT. */
    public const UANG_DUDUK = 'Uang duduk + doorprize';

    /** Nama pos biaya SHU untuk paket munggahan tahun lalu yang dilunasi dari SHU. */
    public const PAKET_MUNGGAHAN = 'Paket munggahan tahun lalu';

    /** Nama pos biaya SHU untuk penyusutan dan penghapusan inventaris. */
    public const PENYUSUTAN = 'Penyusutan dan penghapusan inventaris';

    // ------------------------------------------------------------------ Tagihan bulanan

    /** Pos tagihan dan jenis transaksi yang mencatatnya. */
    public const POS_TAGIHAN = [
        'pokok' => 'simpanan_pokok', 'wajib' => 'simpanan_wajib', 'manasuka' => 'simpanan_manasuka',
        'angsuran' => 'angsuran', 'jasa' => 'jasa',
    ];

    /**
     * Tagihan satu anggota untuk satu bulan, dihitung dari pengaturan dan pinjaman aktifnya.
     * Simpanan pokok ikut ditagih selama anggota belum pernah membayarnya.
     * Relasi pinjamanAktif sebaiknya sudah dimuat.
     *
     * @return array{pokok: int, wajib: int, manasuka: int, pinjaman: array<int, array{angsuran: int, jasa: int, sisa: int}>, angsuran: int, jasa: int, total: int}
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

        $pokok = self::pokokTertagih($anggota, $tahun, $periode);
        $wajib = (int) $tahun->simpanan_wajib;
        $manasuka = (int) $anggota->setoran_manasuka;
        $angsuran = array_sum(array_column($pinjaman, 'angsuran'));
        $jasa = array_sum(array_column($pinjaman, 'jasa'));

        return compact('pokok', 'wajib', 'manasuka', 'pinjaman', 'angsuran', 'jasa')
            + ['total' => $pokok + $wajib + $manasuka + $angsuran + $jasa];
    }

    /**
     * Simpanan pokok yang masih harus dibayar pada bulan ini: penuh jika anggota sudah masuk
     * dan belum pernah membayarnya di luar tagihan bulan ini.
     */
    private static function pokokTertagih(Anggota $anggota, TahunBuku $tahun, Carbon $periode): int
    {
        if ($tahun->simpanan_pokok <= 0 || $anggota->tanggal_masuk?->gt($periode->copy()->endOfMonth())) {
            return 0;
        }

        $sudahBayar = Transaksi::where('anggota_id', $anggota->id)->where('jenis', 'simpanan_pokok')
            ->where(fn ($q) => $q->where('sumber', '!=', 'tagihan')->orWhereDate('periode', '!=', $periode))
            ->exists();

        return $sudahBayar ? 0 : (int) $tahun->simpanan_pokok;
    }

    /**
     * Pembayaran tagihan yang sudah tercatat pada satu bulan, dijumlah per pos: [anggota_id => rincian].
     * Satu bulan boleh dibayar beberapa kali (bertahap), dengan cara bayar yang berbeda-beda.
     */
    public static function pembayaran(Carbon $periode, ?int $anggotaId = null): Collection
    {
        return Transaksi::where('sumber', 'tagihan')->whereDate('periode', $periode)
            ->when($anggotaId, fn ($q) => $q->where('anggota_id', $anggotaId))
            ->orderBy('tanggal')->orderBy('id')->get()
            ->groupBy('anggota_id')
            ->map(function ($grup) {
                $hasil = ['pinjaman' => [], 'rincian' => []];
                foreach (self::POS_TAGIHAN as $pos => $jenis) {
                    $baris = $grup->where('jenis', $jenis);
                    $hasil[$pos] = (int) $baris->sum('jumlah');
                    if ($baris->isNotEmpty()) {
                        $hasil['rincian'][$pos] = [
                            'metode' => $baris->pluck('metode')->filter()->unique()->values()->all(),
                            'tanggal' => $baris->last()->tanggal,
                        ];
                    }
                }
                foreach ($grup->whereNotNull('pinjaman_id')->groupBy('pinjaman_id') as $id => $baris) {
                    $hasil['pinjaman'][$id] = [
                        'angsuran' => (int) $baris->where('jenis', 'angsuran')->sum('jumlah'),
                        'jasa' => (int) $baris->where('jenis', 'jasa')->sum('jumlah'),
                    ];
                }

                return $hasil + [
                    'total' => (int) $grup->sum('jumlah'),
                    'tanggal' => $grup->last()->tanggal,
                    'metode' => $grup->last()->metode,
                ];
            });
    }

    /**
     * Sisa tagihan setelah dikurangi pembayaran bulan itu, dengan bentuk yang sama seperti tagihan(),
     * ditambah status: lunas, sebagian, atau belum.
     */
    public static function sisaTagihan(array $tagihan, ?array $bayar): array
    {
        $sisa = ['pinjaman' => []];
        foreach (['pokok', 'wajib', 'manasuka'] as $pos) {
            $sisa[$pos] = max(0, $tagihan[$pos] - ($bayar[$pos] ?? 0));
        }
        foreach ($tagihan['pinjaman'] as $id => $p) {
            $sudah = $bayar['pinjaman'][$id] ?? ['angsuran' => 0, 'jasa' => 0];
            $sisa['pinjaman'][$id] = [
                'angsuran' => max(0, $p['angsuran'] - $sudah['angsuran']),
                'jasa' => max(0, $p['jasa'] - $sudah['jasa']),
                'sisa' => $p['sisa'],
            ];
        }
        $sisa['angsuran'] = array_sum(array_column($sisa['pinjaman'], 'angsuran'));
        $sisa['jasa'] = array_sum(array_column($sisa['pinjaman'], 'jasa'));
        $sisa['total'] = $sisa['pokok'] + $sisa['wajib'] + $sisa['manasuka'] + $sisa['angsuran'] + $sisa['jasa'];
        $sisa['status'] = $sisa['total'] === 0 ? 'lunas' : ($bayar ? 'sebagian' : 'belum');

        return $sisa;
    }

    /**
     * Tagihan, pembayaran, dan sisa setiap anggota aktif pada satu bulan.
     *
     * @return Collection<int, array{anggota: Anggota, tagihan: array, bayar: ?array, sisa: array, status: string}>
     */
    public static function daftarTagihan(TahunBuku $tahun, Carbon $periode): Collection
    {
        $bayar = self::pembayaran($periode);

        return Anggota::aktif()->with('pinjamanAktif')->orderBy('nomor')->get()
            ->map(function (Anggota $a) use ($tahun, $periode, $bayar) {
                $tagihan = self::tagihan($a, $tahun, $periode);
                $sisa = self::sisaTagihan($tagihan, $bayar[$a->id] ?? null);

                return ['anggota' => $a, 'tagihan' => $tagihan, 'bayar' => $bayar[$a->id] ?? null, 'sisa' => $sisa, 'status' => $sisa['status']];
            });
    }

    /**
     * Mencatat satu kali pembayaran tagihan (boleh sebagian). Mengembalikan jumlah transaksi yang tercatat.
     * Pos yang sudah lunas tidak bisa dibayar lagi, sehingga tidak ada setoran ganda. Hanya simpanan manasuka
     * yang boleh melebihi tagihan (setoran tambahan), dan angsuran boleh melebihi tagihan sampai pinjaman lunas.
     *
     * @param  array{pokok?: int, wajib?: int, manasuka?: int, pinjaman?: array<int, array{angsuran?: int, jasa?: int}>}  $bayar
     */
    public static function bayarTagihan(Anggota $anggota, TahunBuku $tahun, Carbon $periode, array $bayar, string $metode, ?Carbon $tanggal = null, ?int $userId = null): int
    {
        $periode = $periode->copy()->startOfMonth();
        $anggota->load('pinjamanAktif');
        $sisa = self::sisaTagihan(self::tagihan($anggota, $tahun, $periode), self::pembayaran($periode, $anggota->id)->first());

        $tanggal ??= $periode->isSameMonth(now()) ? today() : $periode->copy()->endOfMonth();
        $dasar = [
            'anggota_id' => $anggota->id, 'tanggal' => $tanggal->toDateString(), 'periode' => $periode->toDateString(),
            'metode' => $metode, 'sumber' => 'tagihan', 'user_id' => $userId,
        ];

        $catat = [];
        foreach (['pokok' => $sisa['pokok'], 'wajib' => $sisa['wajib'], 'manasuka' => PHP_INT_MAX] as $pos => $batas) {
            $jumlah = min((int) ($bayar[$pos] ?? 0), $batas);
            if ($jumlah > 0) {
                $catat[] = $dasar + ['jenis' => self::POS_TAGIHAN[$pos], 'jumlah' => $jumlah];
            }
        }

        $pinjamanDibayar = [];
        foreach ($bayar['pinjaman'] ?? [] as $pinjamanId => $isi) {
            $pinjaman = $anggota->pinjamanAktif->firstWhere('id', (int) $pinjamanId);
            if (! $pinjaman) {
                continue;
            }
            $angsuran = min((int) ($isi['angsuran'] ?? 0), $pinjaman->sisa);
            $jasa = min((int) ($isi['jasa'] ?? 0), $sisa['pinjaman'][$pinjaman->id]['jasa'] ?? 0);
            if ($angsuran > 0) {
                $catat[] = $dasar + ['jenis' => 'angsuran', 'jumlah' => $angsuran, 'pinjaman_id' => $pinjaman->id];
            }
            if ($jasa > 0) {
                $catat[] = $dasar + ['jenis' => 'jasa', 'jumlah' => $jasa, 'pinjaman_id' => $pinjaman->id];
            }
            $pinjamanDibayar[] = $pinjaman;
        }

        DB::transaction(function () use ($catat, $pinjamanDibayar) {
            foreach ($catat as $baris) {
                Transaksi::create($baris);
            }
            foreach ($pinjamanDibayar as $pinjaman) {
                $pinjaman->segarkanStatus();
            }
        });

        return count($catat);
    }

    /**
     * Membayar seluruh sisa tagihan satu anggota memakai cara bayar bawaan tiap pos.
     * Jika $hanyaMetode diisi (misalnya potong_gaji), hanya pos dengan cara bayar itu yang dibayar.
     */
    public static function bayarSisaTagihan(Anggota $anggota, TahunBuku $tahun, Carbon $periode, ?string $hanyaMetode = null, ?Carbon $tanggal = null, ?int $userId = null): int
    {
        $anggota->load('pinjamanAktif');
        $sisa = self::sisaTagihan(self::tagihan($anggota, $tahun, $periode), self::pembayaran($periode, $anggota->id)->first());

        $perMetode = [];
        foreach (['pokok', 'wajib', 'manasuka'] as $pos) {
            if ($sisa[$pos] > 0) {
                $perMetode[$anggota->caraBayar($pos)][$pos] = $sisa[$pos];
            }
        }
        foreach ($sisa['pinjaman'] as $id => $p) {
            if ($p['angsuran'] + $p['jasa'] > 0) {
                $perMetode[$anggota->caraBayar('angsuran')]['pinjaman'][$id] = ['angsuran' => $p['angsuran'], 'jasa' => $p['jasa']];
            }
        }

        $jumlah = 0;
        foreach ($perMetode as $metode => $bayar) {
            if ($hanyaMetode === null || $hanyaMetode === $metode) {
                $jumlah += self::bayarTagihan($anggota, $tahun, $periode, $bayar, $metode, $tanggal, $userId);
            }
        }

        return $jumlah;
    }

    /** Membatalkan semua pembayaran tagihan satu anggota pada satu bulan. */
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

    /**
     * Buku tabungan satu anggota: saldo awal (bulan sebelum tahun buku dimulai), lalu satu baris per bulan
     * dalam tahun buku lengkap dengan saldo berjalan. Bulan yang belum tiba tetap ditampilkan dengan tanda "mendatang".
     */
    public static function bukuTabungan(Anggota $anggota, TahunBuku $tahun): array
    {
        $awal = self::saldoAnggota($anggota->id, $tahun->mulai->copy()->subDay());
        $bulanAwal = $tahun->mulai->copy()->startOfMonth()->subMonth();

        $mutasi = [];
        $baris = Transaksi::where('anggota_id', $anggota->id)
            ->whereDate('tanggal', '>=', $tahun->mulai)->whereDate('tanggal', '<=', $tahun->selesai)
            ->selectRaw('periode, jenis, SUM(jumlah) as total')->groupBy('periode', 'jenis')->toBase()->get();
        foreach ($baris as $b) {
            $mutasi[substr((string) $b->periode, 0, 7)][$b->jenis] = (int) $b->total;
        }

        $saldo = $awal;
        $hasil = [];
        $jumlah = ['pokok_masuk' => 0, 'wajib_masuk' => 0, 'wajib_keluar' => 0, 'manasuka_masuk' => 0, 'manasuka_keluar' => 0, 'pinjaman_baru' => 0, 'angsuran' => 0, 'jasa' => 0];
        foreach ($tahun->bulan() as $bulan) {
            $m = $mutasi[$bulan->format('Y-m')] ?? [];
            $b = [
                'pokok_masuk' => ($m['simpanan_pokok'] ?? 0) - ($m['tarik_pokok'] ?? 0),
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
            $saldo['pokok'] += $b['pokok_masuk'];
            $saldo['wajib'] += $b['wajib_masuk'] - $b['wajib_keluar'];
            $saldo['manasuka'] += $b['manasuka_masuk'] - $b['manasuka_keluar'];
            $saldo['pinjaman'] += $b['pinjaman_baru'] - $b['angsuran'];

            $hasil[] = $b + [
                'bulan' => $bulan, 'ada' => $m !== [], 'mendatang' => $bulan->gt(now()),
                'pokok' => $saldo['pokok'], 'wajib' => $saldo['wajib'], 'manasuka' => $saldo['manasuka'], 'pinjaman' => $saldo['pinjaman'],
            ];
        }
        $saldo['simpanan'] = $saldo['pokok'] + $saldo['wajib'] + $saldo['manasuka'];
        $saldo['bersih'] = $saldo['simpanan'] - $saldo['pinjaman'];

        return ['bulanAwal' => $bulanAwal, 'awal' => $awal, 'baris' => $hasil, 'jumlah' => $jumlah, 'akhir' => $saldo];
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
            ->whereNotIn('kategori', KasLain::DI_LUAR_SHU)
            ->selectRaw('tipe, kategori, SUM(jumlah) as total')->groupBy('tipe', 'kategori')->toBase()->get();
        $pendapatanLain = $kas->where('tipe', 'pendapatan')->mapWithKeys(fn ($k) => [$k->kategori => (int) $k->total])->all();
        $biaya = $kas->where('tipe', 'biaya')->mapWithKeys(fn ($k) => [$k->kategori => (int) $k->total])->all();

        $pendapatan = ['Jasa pinjaman' => $jasa, 'Provisi' => $provisi] + $pendapatanLain;
        $inventaris = self::inventaris($tahun);
        $biaya += [
            // Paket munggahan yang dibeli tahun lalu (saldo awal piutangnya) dibebankan ke SHU tahun ini, tanpa uang kas.
            self::PAKET_MUNGGAHAN => (int) $tahun->awal_paket_munggahan,
            // Seperti di NERACA KGB, provisi dikembalikan ke anggota sebagai uang duduk dan doorprize RAT.
            self::UANG_DUDUK => $provisi,
            // Biaya tanpa uang kas: penyusutan, ditambah nilai buku barang yang dihapuskan.
            self::PENYUSUTAN => $inventaris['penyusutan'] + $inventaris['penghapusan'],
        ];
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

    // ------------------------------------------------------------------ Neraca

    /**
     * Neraca satu tahun buku dengan susunan yang sama seperti sheet NERACA di buku KGB: sumber harta,
     * posisi harta, laba dan biaya sampai SHU bersih, pembagian SHU, dan posisi kekayaan.
     * Setiap baris harta berisi awal, masuk, keluar, dan akhir.
     */
    public static function neraca(TahunBuku $tahun): array
    {
        $sebelum = $tahun->mulai->copy()->subDay();
        $rentang = [$tahun->mulai->toDateString(), $tahun->selesai->toDateString()];
        $awal = self::ringkasan($sebelum);

        $mutasi = [];
        foreach (self::mutasi($tahun->mulai, $tahun->selesai) as $perJenis) {
            foreach ($perJenis as $jenis => $total) {
                $mutasi[$jenis] = ($mutasi[$jenis] ?? 0) + $total;
            }
        }
        $m = fn (string $jenis): int => $mutasi[$jenis] ?? 0;

        $kas = KasLain::whereBetween('tanggal', $rentang)->selectRaw('kategori, SUM(jumlah) as total')
            ->groupBy('kategori')->pluck('total', 'kategori');
        $k = fn (string $kategori): int => (int) ($kas[$kategori] ?? 0);

        $shu = self::shu($tahun);
        $inventaris = self::inventaris($tahun);
        // Dana sosial dan cadangan bertambah dari pembagian SHU tahun lalu, yang ditetapkan saat RAT.
        $lalu = TahunBuku::whereDate('selesai', '<', $tahun->mulai)->latest('selesai')->first();
        $pembagianLalu = $lalu ? self::shu($lalu)['pembagian'] : [];
        $bagianLalu = fn (string $kolom): int => $pembagianLalu[$kolom]['jumlah'] ?? 0;

        // Simpanan dan dana: akhir = awal + masuk - keluar. Piutang: uang masuk adalah pengembalian, jadi akhir = awal - masuk + keluar.
        $tambah = fn (int $awal, int $masuk, int $keluar): array => ['awal' => $awal, 'masuk' => $masuk, 'keluar' => $keluar, 'akhir' => $awal + $masuk - $keluar];
        $piutang = fn (int $awal, int $masuk, int $keluar): array => ['awal' => $awal, 'masuk' => $masuk, 'keluar' => $keluar, 'akhir' => $awal - $masuk + $keluar];

        $sumber = [
            'Simpanan Pokok' => $tambah($awal['pokok'], $m('simpanan_pokok'), $m('tarik_pokok')),
            'Simpanan Wajib' => $tambah($awal['wajib'], $m('simpanan_wajib'), $m('tarik_wajib')),
            'Simpanan Manasuka' => $tambah($awal['manasuka'], $m('simpanan_manasuka'), $m('tarik_manasuka')),
            'Dana Sosial' => $tambah((int) $tahun->awal_dana_sosial, $bagianLalu('shu_dana_sosial'), $k('Dana sosial')),
            'Cadangan' => $tambah((int) $tahun->awal_cadangan, $bagianLalu('shu_cadangan'), 0),
        ];

        $posisi = [
            'Pinjaman Modal Anggota' => $piutang($awal['pinjaman'], $m('angsuran'), $m('pinjaman')),
            'Modal Warung KGB' => $piutang((int) $tahun->awal_modal_warung, $k('Pengembalian modal warung KGB'), $k('Modal warung KGB')),
            'Pinjaman di Luar Anggota' => $piutang((int) $tahun->awal_pinjaman_luar, $k('Pengembalian pinjaman di luar anggota'), $k('Pinjaman di luar anggota')),
            // Paket tahun lalu dianggap lunas karena dibebankan ke SHU tahun ini; keluar = paket yang dibeli tahun ini.
            'Pinjaman Paket Munggahan' => $piutang((int) $tahun->awal_paket_munggahan, (int) $tahun->awal_paket_munggahan, $k('Pinjaman paket munggahan')),
            // Inventaris dalam nilai buku: masuk = barang dibeli, keluar = penyusutan + nilai buku barang yang dihapuskan.
            'Inventaris' => $tambah($inventaris['awal'], $inventaris['masuk'], $inventaris['penyusutan'] + $inventaris['penghapusan']),
        ];

        $jumlah = fn (array $baris): array => array_map(fn ($kunci) => array_sum(array_column($baris, $kunci)), ['awal' => 'awal', 'masuk' => 'masuk', 'keluar' => 'keluar', 'akhir' => 'akhir']);
        $jumlahSumber = $jumlah($sumber);
        $jumlahPosisi = $jumlah($posisi);
        $diKas = $jumlahSumber['akhir'] - $jumlahPosisi['akhir'];

        $usaha = array_diff_key($shu['pendapatan'], ['Jasa pinjaman' => 0, 'Provisi' => 0]);
        $biayaUsaha = array_diff_key($shu['biaya'], [self::PAKET_MUNGGAHAN => 0, self::UANG_DUDUK => 0, self::PENYUSUTAN => 0]);
        $laba = [
            'Laba Pinjaman Modal/Jasa' => $shu['pendapatan']['Jasa pinjaman'],
            'Hibah Anggota/Provisi' => $shu['pendapatan']['Provisi'],
            'Laba Usaha Warung KGB' => array_sum($usaha),
        ];
        $biaya = [
            'Biaya Usaha' => array_sum($biayaUsaha),
            'Paket Munggahan Tahun Lalu' => $shu['biaya'][self::PAKET_MUNGGAHAN],
            'Uang Duduk + Doorprize' => $shu['biaya'][self::UANG_DUDUK],
            'Penyusutan dan Penghapusan Inventaris' => $shu['biaya'][self::PENYUSUTAN],
        ];

        $piutangAnggota = $posisi['Pinjaman Modal Anggota']['akhir'];
        $kekayaan = [
            'Kas' => ['jumlah' => $diKas, 'laba' => $shu['bersih']],
            'Anggota' => ['jumlah' => $piutangAnggota, 'laba' => 0],
            'Non kas' => ['jumlah' => $jumlahPosisi['akhir'] - $piutangAnggota, 'laba' => 0],
        ];

        return [
            'sumber' => $sumber, 'jumlahSumber' => $jumlahSumber,
            'posisi' => $posisi, 'jumlahPosisi' => $jumlahPosisi, 'diKas' => $diKas,
            'laba' => $laba, 'rincianUsaha' => $usaha, 'biaya' => $biaya, 'rincianBiaya' => $biayaUsaha,
            'shuBersih' => $shu['bersih'], 'pembagian' => $shu['pembagian'], 'kekayaan' => $kekayaan,
            'inventaris' => $inventaris,
        ];
    }

    /**
     * Nilai buku inventaris dalam satu tahun buku, dihitung sampai hari ini (atau akhir tahun buku jika sudah lewat):
     * awal + masuk - penyusutan - penghapusan = akhir.
     *
     * @return array{awal: int, masuk: int, penyusutan: int, penghapusan: int, akhir: int}
     */
    public static function inventaris(TahunBuku $tahun): array
    {
        $sebelum = $tahun->mulai->copy()->subDay();
        $sampai = $tahun->selesai->lt(today()) ? $tahun->selesai->copy() : today();
        $hasil = ['awal' => 0, 'masuk' => 0, 'penyusutan' => 0, 'penghapusan' => 0, 'akhir' => 0];

        $barang = Inventaris::whereDate('tanggal_perolehan', '<=', $sampai)
            ->where(fn ($q) => $q->whereNull('tanggal_dihapus')->orWhereDate('tanggal_dihapus', '>=', $tahun->mulai))
            ->get();

        foreach ($barang as $b) {
            $dimilikiAwal = $b->tanggal_perolehan->lte($sebelum);
            $dihapusTahunIni = $b->tanggal_dihapus && $b->tanggal_dihapus->lte($sampai);
            $akhirHitung = $dihapusTahunIni ? $b->tanggal_dihapus : $sampai;

            $hasil['awal'] += $dimilikiAwal ? $b->nilaiBuku($sebelum) : 0;
            $hasil['masuk'] += $dimilikiAwal ? 0 : (int) $b->nilai;
            $hasil['penyusutan'] += $b->akumulasiPenyusutan($akhirHitung) - ($dimilikiAwal ? $b->akumulasiPenyusutan($sebelum) : 0);
            $hasil['penghapusan'] += $dihapusTahunIni ? $b->nilaiBuku($b->tanggal_dihapus) : 0;
            $hasil['akhir'] += $dihapusTahunIni ? 0 : $b->nilaiBuku($sampai);
        }

        return $hasil;
    }

    // ------------------------------------------------------------------ Rekap bulanan

    /**
     * Rekap satu bulan: apa yang masuk dan keluar per anggota, dan saldonya di akhir bulan.
     *
     * @return array{baris: array<int, array<string, mixed>>, jumlah: array<string, int>}
     */
    public static function rekapBulanan(Carbon $periode): array
    {
        $akhir = $periode->copy()->endOfMonth();
        $mutasi = self::mutasi($periode, $akhir);
        $saldo = self::saldo($akhir);

        $kolom = ['wajib', 'manasuka', 'tarik', 'pinjaman_baru', 'angsuran', 'jasa', 'simpanan', 'pinjaman'];
        $jumlah = array_fill_keys($kolom, 0);

        $baris = [];
        foreach (Anggota::orderBy('nomor')->get() as $a) {
            $m = $mutasi[$a->id] ?? [];
            $s = $saldo[$a->id] ?? null;
            if ($a->status !== 'aktif' && $m === [] && (! $s || ($s['simpanan'] == 0 && $s['pinjaman'] == 0))) {
                continue;
            }
            $b = [
                'wajib' => $m['simpanan_wajib'] ?? 0,
                'manasuka' => $m['simpanan_manasuka'] ?? 0,
                'tarik' => ($m['tarik_wajib'] ?? 0) + ($m['tarik_manasuka'] ?? 0) + ($m['tarik_pokok'] ?? 0),
                'pinjaman_baru' => $m['pinjaman'] ?? 0,
                'angsuran' => $m['angsuran'] ?? 0,
                'jasa' => $m['jasa'] ?? 0,
                'simpanan' => $s['simpanan'] ?? 0,
                'pinjaman' => $s['pinjaman'] ?? 0,
            ];
            foreach ($kolom as $k) {
                $jumlah[$k] += $b[$k];
            }
            $baris[] = $b + ['anggota' => $a];
        }

        return ['baris' => $baris, 'jumlah' => $jumlah];
    }
}

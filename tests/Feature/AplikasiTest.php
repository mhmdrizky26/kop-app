<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Pinjaman;
use App\Models\TahunBuku;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\Keuangan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AplikasiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function pengurus(): User
    {
        return User::where('role', 'pengurus')->firstOrFail();
    }

    private function akunAnggota(int $nomor): User
    {
        return User::where('username', (string) $nomor)->firstOrFail();
    }

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/beranda')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Masuk');
    }

    public function test_login_pengurus_dan_anggota(): void
    {
        $this->post('/login', ['username' => 'bendahara', 'password' => 'password'])->assertRedirect('/beranda');
        $this->post('/logout')->assertRedirect('/login');

        $this->post('/login', ['username' => '3', 'password' => '123456'])->assertRedirect('/saya');
        $this->post('/logout');

        $this->post('/login', ['username' => '3', 'password' => 'salah'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_semua_halaman_pengurus_terbuka(): void
    {
        $this->actingAs($this->pengurus());
        $anggota = Anggota::first();
        $pinjaman = Pinjaman::first();

        $halaman = [
            '/beranda', '/tagihan', '/tagihan?bulan='.now()->subMonth()->format('Y-m'),
            '/transaksi', '/transaksi?jenis=angsuran&cari=a', '/transaksi/baru',
            '/pinjaman', '/pinjaman?status=semua', '/pinjaman/baru', '/pinjaman/'.$pinjaman->id,
            '/anggota', '/anggota?status=semua&cari=an', '/anggota/baru', '/anggota/'.$anggota->id, '/anggota/'.$anggota->id.'/ubah',
            '/kas', '/laporan/rekap', '/laporan/shu', '/pengaturan',
        ];

        foreach ($halaman as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_anggota_hanya_melihat_datanya_sendiri(): void
    {
        $this->actingAs($this->akunAnggota(2));
        $lain = Anggota::where('nomor', 5)->first();

        $this->get('/saya')->assertOk()->assertSee(Anggota::where('nomor', 2)->value('nama'))->assertDontSee($lain->nama);
        $this->get('/saya/pin')->assertOk();

        // Halaman pengurus dan data anggota lain tidak bisa dibuka.
        foreach (['/beranda', '/anggota', '/anggota/'.$lain->id, '/tagihan', '/laporan/shu', '/pengaturan'] as $url) {
            $this->get($url)->assertRedirect('/saya');
        }
        $this->post('/tagihan/bayar', ['anggota_id' => $lain->id, 'metode' => 'setor'])->assertRedirect('/saya');
    }

    public function test_bayar_dan_batal_tagihan(): void
    {
        $this->actingAs($this->pengurus());
        $periode = now()->startOfMonth();
        $bulan = $periode->format('Y-m');

        $anggota = Anggota::aktif()->with('pinjamanAktif')->get()
            ->first(fn ($a) => ! Keuangan::pembayaran($periode)->has($a->id) && $a->pinjamanAktif->isNotEmpty());
        $this->assertNotNull($anggota, 'Data contoh harus punya peminjam yang belum bayar bulan ini.');

        $tagihan = Keuangan::tagihan($anggota, TahunBuku::aktif(), $periode);
        $sebelum = Keuangan::saldoAnggota($anggota->id);
        $pinjamanId = array_key_first($tagihan['pinjaman']);

        $this->post('/tagihan/bayar', [
            'anggota_id' => $anggota->id, 'bulan' => $bulan, 'metode' => 'setor',
            'wajib' => $tagihan['wajib'], 'manasuka' => $tagihan['manasuka'],
            'angsuran' => [$pinjamanId => $tagihan['pinjaman'][$pinjamanId]['angsuran']],
            'jasa' => [$pinjamanId => $tagihan['pinjaman'][$pinjamanId]['jasa']],
        ])->assertSessionHas('sukses');

        $sesudah = Keuangan::saldoAnggota($anggota->id);
        $this->assertSame($sebelum['wajib'] + $tagihan['wajib'], $sesudah['wajib']);
        $this->assertSame($sebelum['pinjaman'] - $tagihan['pinjaman'][$pinjamanId]['angsuran'], $sesudah['pinjaman']);

        // Bayar dua kali pada bulan yang sama ditolak.
        $this->post('/tagihan/bayar', ['anggota_id' => $anggota->id, 'bulan' => $bulan, 'metode' => 'setor', 'wajib' => 100000])
            ->assertSessionHas('galat');

        $this->post('/tagihan/batal', ['anggota_id' => $anggota->id, 'bulan' => $bulan])->assertSessionHas('sukses');
        $this->assertSame($sebelum, Keuangan::saldoAnggota($anggota->id));
    }

    public function test_tandai_semua_potong_gaji(): void
    {
        $this->actingAs($this->pengurus());
        $periode = now()->startOfMonth();
        $potongGaji = Anggota::aktif()->where('potong_gaji', true)->count();
        $sebelum = Keuangan::pembayaran($periode)->count();

        $this->post('/tagihan/bayar-semua', ['bulan' => $periode->format('Y-m'), 'kelompok' => 'potong_gaji'])->assertSessionHas('sukses');

        $this->assertSame($sebelum + $potongGaji, Keuangan::pembayaran($periode)->count());
    }

    public function test_penarikan_tidak_boleh_melebihi_saldo(): void
    {
        $this->actingAs($this->pengurus());
        $anggota = Anggota::first();
        $saldo = Keuangan::saldoAnggota($anggota->id);
        $data = ['anggota_id' => $anggota->id, 'jenis' => 'tarik_manasuka', 'tanggal' => now()->toDateString()];

        $this->post('/transaksi', $data + ['jumlah' => $saldo['manasuka'] + 1])->assertSessionHasErrors('jumlah');
        $this->post('/transaksi', $data + ['jumlah' => 50000])->assertRedirect('/transaksi');

        $this->assertSame($saldo['manasuka'] - 50000, Keuangan::saldoAnggota($anggota->id)['manasuka']);
    }

    public function test_pinjaman_baru_memakai_tarif_pengaturan_dan_bisa_dilunasi(): void
    {
        $this->actingAs($this->pengurus());
        $anggota = Anggota::where('nomor', 24)->first();

        $this->post('/pinjaman', [
            'anggota_id' => $anggota->id, 'pokok' => 10_000_000, 'tenor' => 12, 'tanggal' => now()->toDateString(),
            'jasa_persen' => 1, 'jasa_metode' => 'menurun', 'provisi_persen' => 0.5,
        ])->assertRedirect();

        $pinjaman = Pinjaman::where('anggota_id', $anggota->id)->latest('id')->first();
        $this->assertSame(834_000, (int) $pinjaman->angsuran_pokok);
        $this->assertSame(50_000, (int) $pinjaman->provisi);
        $this->assertSame(100_000, $pinjaman->tagihan_jasa);
        $this->assertSame(50_000, (int) Transaksi::where('pinjaman_id', $pinjaman->id)->where('jenis', 'provisi')->sum('jumlah'));

        $this->post("/pinjaman/{$pinjaman->id}/bayar", ['angsuran' => 10_000_001, 'tanggal' => now()->toDateString(), 'metode' => 'setor'])
            ->assertSessionHasErrors('angsuran');
        $this->post("/pinjaman/{$pinjaman->id}/bayar", ['angsuran' => 10_000_000, 'tanggal' => now()->toDateString(), 'metode' => 'setor'])
            ->assertSessionHas('sukses');

        $this->assertSame('lunas', $pinjaman->fresh()->status);
    }

    public function test_pengaturan_menolak_pembagian_shu_yang_tidak_100_persen(): void
    {
        $this->actingAs($this->pengurus());
        $tahun = TahunBuku::aktif();
        $data = $tahun->only(['nama', 'simpanan_pokok', 'simpanan_wajib', 'jasa_persen', 'jasa_metode', 'provisi_persen', ...array_keys(TahunBuku::POS_SHU)]);

        $this->put('/pengaturan', ['shu_jasa_simpanan' => 33] + $data)->assertSessionHasErrors('shu');
        $this->put('/pengaturan', ['shu_jasa_simpanan' => 33, 'shu_jasa_pinjaman' => 40, 'simpanan_wajib' => 150000] + $data)->assertSessionHas('sukses');

        $this->assertSame(150000, (int) TahunBuku::aktif()->simpanan_wajib);
        $this->assertSame(33.0, TahunBuku::aktif()->shu_jasa_simpanan);
    }

    public function test_anggota_baru_mendapat_akun_dan_simpanan_pokok(): void
    {
        $this->actingAs($this->pengurus());

        $this->post('/anggota', [
            'nomor' => 99, 'nama' => 'Anggota Uji', 'jabatan' => 'Guru', 'tanggal_masuk' => now()->toDateString(),
            'cara_bayar' => 'setor', 'setoran_manasuka' => 50000, 'catat_pokok' => 1,
        ])->assertRedirect()->assertSessionHas('sukses');

        $anggota = Anggota::where('nomor', 99)->firstOrFail();
        $this->assertSame(5000, Keuangan::saldoAnggota($anggota->id)['pokok']);
        $this->assertTrue(User::where('username', '99')->where('anggota_id', $anggota->id)->exists());

        $this->post('/anggota', ['nomor' => 99, 'nama' => 'Kembar', 'tanggal_masuk' => now()->toDateString(), 'cara_bayar' => 'setor'])
            ->assertSessionHasErrors('nomor');
    }
}

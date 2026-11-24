<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Inventaris;
use App\Models\KasLain;
use App\Models\Pinjaman;
use App\Models\TahunBuku;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\Keuangan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AplikasiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function pengurus(): User
    {
        return User::where('role', 'pengurus')->firstOrFail();
    }

    private function admin(): User
    {
        return User::where('role', 'admin')->firstOrFail();
    }

    private function akunAnggota(int $nomor): User
    {
        return Anggota::where('nomor', $nomor)->firstOrFail()->user;
    }

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/beranda')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Nama pengguna');
        $this->get('/login/pengurus')->assertOk()->assertSee('Kata sandi');
    }

    public function test_login_pengurus_dengan_username_dan_password(): void
    {
        $this->post('/login/pengurus', ['username' => 'bendahara', 'password' => 'password'])->assertRedirect('/beranda');
        $this->post('/logout')->assertRedirect('/login');

        $this->post('/login/pengurus', ['username' => 'bendahara', 'password' => 'salah'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_login_anggota_cukup_dengan_nama_pengguna(): void
    {
        // Anggota nomor 3 bernama "Sri Wahyuni, S.Pd.", jadi nama penggunanya sri.wahyuni.
        $akun = $this->akunAnggota(3);
        $this->assertSame('sri.wahyuni', $akun->username);

        // Boleh diketik sebagai nama pengguna atau sebagai nama biasa.
        foreach (['sri.wahyuni', ' Sri Wahyuni '] as $ketikan) {
            $this->post('/login', ['nama_pengguna' => $ketikan])->assertRedirect('/saya');
            $this->assertAuthenticatedAs($akun);
            $this->post('/logout');
        }

        $this->post('/login', ['nama_pengguna' => 'tidak.ada'])->assertSessionHasErrors('nama_pengguna');
        $this->assertGuest();
    }

    public function test_akun_anggota_tidak_bisa_masuk_lewat_form_pengurus_dan_sebaliknya(): void
    {
        $akun = $this->akunAnggota(3);
        $akun->update(['password' => 'rahasia']);

        $this->post('/login/pengurus', ['username' => $akun->username, 'password' => 'rahasia'])->assertSessionHasErrors('username');
        $this->post('/login', ['nama_pengguna' => 'bendahara'])->assertSessionHasErrors('nama_pengguna');
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
            '/kas', '/laporan/rekap', '/laporan/shu', '/laporan/neraca',
            '/inventaris', '/inventaris?tampil=semua', '/inventaris/baru', '/inventaris/'.Inventaris::first()->id.'/ubah',
        ];

        foreach ($halaman as $url) {
            $this->get($url)->assertOk();
        }

        // Pengaturan dan akun pengurus khusus admin.
        $this->get('/pengaturan')->assertRedirect('/beranda');
        $this->get('/akun')->assertRedirect('/beranda');
    }

    public function test_admin_hanya_mengatur_akun_dan_aplikasi(): void
    {
        $this->post('/login/pengurus', ['username' => 'admin', 'password' => 'password'])->assertRedirect('/pengaturan');
        $this->get('/pengaturan')->assertOk()->assertSee('Identitas koperasi');
        $this->get('/akun')->assertOk();

        // Data dan laporan tidak bisa dibuka admin.
        foreach (['/beranda', '/tagihan', '/anggota', '/laporan/neraca'] as $url) {
            $this->get($url)->assertRedirect('/pengaturan');
        }
        $this->post('/kas', ['tipe' => 'biaya', 'kategori' => 'Konsumsi rapat', 'tanggal' => now()->toDateString(), 'jumlah' => 1000])
            ->assertRedirect('/pengaturan');

        // Identitas yang dulu di .env sekarang diatur dari aplikasi dan langsung dipakai di tampilan dan laporan.
        $this->put('/pengaturan/identitas', [
            'nama_aplikasi' => 'Aplikasi Uji', 'nama_koperasi' => 'Koperasi Uji Sejahtera', 'alamat' => 'Jalan Uji 1', 'kota' => 'Kota Uji',
        ])->assertSessionHas('sukses');
        $this->get('/pengaturan')->assertSee('Aplikasi Uji');

        $this->post('/logout');
        $this->actingAs($this->pengurus());
        $this->get('/laporan/neraca?format=cetak')->assertSee('Koperasi Uji Sejahtera')->assertSee('Jalan Uji 1')->assertSee('Kota Uji');
    }

    public function test_anggota_hanya_melihat_datanya_sendiri(): void
    {
        $this->actingAs($this->akunAnggota(2));
        $lain = Anggota::where('nomor', 5)->first();

        $this->get('/saya')->assertOk()->assertSee(Anggota::where('nomor', 2)->value('nama'))->assertDontSee($lain->nama);

        // Halaman pengurus dan data anggota lain tidak bisa dibuka.
        foreach (['/beranda', '/anggota', '/anggota/'.$lain->id, '/tagihan', '/laporan/shu', '/pengaturan'] as $url) {
            $this->get($url)->assertRedirect('/saya');
        }
        $this->post('/tagihan/bayar', ['anggota_id' => $lain->id, 'metode' => 'setor'])->assertRedirect('/saya');
    }

    public function test_akun_lihat_saja_bisa_membuka_tetapi_tidak_bisa_mengubah(): void
    {
        $this->post('/login/pengurus', ['username' => 'ketua', 'password' => 'password'])->assertRedirect('/beranda');
        $anggota = Anggota::first();
        $barang = Inventaris::first();

        foreach (['/beranda', '/tagihan', '/anggota/'.$anggota->id, '/inventaris', '/kas', '/laporan/neraca'] as $url) {
            $this->get($url)->assertOk()->assertDontSee(route('pengaturan.edit'));
        }
        $this->get('/laporan/shu?format=pdf')->assertOk();
        $this->get('/tagihan')->assertDontSee('Tandai semua potong gaji')->assertDontSee('Bayar semua');

        $this->get('/anggota/baru')->assertForbidden();
        $this->get('/pengaturan')->assertRedirect('/beranda');
        $this->get('/akun')->assertRedirect('/beranda');
        $this->post('/tagihan/bayar', ['anggota_id' => $anggota->id, 'otomatis' => 1])->assertForbidden();
        $this->post('/kas', ['tipe' => 'biaya', 'kategori' => 'Konsumsi rapat', 'tanggal' => now()->toDateString(), 'jumlah' => 1000])->assertForbidden();
        $this->delete('/inventaris/'.$barang->id)->assertForbidden();
        $this->assertModelExists($barang);
    }

    public function test_admin_mengelola_akun_pengurus(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->post('/akun', ['name' => 'Pemeriksa Baru', 'username' => 'pemeriksa3', 'password' => 'rahasia123', 'role' => 'pengawas'])
            ->assertSessionHas('sukses');
        $baru = User::where('username', 'pemeriksa3')->firstOrFail();
        $this->assertFalse($baru->bisaMengubah());

        // Admin terakhir tidak bisa diturunkan atau dihapus, dan akun sendiri tidak bisa dihapus.
        $this->put('/akun/'.$admin->id, ['role' => 'pengurus'])->assertSessionHasErrors('akun');
        $this->delete('/akun/'.$admin->id)->assertSessionHasErrors('akun');
        $this->assertTrue($admin->fresh()->isAdmin());

        $this->put('/akun/'.$baru->id, ['role' => 'pengurus', 'password' => 'katasandi99'])->assertSessionHas('sukses');
        $this->assertTrue($baru->fresh()->bisaMengubah());
        $this->delete('/akun/'.$baru->id)->assertSessionHas('sukses');
        $this->assertModelMissing($baru);

        // Akun anggota tidak bisa diubah lewat halaman ini.
        $this->put('/akun/'.$this->akunAnggota(1)->id, ['role' => 'pengurus'])->assertNotFound();
    }

    public function test_pengurus_mengubah_profil_sendiri(): void
    {
        $ketua = User::where('username', 'ketua')->firstOrFail();
        $this->actingAs($ketua);
        $this->get('/beranda')->assertOk()->assertSee('Profil Saya')->assertSee('Peran: Lihat saja');

        // Nama pengguna yang sudah dipakai ditolak; kata sandi baru butuh kata sandi lama yang benar.
        $this->put('/profil', ['name' => 'Ketua', 'username' => 'bendahara'])->assertSessionHasErrorsIn('profil', 'username');
        $this->put('/profil', ['name' => 'Ketua', 'username' => 'ketua', 'password' => 'sandibaru123', 'password_confirmation' => 'sandibaru123'])
            ->assertSessionHasErrorsIn('profil', 'password_lama');
        $this->put('/profil', ['name' => 'Ketua', 'username' => 'ketua', 'password_lama' => 'salah', 'password' => 'sandibaru123', 'password_confirmation' => 'sandibaru123'])
            ->assertSessionHasErrorsIn('profil', 'password_lama');

        $this->put('/profil', [
            'name' => 'Ketua Koperasi', 'username' => 'ketua.kgb', 'password_lama' => 'password',
            'password' => 'sandibaru123', 'password_confirmation' => 'sandibaru123',
        ])->assertSessionHas('sukses');
        $this->assertSame('Ketua Koperasi', $ketua->fresh()->name);
        $this->assertSame('pengawas', $ketua->fresh()->role);

        $this->post('/logout');
        $this->post('/login/pengurus', ['username' => 'ketua.kgb', 'password' => 'sandibaru123'])->assertRedirect('/beranda');

        // Anggota tidak bisa mengubah profil pengurus.
        $this->post('/logout');
        $this->actingAs($this->akunAnggota(1))->put('/profil', ['name' => 'X', 'username' => 'x'])->assertRedirect('/saya');
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

    public function test_tagihan_bisa_dibayar_bertahap_tanpa_setoran_ganda(): void
    {
        $this->actingAs($this->pengurus());
        $tahun = TahunBuku::aktif();
        $periode = now()->startOfMonth();
        $bulan = $periode->format('Y-m');
        $anggota = Keuangan::daftarTagihan($tahun, $periode)->firstWhere('status', 'belum')['anggota'];
        $sebelum = Keuangan::saldoAnggota($anggota->id);

        // Tahap 1: hanya simpanan wajib, lewat potong gaji.
        $this->post('/tagihan/bayar', ['anggota_id' => $anggota->id, 'bulan' => $bulan, 'metode' => 'potong_gaji', 'wajib' => 100000])
            ->assertSessionHas('sukses');
        $this->assertSame('sebagian', Keuangan::daftarTagihan($tahun, $periode)->firstWhere('anggota.id', $anggota->id)['status']);

        // Wajib yang sudah lunas tidak bisa tercatat lagi.
        $this->post('/tagihan/bayar', ['anggota_id' => $anggota->id, 'bulan' => $bulan, 'metode' => 'setor', 'wajib' => 100000])
            ->assertSessionHas('galat');
        $this->assertSame($sebelum['wajib'] + 100000, Keuangan::saldoAnggota($anggota->id)['wajib']);

        // Tahap 2: sisanya dibayar sekaligus.
        $this->post('/tagihan/bayar', ['anggota_id' => $anggota->id, 'bulan' => $bulan, 'otomatis' => 1])->assertSessionHas('sukses');
        $baris = Keuangan::daftarTagihan($tahun, $periode)->firstWhere('anggota.id', $anggota->id);
        $this->assertSame('lunas', $baris['status']);
        $this->assertSame($baris['tagihan']['total'], $baris['bayar']['total']);
    }

    public function test_tandai_potong_gaji_hanya_membayar_bagian_potong_gaji(): void
    {
        $this->actingAs($this->pengurus());
        $tahun = TahunBuku::aktif();
        $periode = now()->startOfMonth();
        $anggota = Anggota::where('nomor', 1)->firstOrFail();
        $anggota->update(['bayar_wajib' => 'potong_gaji', 'bayar_manasuka' => 'setor', 'bayar_pinjaman' => 'potong_gaji', 'setoran_manasuka' => 150000]);
        Keuangan::batalTagihan($anggota->id, $periode);

        $this->post('/tagihan/bayar-semua', ['bulan' => $periode->format('Y-m'), 'kelompok' => 'potong_gaji'])->assertSessionHas('sukses');

        $baris = Keuangan::daftarTagihan($tahun, $periode)->firstWhere('anggota.id', $anggota->id);
        $this->assertSame('sebagian', $baris['status']);
        $this->assertSame($baris['tagihan']['wajib'], $baris['bayar']['wajib']);
        $this->assertSame(['potong_gaji'], $baris['bayar']['rincian']['wajib']['metode']);
        $this->assertSame(0, $baris['bayar']['manasuka']);
        $this->assertSame(150000, $baris['sisa']['manasuka']);

        // Manasuka yang disetor langsung melunasi tagihan.
        Keuangan::bayarSisaTagihan($anggota, $tahun, $periode, 'setor');
        $this->assertSame('lunas', Keuangan::daftarTagihan($tahun, $periode)->firstWhere('anggota.id', $anggota->id)['status']);
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

        // Setoran tidak bisa lagi dicatat dari form ini; semua setoran lewat tagihan.
        $this->post('/transaksi', ['jenis' => 'simpanan_manasuka', 'jumlah' => 50000] + $data)->assertSessionHasErrors('jenis');
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
        $this->actingAs($this->admin());
        $tahun = TahunBuku::aktif();
        $data = $tahun->only(['nama', 'simpanan_pokok', 'simpanan_wajib', 'jasa_persen', 'jasa_metode', 'provisi_persen', ...array_keys(TahunBuku::POS_SHU)]);

        $this->put('/pengaturan', ['shu_jasa_simpanan' => 33] + $data)->assertSessionHasErrors('shu');
        $this->put('/pengaturan', ['shu_jasa_simpanan' => 33, 'shu_jasa_pinjaman' => 40, 'simpanan_wajib' => 150000] + $data)->assertSessionHas('sukses');

        $this->assertSame(150000, (int) TahunBuku::aktif()->simpanan_wajib);
        $this->assertSame(33.0, TahunBuku::aktif()->shu_jasa_simpanan);
    }

    public function test_buku_tabungan_berisi_saldo_awal_dan_semua_bulan_tahun_buku(): void
    {
        $tahun = TahunBuku::aktif();
        $anggota = Anggota::first();

        $buku = Keuangan::bukuTabungan($anggota, $tahun);

        // Saldo awal memakai bulan sebelum tahun buku dimulai, lalu 12 bulan penuh walau sebagian belum tiba.
        $this->assertTrue($buku['bulanAwal']->equalTo($tahun->mulai->copy()->startOfMonth()->subMonth()));
        $this->assertCount(12, $buku['baris']);
        $this->assertTrue(end($buku['baris'])['bulan']->isSameMonth($tahun->selesai));
        foreach ($buku['baris'] as $baris) {
            $this->assertSame($baris['bulan']->gt(now()), $baris['mendatang']);
        }

        // Kolom simpanan pokok ikut dicatat dan saldo akhirnya sama dengan saldo anggota.
        $this->assertSame(Keuangan::saldoAnggota($anggota->id)['pokok'], $buku['akhir']['pokok']);
        $this->assertSame($buku['akhir']['pokok'], end($buku['baris'])['pokok']);
    }

    public function test_anggota_baru_mendapat_id_otomatis_dan_simpanan_pokok(): void
    {
        $this->actingAs($this->pengurus());

        // Nomor yang dikirim dari luar diabaikan; ID selalu dibuat otomatis berurutan.
        $this->post('/anggota', [
            'nomor' => 3, 'nama' => 'Anggota Uji', 'jabatan' => 'Guru', 'tanggal_masuk' => now()->toDateString(),
            'bayar_wajib' => 'setor', 'bayar_manasuka' => 'setor', 'bayar_pinjaman' => 'setor', 'setoran_manasuka' => 50000, 'catat_pokok' => 1,
        ])->assertRedirect()->assertSessionHas('sukses');

        $anggota = Anggota::where('nama', 'Anggota Uji')->firstOrFail();
        $this->assertSame('KGB025', $anggota->kode);
        $this->assertSame('anggota.uji', $anggota->user->username);
        $this->assertSame(5000, Keuangan::saldoAnggota($anggota->id)['pokok']);

        // Nama kembar mendapat angka di belakang; gelar setelah koma tidak ikut.
        $this->post('/anggota', [
            'nama' => 'Anggota Uji, S.Pd.', 'tanggal_masuk' => now()->toDateString(),
            'bayar_wajib' => 'setor', 'bayar_manasuka' => 'setor', 'bayar_pinjaman' => 'setor',
        ])->assertSessionHas('sukses');
        $kembar = Anggota::where('nama', 'Anggota Uji, S.Pd.')->firstOrFail();
        $this->assertSame('anggota.uji2', $kembar->user->username);

        // Nama diubah: nama pengguna ikut berubah.
        $this->put('/anggota/'.$kembar->id, [
            'nama' => 'Muhamad Rizky', 'tanggal_masuk' => now()->toDateString(), 'status' => 'aktif',
            'bayar_wajib' => 'setor', 'bayar_manasuka' => 'setor', 'bayar_pinjaman' => 'setor',
        ])->assertSessionHas('sukses');
        $this->assertSame('muhamad.rizky', $kembar->user->fresh()->username);

        $this->post('/logout');
        $this->post('/login', ['nama_pengguna' => 'anggota.uji'])->assertRedirect('/saya');
        $this->assertAuthenticatedAs($anggota->user);
    }

    public function test_kas_di_luar_shu_tidak_mengubah_shu(): void
    {
        $this->actingAs($this->pengurus());
        $tahun = TahunBuku::aktif();
        $sebelum = Keuangan::shu($tahun)['bersih'];
        $data = ['tanggal' => now()->toDateString(), 'jumlah' => 5_000_000];

        $this->post('/kas', ['tipe' => 'biaya', 'kategori' => 'Modal warung KGB'] + $data)->assertSessionHas('sukses');
        $this->assertSame($sebelum, Keuangan::shu($tahun)['bersih']);

        $this->post('/kas', ['tipe' => 'biaya', 'kategori' => 'Konsumsi rapat'] + $data)->assertSessionHas('sukses');
        $this->assertSame($sebelum - 5_000_000, Keuangan::shu($tahun)['bersih']);

        // Kategori harus sesuai jenisnya.
        $this->post('/kas', ['tipe' => 'pendapatan', 'kategori' => 'Konsumsi rapat'] + $data)->assertSessionHasErrors('kategori');
    }

    public function test_neraca_cocok_dengan_shu_dan_saldo_anggota(): void
    {
        $tahun = TahunBuku::aktif();
        $neraca = Keuangan::neraca($tahun);
        $posisi = Keuangan::ringkasan();

        $this->assertSame(Keuangan::shu($tahun)['bersih'], $neraca['shuBersih']);
        $this->assertSame($neraca['shuBersih'], array_sum($neraca['laba']) - array_sum($neraca['biaya']));
        $this->assertSame($posisi['wajib'], $neraca['sumber']['Simpanan Wajib']['akhir']);
        $this->assertSame($posisi['manasuka'], $neraca['sumber']['Simpanan Manasuka']['akhir']);
        $this->assertSame($posisi['pinjaman'], $neraca['posisi']['Pinjaman Modal Anggota']['akhir']);
        $this->assertSame($neraca['jumlahSumber']['akhir'] - $neraca['jumlahPosisi']['akhir'], $neraca['diKas']);
    }

    public function test_inventaris_masuk_ke_neraca_dan_kas(): void
    {
        $this->actingAs($this->pengurus());
        $tahun = TahunBuku::aktif();
        $sebelum = Keuangan::neraca($tahun);

        $this->post('/inventaris', [
            'nama' => 'Kamera uji', 'jumlah' => 1, 'satuan' => 'unit', 'tanggal_perolehan' => now()->toDateString(),
            'nilai' => 4_000_000, 'umur_tahun' => 4, 'kondisi' => 'baik', 'catat_kas' => 1,
        ])->assertRedirect('/inventaris');

        $sesudah = Keuangan::neraca($tahun);
        $this->assertSame($sebelum['posisi']['Inventaris']['masuk'] + 4_000_000, $sesudah['posisi']['Inventaris']['masuk']);
        $this->assertTrue(KasLain::where('kategori', 'Pembelian inventaris')->where('jumlah', 4_000_000)->exists());
        $this->assertSame($sebelum['shuBersih'], $sesudah['shuBersih']);

        // Barang yang dihapuskan keluar dari kekayaan.
        $barang = Inventaris::where('nama', 'Kamera uji')->firstOrFail();
        $this->put('/inventaris/'.$barang->id, ['tanggal_dihapus' => now()->toDateString(), 'kondisi' => 'rusak_berat']
            + $barang->only(['nama', 'jumlah', 'satuan', 'nilai', 'umur_tahun']) + ['tanggal_perolehan' => $barang->tanggal_perolehan->toDateString()])
            ->assertRedirect('/inventaris');
        $this->assertSame($sebelum['posisi']['Inventaris']['akhir'], Keuangan::neraca($tahun)['posisi']['Inventaris']['akhir']);
    }

    public function test_penyusutan_inventaris_garis_lurus_per_bulan(): void
    {
        $barang = new Inventaris(['nilai' => 4_800_000, 'umur_tahun' => 4, 'tanggal_perolehan' => '2026-01-15']);

        // Bulan perolehan tidak disusutkan; setelah itu 4.800.000 / 48 bulan = 100.000 per bulan, berhenti saat nilainya habis.
        $this->assertSame(0, $barang->akumulasiPenyusutan(Carbon::parse('2026-01-31')));
        $this->assertSame(100_000, $barang->akumulasiPenyusutan(Carbon::parse('2026-02-28')));
        $this->assertSame(4_500_000, $barang->nilaiBuku(Carbon::parse('2026-04-30')));
        $this->assertSame(4_800_000, $barang->akumulasiPenyusutan(Carbon::parse('2035-01-01')));

        // Barang yang dihapuskan berhenti disusutkan pada tanggal penghapusan; barang tanpa umur tidak disusutkan.
        $barang->tanggal_dihapus = Carbon::parse('2026-06-10');
        $this->assertSame(500_000, $barang->akumulasiPenyusutan(Carbon::parse('2027-01-01')));
        $this->assertSame(0, (new Inventaris(['nilai' => 1_000_000, 'umur_tahun' => 0, 'tanggal_perolehan' => '2020-01-01']))->akumulasiPenyusutan(today()));
    }

    public function test_neraca_inventaris_memakai_nilai_buku_dan_penyusutan_masuk_shu(): void
    {
        $tahun = TahunBuku::aktif();
        $sampai = $tahun->selesai->lt(today()) ? $tahun->selesai : today();
        $inventaris = Keuangan::inventaris($tahun);
        $neraca = Keuangan::neraca($tahun);
        $shu = Keuangan::shu($tahun);

        // Data contoh berisi barang yang disusutkan dan satu barang yang dihapuskan tahun ini.
        $this->assertGreaterThan(0, $inventaris['penyusutan']);
        $this->assertGreaterThan(0, $inventaris['penghapusan']);
        $this->assertSame($inventaris['awal'] + $inventaris['masuk'] - $inventaris['penyusutan'] - $inventaris['penghapusan'], $inventaris['akhir']);
        $this->assertSame(
            Inventaris::whereNull('tanggal_dihapus')->get()->sum(fn ($b) => $b->nilaiBuku($sampai)),
            $neraca['posisi']['Inventaris']['akhir'],
        );
        $this->assertSame($inventaris['penyusutan'] + $inventaris['penghapusan'], $shu['biaya'][Keuangan::PENYUSUTAN]);
    }

    public function test_paket_munggahan_tahun_lalu_dibebankan_ke_shu_tanpa_kas(): void
    {
        $tahun = TahunBuku::aktif();
        $neraca = Keuangan::neraca($tahun);

        // Paket tahun lalu (saldo awal) jadi biaya SHU dan piutangnya lunas; paket tahun ini menjadi saldo akhir.
        $this->assertSame((int) $tahun->awal_paket_munggahan, Keuangan::shu($tahun)['biaya'][Keuangan::PAKET_MUNGGAHAN]);
        $this->assertSame((int) $tahun->awal_paket_munggahan, $neraca['posisi']['Pinjaman Paket Munggahan']['masuk']);
        $this->assertSame(
            (int) KasLain::where('kategori', 'Pinjaman paket munggahan')->sum('jumlah'),
            $neraca['posisi']['Pinjaman Paket Munggahan']['akhir'],
        );

        // Pelunasan dan paket lebaran tidak dicatat lagi lewat kas.
        $this->assertNotContains('Paket lebaran', KasLain::KATEGORI['biaya']);
        $this->assertNotContains('Pengembalian pinjaman paket munggahan', KasLain::KATEGORI['pendapatan']);
    }

    public function test_tahun_buku_baru_melanjutkan_saldo_awal_neraca(): void
    {
        $this->actingAs($this->admin());
        $lama = TahunBuku::aktif();
        $akhir = Keuangan::neraca($lama);

        $this->post('/pengaturan/tahun-baru')->assertSessionHas('sukses');

        $baru = TahunBuku::aktif();
        $this->assertNotSame($lama->id, $baru->id);
        $this->assertSame($akhir['posisi']['Modal Warung KGB']['akhir'], (int) $baru->awal_modal_warung);
        $this->assertSame($akhir['sumber']['Dana Sosial']['akhir'], (int) $baru->awal_dana_sosial);
    }

    public function test_laporan_bisa_dicetak_dan_diekspor_ke_pdf_dan_excel(): void
    {
        $this->actingAs($this->pengurus());
        $tahun = TahunBuku::aktif();
        $tahun->update(['nama_ketua' => 'Ketua Uji', 'nama_bendahara' => 'Bendahara Uji']);

        foreach (['/laporan/rekap' => 'Rekapitulasi Bulanan', '/laporan/shu' => 'Perhitungan Sisa Hasil Usaha', '/laporan/neraca' => 'Neraca'] as $url => $judul) {
            $this->get($url.'?format=cetak')->assertOk()->assertSee($judul)->assertSee('Ketua Uji')->assertSee('Badan Pemeriksa');

            $pdf = $this->get($url.'?format=pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $pdf->getContent());

            $excel = $this->get($url.'?format=xlsx')->assertOk()->assertDownload();
            $berkas = tempnam(sys_get_temp_dir(), 'lap');
            file_put_contents($berkas, $excel->streamedContent());
            $sheet = IOFactory::load($berkas)->getActiveSheet();
            unlink($berkas);
            $this->assertStringContainsStringIgnoringCase($judul, (string) $sheet->getCell('A3')->getValue().' '.(string) $sheet->getCell('A4')->getValue());
        }

        // Format yang tidak dikenal tetap menampilkan halaman biasa.
        $this->get('/laporan/neraca?format=docx')->assertOk()->assertSee('Keadaan Harta Kekayaan');
    }

    public function test_simpanan_pokok_ditagih_sekali_jika_belum_dibayar(): void
    {
        $this->actingAs($this->pengurus());
        $tahun = TahunBuku::aktif();
        $periode = now()->startOfMonth();

        $this->post('/anggota', [
            'nama' => 'Tanpa Pokok', 'tanggal_masuk' => now()->toDateString(), 'setoran_manasuka' => 0,
            'bayar_wajib' => 'potong_gaji', 'bayar_manasuka' => 'setor', 'bayar_pinjaman' => 'potong_gaji',
        ])->assertRedirect();
        $anggota = Anggota::where('nama', 'Tanpa Pokok')->firstOrFail();

        $this->assertSame(5000, Keuangan::tagihan($anggota, $tahun, $periode)['pokok']);

        // Pokok selalu setor langsung, jadi tidak ikut "Tandai semua potong gaji".
        Keuangan::bayarSisaTagihan($anggota, $tahun, $periode, 'potong_gaji');
        $this->assertSame(0, Keuangan::saldoAnggota($anggota->id)['pokok']);

        Keuangan::bayarSisaTagihan($anggota, $tahun, $periode, 'setor');
        $this->assertSame(5000, Keuangan::saldoAnggota($anggota->id)['pokok']);
        $this->assertSame(0, Keuangan::tagihan($anggota->fresh(), $tahun, $periode->copy()->addMonth())['pokok']);
    }
}

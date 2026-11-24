@extends('layouts.app')

@section('judul', 'Pengaturan')
@section('keterangan', 'Semua aturan hitung ada di sini. Ubah angkanya, hitungan di seluruh aplikasi ikut menyesuaikan.')

@section('isi')
    @php
        $pos = \App\Models\TahunBuku::POS_SHU;
        $awalShu = collect($pos)->map(fn ($nama, $kolom) => (float) old($kolom, $tahun->$kolom));
    @endphp

    <div class="grid items-start gap-6 xl:grid-cols-3">
    <form method="POST" action="{{ route('pengaturan.update') }}" class="space-y-6 xl:col-span-2">
        @csrf @method('PUT')

        <div class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Simpanan</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Dipakai untuk tagihan bulanan dan anggota baru</p>
                </div>
            </div>
            <div class="grid gap-5 p-5 sm:grid-cols-3">
                <div>
                    <label for="nama" class="label">Nama tahun buku</label>
                    <input id="nama" name="nama" value="{{ old('nama', $tahun->nama) }}" class="input" required>
                    <p class="hint">{{ $tahun->mulai->translatedFormat('j M Y') }} sampai {{ $tahun->selesai->translatedFormat('j M Y') }}</p>
                </div>
                <div>
                    <label class="label">Simpanan wajib per bulan</label>
                    <x-rupiah name="simpanan_wajib" :value="$tahun->simpanan_wajib" required />
                    @error('simpanan_wajib') <p class="galat">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Simpanan pokok</label>
                    <x-rupiah name="simpanan_pokok" :value="$tahun->simpanan_pokok" required />
                    <p class="hint">Sekali, saat menjadi anggota.</p>
                    @error('simpanan_pokok') <p class="galat">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="card" x-data="{ metode: @js(old('jasa_metode', $tahun->jasa_metode)), persen: @js((float) old('jasa_persen', $tahun->jasa_persen)) }">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Pinjaman</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Berlaku untuk pinjaman baru. Pinjaman yang sudah berjalan tetap memakai tarif lamanya.</p>
                </div>
            </div>
            <div class="space-y-5 p-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="jasa_persen" class="label">Jasa pinjaman per bulan</label>
                        <div class="relative">
                            <input id="jasa_persen" type="number" step="0.01" min="0" max="100" name="jasa_persen" x-model.number="persen" class="input pr-8" required>
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">%</span>
                        </div>
                        @error('jasa_persen') <p class="galat">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="provisi_persen" class="label">Provisi pinjaman baru</label>
                        <div class="relative">
                            <input id="provisi_persen" type="number" step="0.01" min="0" max="100" name="provisi_persen" value="{{ old('provisi_persen', $tahun->provisi_persen) }}" class="input pr-8" required>
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">%</span>
                        </div>
                        <p class="hint">Dipotong sekali dari pinjaman saat dicairkan.</p>
                        @error('provisi_persen') <p class="galat">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <span class="label">Cara menghitung jasa</span>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="cursor-pointer rounded-xl border border-slate-200 p-4 has-checked:border-blue-500 has-checked:bg-blue-50/60">
                            <span class="flex items-center gap-2.5 text-sm font-medium text-slate-900"><input type="radio" name="jasa_metode" value="tetap" x-model="metode" class="size-4 accent-blue-600"> Tetap</span>
                            <span class="mt-1.5 block text-xs leading-relaxed text-slate-500">Dihitung dari jumlah pinjaman awal, sehingga sama setiap bulan.</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border border-slate-200 p-4 has-checked:border-blue-500 has-checked:bg-blue-50/60">
                            <span class="flex items-center gap-2.5 text-sm font-medium text-slate-900"><input type="radio" name="jasa_metode" value="menurun" x-model="metode" class="size-4 accent-blue-600"> Menurun</span>
                            <span class="mt-1.5 block text-xs leading-relaxed text-slate-500">Dihitung dari sisa pinjaman, sehingga makin kecil setiap bulan.</span>
                        </label>
                    </div>
                </div>
                <p class="rounded-lg bg-slate-50 px-3 py-2.5 text-xs leading-relaxed text-slate-600">
                    Contoh pinjaman Rp 10.000.000 selama 10 bulan: jasa bulan pertama <span class="font-semibold text-slate-900" x-text="rupiah(10000000 * persen / 100)"></span>,
                    bulan kelima <span class="font-semibold text-slate-900" x-text="rupiah((metode === 'menurun' ? 6000000 : 10000000) * persen / 100)"></span>.
                </p>
            </div>
        </div>

        <div class="card" x-data="{ pos: @js($awalShu), get total() { return Math.round(Object.values(this.pos).reduce((a, b) => a + (Number(b) || 0), 0) * 100) / 100 } }">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Pembagian SHU</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Jumlah keenam pos harus tepat 100%</p>
                </div>
                <span class="badge" :class="total === 100 ? 'badge-green' : 'badge-rose'">Total <span x-text="total.toLocaleString('id-ID')"></span>%</span>
            </div>
            <div class="grid gap-x-5 gap-y-4 p-5 sm:grid-cols-2">
                @foreach ($pos as $kolom => $nama)
                    <div>
                        <label for="{{ $kolom }}" class="label">{{ $nama }}</label>
                        <div class="relative">
                            <input id="{{ $kolom }}" type="number" step="0.01" min="0" max="100" name="{{ $kolom }}" x-model.number="pos.{{ $kolom }}" class="input pr-8" required>
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">%</span>
                        </div>
                    </div>
                @endforeach
            </div>
            @error('shu') <p class="galat px-5 pb-4">{{ $message }}</p> @enderror
            <p class="px-5 pb-5 text-xs text-rose-600" x-show="total !== 100" x-cloak>Total sekarang <span x-text="total.toLocaleString('id-ID')"></span>%. Sesuaikan sampai tepat 100% agar bisa disimpan.</p>
        </div>

        <div class="card scroll-mt-24" id="saldo-awal">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Saldo awal neraca</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Saldo pada awal tahun buku untuk pos yang tidak berasal dari transaksi anggota. Saat tahun buku baru dibuka, terisi otomatis dari saldo akhir tahun sebelumnya.</p>
                </div>
            </div>
            <div class="grid gap-x-5 gap-y-4 p-5 sm:grid-cols-2">
                @foreach (\App\Models\TahunBuku::SALDO_AWAL_NERACA as $kolom => $nama)
                    <div>
                        <label class="label">{{ $nama }}</label>
                        <x-rupiah :name="$kolom" :value="$tahun->$kolom" />
                        @error($kolom) <p class="galat">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Pengesah laporan</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Nama yang tercantum di kolom tanda tangan laporan cetak, PDF, dan Excel.</p>
                </div>
            </div>
            <div class="grid gap-x-5 gap-y-4 p-5 sm:grid-cols-2">
                @foreach (\App\Models\TahunBuku::PENGESAH as $kolom => $jabatan)
                    <div>
                        <label for="{{ $kolom }}" class="label">{{ $jabatan }}</label>
                        <input id="{{ $kolom }}" name="{{ $kolom }}" value="{{ old($kolom, $tahun->$kolom) }}" class="input" maxlength="100">
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end">
            <button class="btn btn-primary max-sm:w-full">Simpan pengaturan</button>
        </div>
    </form>

    <div class="space-y-6">
    {{-- Identitas koperasi: dulu diisi di file .env --}}
    <form method="POST" action="{{ route('pengaturan.identitas') }}" class="card">
        @csrf @method('PUT')
        <div class="card-head">
            <div>
                <h2 class="card-title">Identitas koperasi</h2>
                <p class="mt-0.5 text-xs text-slate-500">Tampil di aplikasi dan di kop laporan cetak, PDF, dan Excel.</p>
            </div>
        </div>
        <div class="space-y-4 p-5">
            @foreach (\App\Models\PengaturanAplikasi::IDENTITAS as $kunci => [, $label])
                <div>
                    <label for="{{ $kunci }}" class="label">{{ $label }}</label>
                    <input id="{{ $kunci }}" name="{{ $kunci }}" value="{{ old($kunci, $identitas[$kunci]) }}" class="input" maxlength="150">
                    @error($kunci) <p class="galat">{{ $message }}</p> @enderror
                </div>
            @endforeach
            <button class="btn btn-primary w-full">Simpan identitas</button>
        </div>
    </form>

    {{-- Tahun buku --}}
    <div class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Tahun buku</h2>
                <p class="mt-0.5 text-xs text-slate-500">Saat tahun buku baru dibuka, saldo simpanan dan pinjaman otomatis berlanjut.</p>
            </div>
            <form method="POST" action="{{ route('pengaturan.tahun-baru') }}" class="w-full sm:w-auto" onsubmit="return confirm('Buka tahun buku berikutnya? Tahun buku {{ $tahun->nama }} tidak lagi menjadi tahun aktif.')">
                @csrf
                <button class="btn btn-ghost w-full sm:w-auto">Buka tahun buku berikutnya</button>
            </form>
        </div>
        @foreach ($daftarTahun as $t)
            <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 text-sm first:border-t-0">
                <div>
                    <p class="font-medium text-slate-900">{{ $t->nama }}</p>
                    <p class="text-xs text-slate-500">{{ $t->mulai->translatedFormat('j F Y') }} sampai {{ $t->selesai->translatedFormat('j F Y') }}</p>
                </div>
                <span @class(['badge', 'badge-blue' => $t->aktif, 'badge-slate' => ! $t->aktif])>{{ $t->aktif ? 'Sedang berjalan' : 'Selesai' }}</span>
            </div>
        @endforeach
    </div>
    </div>
    </div>
@endsection

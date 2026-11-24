@extends('layouts.app')

@php $baru = ! $anggota->exists; @endphp

@section('judul', $baru ? 'Anggota Baru' : 'Ubah Data Anggota')
@section('keterangan', $baru ? 'Akun login anggota dibuat otomatis setelah disimpan.' : $anggota->nama)

@section('isi')
    <form method="POST" action="{{ $baru ? route('anggota.store') : route('anggota.update', $anggota) }}" class="card max-w-4xl">
        @csrf
        @unless ($baru) @method('PUT') @endunless

        <div class="space-y-8 p-5 sm:p-6">
            <section>
                <h2 class="card-title">Identitas</h2>
                <div class="mt-4 grid gap-5 sm:grid-cols-6">
                    <div class="sm:col-span-2">
                        <label for="kode" class="label">ID anggota</label>
                        <input id="kode" value="{{ $anggota->kode }}" class="input cursor-not-allowed bg-slate-100 font-mono font-semibold tracking-wider text-slate-600" disabled>
                        <p class="hint">Nomor anggota, dibuat otomatis.</p>
                    </div>
                    <div class="sm:col-span-4">
                        <label for="nama" class="label">Nama lengkap</label>
                        <input id="nama" name="nama" value="{{ old('nama', $anggota->nama) }}" class="input" maxlength="100" required>
                        <p class="hint">
                            Nama pengguna untuk masuk dibuat otomatis dari nama ini, misalnya "Muhamad Rizky, S.Pd." menjadi muhamad.rizky.
                            @unless ($baru) Sekarang: <span class="font-mono text-slate-700">{{ $anggota->user?->username }}</span>. @endunless
                        </p>
                        @error('nama') <p class="galat">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="jabatan" class="label">Jabatan</label>
                        <select id="jabatan" name="jabatan" class="input">
                            @foreach (['Guru', 'Tata Usaha', 'Lainnya'] as $j)
                                <option @selected(old('jabatan', $anggota->jabatan ?? 'Guru') === $j)>{{ $j }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="no_hp" class="label">Nomor HP <span class="font-normal text-slate-400">(boleh kosong)</span></label>
                        <input id="no_hp" name="no_hp" value="{{ old('no_hp', $anggota->no_hp) }}" class="input" maxlength="30" inputmode="tel">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="tanggal_masuk" class="label">Tanggal masuk</label>
                        <input id="tanggal_masuk" type="date" name="tanggal_masuk" value="{{ old('tanggal_masuk', $anggota->tanggal_masuk?->toDateString()) }}" class="input" required>
                        @error('tanggal_masuk') <p class="galat">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="border-t border-slate-100 pt-6">
                <h2 class="card-title">Setoran rutin</h2>
                <p class="mt-1 text-sm text-slate-500">Dipakai untuk menyiapkan tagihan bulanan secara otomatis. Simpanan wajib mengikuti pengaturan, sekarang {{ rupiah($tahun->simpanan_wajib) }} per bulan.</p>
                <div class="mt-4 max-w-sm">
                    <label class="label">Simpanan manasuka per bulan</label>
                    <x-rupiah name="setoran_manasuka" :value="$anggota->setoran_manasuka" />
                    <p class="hint">Isi 0 jika anggota tidak menabung rutin.</p>
                </div>

                @php
                    $caraAwal = collect(\App\Models\Anggota::CARA_BAYAR_BAGIAN)
                        ->mapWithKeys(fn ($b) => [$b['kolom'] => old($b['kolom'], $anggota->{$b['kolom']} ?? 'setor')]);
                @endphp
                <div class="mt-6" x-data="{ cara: @js($caraAwal), semua(metode) { Object.keys(this.cara).forEach(k => this.cara[k] = metode) } }">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="label mb-0">Cara bayar tiap bagian</span>
                        <div class="flex gap-2 text-xs">
                            <button type="button" class="font-medium text-blue-700 hover:text-blue-900" @click="semua('potong_gaji')">Semua potong gaji</button>
                            <span class="text-slate-300">|</span>
                            <button type="button" class="font-medium text-blue-700 hover:text-blue-900" @click="semua('setor')">Semua setor langsung</button>
                        </div>
                    </div>
                    <div class="mt-2 divide-y divide-slate-100 rounded-xl border border-slate-200">
                        @foreach (\App\Models\Anggota::CARA_BAYAR_BAGIAN as $bagian)
                            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                                <span class="text-sm text-slate-700">{{ $bagian['label'] }}</span>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach (\App\Models\Transaksi::METODE as $kunci => $label)
                                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm has-checked:border-blue-500 has-checked:bg-blue-50 has-checked:text-blue-800">
                                            <input type="radio" name="{{ $bagian['kolom'] }}" value="{{ $kunci }}" x-model="cara.{{ $bagian['kolom'] }}" class="size-4 accent-blue-600"> {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="hint">Untuk anggota yang bayarnya campuran, misalnya simpanan wajib dan angsuran dipotong gaji tetapi manasuka disetor langsung. Simpanan pokok selalu disetor langsung.</p>
                    @error('bayar_wajib') <p class="galat">{{ $message }}</p> @enderror
                </div>
            </section>

            @if ($baru)
                <section class="border-t border-slate-100 pt-6">
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="catat_pokok" value="1" class="mt-0.5 size-4 rounded accent-blue-600" @checked(old('catat_pokok', true))>
                        <span>
                            <span class="font-medium text-slate-900">Catat simpanan pokok {{ rupiah($tahun->simpanan_pokok) }}</span>
                            <span class="block text-slate-500">Dibayar sekali saat pertama kali menjadi anggota.</span>
                        </span>
                    </label>
                </section>
            @else
                <section class="border-t border-slate-100 pt-6">
                    <label for="status" class="label">Status keanggotaan</label>
                    <select id="status" name="status" class="input max-w-xs">
                        <option value="aktif" @selected(old('status', $anggota->status) === 'aktif')>Aktif</option>
                        <option value="keluar" @selected(old('status', $anggota->status) === 'keluar')>Keluar</option>
                    </select>
                    <p class="hint">Anggota yang keluar tidak muncul lagi di tagihan bulanan. Riwayatnya tetap tersimpan.</p>
                </section>
            @endif
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4 sm:px-6">
            <a href="{{ $baru ? route('anggota.index') : route('anggota.show', $anggota) }}" class="btn btn-ghost">Batal</a>
            <button class="btn btn-primary">{{ $baru ? 'Simpan anggota' : 'Simpan perubahan' }}</button>
        </div>
    </form>
@endsection

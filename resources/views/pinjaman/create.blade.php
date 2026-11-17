@extends('layouts.app')

@section('judul', 'Pinjaman Baru')
@section('keterangan', 'Angsuran, jasa, dan provisi dihitung otomatis dari pengaturan.')

@section('isi')
    <div class="grid gap-6 lg:grid-cols-3" x-data="{
            pokok: @js((int) old('pokok', 0)), tenor: @js((int) old('tenor', 10)),
            jasaPersen: @js((float) old('jasa_persen', $tahun->jasa_persen)), provisiPersen: @js((float) old('provisi_persen', $tahun->provisi_persen)),
            metode: @js(old('jasa_metode', $tahun->jasa_metode)), lanjutan: {{ $errors->hasAny(['jasa_persen', 'provisi_persen', 'jasa_metode']) ? 'true' : 'false' }},
            get angsuran() { return this.tenor > 0 ? Math.ceil(this.pokok / this.tenor / 1000) * 1000 : 0 },
            get jasa() { return Math.round(this.pokok * this.jasaPersen / 100) },
            get provisi() { return Math.round(this.pokok * this.provisiPersen / 100) },
        }">
        <form method="POST" action="{{ route('pinjaman.store') }}" class="card lg:col-span-2">
            @csrf
            <div class="space-y-6 p-5 sm:p-6">
                <div>
                    <label class="label">Anggota peminjam</label>
                    <x-pilih-anggota :anggota="$anggota" :selected="$terpilih" />
                    @error('anggota_id') <p class="galat">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <label class="label">Jumlah pinjaman</label>
                        <x-rupiah name="pokok" required @nilai="pokok = $event.detail" />
                        @error('pokok') <p class="galat">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="tenor" class="label">Lama angsuran</label>
                        <div class="relative">
                            <input id="tenor" type="number" name="tenor" min="1" max="120" x-model.number="tenor" class="input pr-14" required>
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">bulan</span>
                        </div>
                        @error('tenor') <p class="galat">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="tanggal" class="label">Tanggal pinjam</label>
                        <input id="tanggal" type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="input" required>
                        @error('tanggal') <p class="galat">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="keterangan" class="label">Keperluan <span class="font-normal text-slate-400">(boleh kosong)</span></label>
                    <input id="keterangan" name="keterangan" value="{{ old('keterangan') }}" class="input" maxlength="255" placeholder="Contoh: biaya sekolah anak">
                </div>

                {{-- Tarif khusus: tersembunyi supaya form tetap ringkas --}}
                <div class="rounded-xl border border-slate-200">
                    <button type="button" class="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-medium text-slate-700" @click="lanjutan = !lanjutan">
                        <span>Tarif untuk pinjaman ini <span class="font-normal text-slate-500">· jasa <span x-text="jasaPersen"></span>%, provisi <span x-text="provisiPersen"></span>%</span></span>
                        <x-icon name="down" class="size-4 text-slate-400 transition" ::class="lanjutan && 'rotate-180'" />
                    </button>
                    <div x-show="lanjutan" x-cloak class="grid gap-5 border-t border-slate-100 p-4 sm:grid-cols-3">
                        <div>
                            <label for="jasa_persen" class="label">Jasa per bulan</label>
                            <div class="relative">
                                <input id="jasa_persen" type="number" step="0.01" min="0" max="100" name="jasa_persen" x-model.number="jasaPersen" class="input pr-8">
                                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">%</span>
                            </div>
                        </div>
                        <div>
                            <label for="jasa_metode" class="label">Cara hitung jasa</label>
                            <select id="jasa_metode" name="jasa_metode" x-model="metode" class="input">
                                <option value="tetap">Tetap dari pinjaman awal</option>
                                <option value="menurun">Menurun dari sisa pinjaman</option>
                            </select>
                        </div>
                        <div>
                            <label for="provisi_persen" class="label">Provisi</label>
                            <div class="relative">
                                <input id="provisi_persen" type="number" step="0.01" min="0" max="100" name="provisi_persen" x-model.number="provisiPersen" class="input pr-8">
                                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">%</span>
                            </div>
                        </div>
                        <p class="hint sm:col-span-3">Nilai awal mengikuti halaman Pengaturan. Perubahan di sini hanya berlaku untuk pinjaman ini.</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4 sm:px-6">
                <a href="{{ route('pinjaman.index') }}" class="btn btn-ghost">Batal</a>
                <button class="btn btn-primary">Simpan pinjaman</button>
            </div>
        </form>

        {{-- Simulasi langsung --}}
        <div class="card h-fit overflow-hidden">
            <div class="bg-blue-600 p-5 text-white">
                <p class="text-sm text-blue-100">Tagihan per bulan</p>
                <p class="mt-1 text-2xl font-semibold tracking-tight tabular-nums" x-text="rupiah(angsuran + jasa)"></p>
                <p class="mt-1 text-xs text-blue-100" x-show="metode === 'menurun'" x-cloak>Bulan pertama. Jasa turun mengikuti sisa pinjaman.</p>
            </div>
            <dl class="space-y-3 p-5 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Angsuran pokok</dt><dd class="font-medium text-slate-900 tabular-nums" x-text="rupiah(angsuran)"></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Jasa pinjaman</dt><dd class="font-medium text-slate-900 tabular-nums" x-text="rupiah(jasa)"></dd></div>
                <div class="flex justify-between border-t border-slate-100 pt-3"><dt class="text-slate-500">Provisi (sekali, saat cair)</dt><dd class="font-medium text-slate-900 tabular-nums" x-text="rupiah(provisi)"></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Uang diterima anggota</dt><dd class="font-semibold text-slate-900 tabular-nums" x-text="rupiah(pokok - provisi)"></dd></div>
            </dl>
        </div>
    </div>
@endsection

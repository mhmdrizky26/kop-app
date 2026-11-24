@extends('layouts.app')

@section('judul', 'Catat Penarikan')
@section('keterangan', 'Untuk anggota yang mengambil simpanannya. Semua setoran dicatat lewat Tagihan bulanan.')

@section('isi')
    @php
        $saldoJs = $saldo->map(fn ($s) => ['pokok' => $s['pokok'], 'wajib' => $s['wajib'], 'manasuka' => $s['manasuka'], 'pinjaman' => $s['pinjaman']]);
    @endphp
    <div class="grid gap-6 lg:grid-cols-3" x-data="{
            saldo: @js($saldoJs), anggota: @js(old('anggota_id', $terpilih)), jenis: @js(old('jenis', 'tarik_manasuka')), jumlah: @js((int) old('jumlah', 0)),
            get milik() { return this.saldo[this.anggota] ?? null },
            get kunci() { return this.jenis.split('_')[1] },
            get lebih() { return this.milik && this.jumlah > this.milik[this.kunci] },
        }">
        <form method="POST" action="{{ route('transaksi.store') }}" class="card lg:col-span-2">
            @csrf
            <div class="space-y-6 p-5 sm:p-6">
                <div>
                    <label class="label">Anggota</label>
                    <x-pilih-anggota :anggota="$anggota" :selected="$terpilih" @anggota="anggota = $event.detail" />
                    @error('anggota_id') <p class="galat">{{ $message }}</p> @enderror
                </div>

                <div>
                    <span class="label">Simpanan yang ditarik</span>
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach ($jenis as $kunci => $j)
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-200 px-3 py-2 text-sm has-checked:border-blue-500 has-checked:bg-blue-50 has-checked:text-blue-800">
                                <input type="radio" name="jenis" value="{{ $kunci }}" x-model="jenis" class="size-4 accent-blue-600">
                                {{ $j['label'] }}
                            </label>
                        @endforeach
                    </div>
                    <p class="hint">Simpanan pokok dan wajib biasanya hanya ditarik saat anggota keluar.</p>
                    @error('jenis') <p class="galat">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="label">Jumlah</label>
                        <x-rupiah name="jumlah" required @nilai="jumlah = $event.detail" />
                        <p class="galat" x-show="lebih" x-cloak>Melebihi saldo yang bisa ditarik.</p>
                        @error('jumlah') <p class="galat">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="tanggal" class="label">Tanggal</label>
                        <input id="tanggal" type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="input" required>
                        @error('tanggal') <p class="galat">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="keterangan" class="label">Catatan <span class="font-normal text-slate-400">(boleh kosong)</span></label>
                        <input id="keterangan" name="keterangan" value="{{ old('keterangan') }}" class="input" maxlength="255" placeholder="Contoh: untuk biaya sekolah anak">
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4 sm:px-6">
                <a href="{{ route('transaksi.index') }}" class="btn btn-ghost">Batal</a>
                <button class="btn btn-primary" :disabled="lebih">Simpan penarikan</button>
            </div>
        </form>

        {{-- Saldo anggota terpilih, supaya pengurus tidak perlu membuka halaman lain --}}
        <div class="card h-fit p-5">
            <h2 class="card-title">Saldo anggota</h2>
            <template x-if="!milik">
                <p class="mt-3 text-sm text-slate-500">Pilih anggota untuk melihat saldonya di sini.</p>
            </template>
            <template x-if="milik">
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Simpanan pokok</dt><dd class="font-medium text-slate-900 tabular-nums" x-text="rupiah(milik.pokok)"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Simpanan wajib</dt><dd class="font-medium text-slate-900 tabular-nums" x-text="rupiah(milik.wajib)"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Simpanan manasuka</dt><dd class="font-medium text-slate-900 tabular-nums" x-text="rupiah(milik.manasuka)"></dd></div>
                    <div class="flex justify-between border-t border-slate-100 pt-3"><dt class="text-slate-500">Sisa pinjaman</dt><dd class="font-medium text-slate-900 tabular-nums" x-text="rupiah(milik.pinjaman)"></dd></div>
                    <div class="rounded-lg bg-blue-50 px-3 py-2.5 text-blue-900" x-show="jumlah > 0 && !lebih">
                        <span class="text-xs text-blue-700">Saldo setelah transaksi ini</span>
                        <p class="font-semibold tabular-nums" x-text="rupiah(milik[kunci] - jumlah)"></p>
                    </div>
                </dl>
            </template>
        </div>
    </div>
@endsection

@extends('layouts.app')

@php $baru = ! $barang->exists; @endphp

@section('judul', $baru ? 'Tambah Barang Inventaris' : 'Ubah Barang Inventaris')
@section('keterangan', $baru ? 'Barang milik koperasi, misalnya laptop, printer, atau lemari.' : $barang->nama)

@section('isi')
    <form method="POST" action="{{ $baru ? route('inventaris.store') : route('inventaris.update', $barang) }}" class="card max-w-4xl">
        @csrf
        @unless ($baru) @method('PUT') @endunless

        <div class="space-y-8 p-5 sm:p-6">
            <section class="grid gap-5 sm:grid-cols-6">
                <div class="sm:col-span-4">
                    <label for="nama" class="label">Nama barang</label>
                    <input id="nama" name="nama" value="{{ old('nama', $barang->nama) }}" class="input" maxlength="100" required placeholder="Contoh: Laptop Asus">
                    @error('nama') <p class="galat">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-1">
                    <label for="jumlah" class="label">Jumlah</label>
                    <input id="jumlah" type="number" min="1" name="jumlah" value="{{ old('jumlah', $barang->jumlah) }}" class="input" required>
                    @error('jumlah') <p class="galat">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-1">
                    <label for="satuan" class="label">Satuan</label>
                    <input id="satuan" name="satuan" value="{{ old('satuan', $barang->satuan) }}" class="input" maxlength="20" required>
                </div>
                <div class="sm:col-span-2">
                    <label for="tanggal_perolehan" class="label">Tanggal perolehan</label>
                    <input id="tanggal_perolehan" type="date" name="tanggal_perolehan" value="{{ old('tanggal_perolehan', $barang->tanggal_perolehan?->toDateString()) }}" max="{{ now()->toDateString() }}" class="input" required>
                    @error('tanggal_perolehan') <p class="galat">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Nilai perolehan</label>
                    <x-rupiah name="nilai" :value="$barang->nilai" required />
                    <p class="hint">Harga seluruh jumlah barang.</p>
                    @error('nilai') <p class="galat">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2" x-data="{ umur: @js((int) old('umur_tahun', $barang->umur_tahun)) }">
                    <label for="umur_tahun" class="label">Umur pakai (tahun)</label>
                    <input id="umur_tahun" type="number" min="0" max="50" name="umur_tahun" x-model.number="umur" class="input" required>
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        @foreach (\App\Models\Inventaris::UMUR_PAKAI as $tahunPakai => $keterangan)
                            <button type="button" class="rounded-md border border-slate-200 px-2 py-0.5 text-xs text-slate-600 hover:border-blue-300 hover:text-blue-700"
                                :class="umur === {{ $tahunPakai }} && 'border-blue-500 bg-blue-50 text-blue-700'" @click="umur = {{ $tahunPakai }}" title="{{ $keterangan }}">
                                {{ $tahunPakai === 0 ? 'Tidak disusutkan' : $tahunPakai.' tahun' }}
                            </button>
                        @endforeach
                    </div>
                    <p class="hint">Untuk penyusutan per bulan. 4 tahun untuk elektronik dan perabot kecil, 8 tahun untuk perabot dan mesin besar.</p>
                    @error('umur_tahun') <p class="galat">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="kondisi" class="label">Kondisi</label>
                    <select id="kondisi" name="kondisi" class="input">
                        @foreach (\App\Models\Inventaris::KONDISI as $kunci => $label)
                            <option value="{{ $kunci }}" @selected(old('kondisi', $barang->kondisi) === $kunci)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="lokasi" class="label">Lokasi <span class="font-normal text-slate-400">(boleh kosong)</span></label>
                    <input id="lokasi" name="lokasi" value="{{ old('lokasi', $barang->lokasi) }}" class="input" maxlength="100" placeholder="Contoh: Ruang koperasi">
                </div>
                <div class="sm:col-span-2">
                    <label for="keterangan" class="label">Catatan <span class="font-normal text-slate-400">(boleh kosong)</span></label>
                    <input id="keterangan" name="keterangan" value="{{ old('keterangan', $barang->keterangan) }}" class="input" maxlength="255">
                </div>
            </section>

            @if ($baru)
                <section class="border-t border-slate-100 pt-6">
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="catat_kas" value="1" class="mt-0.5 size-4 rounded accent-blue-600" @checked(old('catat_kas', true))>
                        <span>
                            <span class="font-medium text-slate-900">Catat juga sebagai pengeluaran kas</span>
                            <span class="block text-slate-500">Masuk ke Kas lain-lain dengan kategori "Pembelian inventaris" (tidak mengurangi SHU). Hilangkan centang jika barang ini hibah atau sudah dicatat sebelumnya.</span>
                        </span>
                    </label>
                </section>
            @else
                <section class="border-t border-slate-100 pt-6">
                    <label for="tanggal_dihapus" class="label">Tanggal dihapuskan <span class="font-normal text-slate-400">(isi jika barang dijual, hilang, atau rusak total)</span></label>
                    <input id="tanggal_dihapus" type="date" name="tanggal_dihapus" value="{{ old('tanggal_dihapus', $barang->tanggal_dihapus?->toDateString()) }}" max="{{ now()->toDateString() }}" class="input max-w-xs">
                    <p class="hint">Barang yang dihapuskan tidak lagi dihitung sebagai kekayaan, tetapi riwayatnya tetap tersimpan.</p>
                    @error('tanggal_dihapus') <p class="galat">{{ $message }}</p> @enderror
                </section>
            @endif
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-5 py-4 sm:px-6">
            <div>
                @unless ($baru)
                    <button type="submit" form="hapus-barang" class="btn btn-danger">Hapus catatan</button>
                @endunless
            </div>
            <div class="flex gap-2">
                <a href="{{ route('inventaris.index') }}" class="btn btn-ghost">Batal</a>
                <button class="btn btn-primary">{{ $baru ? 'Simpan barang' : 'Simpan perubahan' }}</button>
            </div>
        </div>
    </form>

    @unless ($baru)
        <form id="hapus-barang" method="POST" action="{{ route('inventaris.destroy', $barang) }}" onsubmit="return confirm('Hapus catatan ini? Gunakan hanya untuk salah input. Untuk barang yang dijual atau rusak, isi tanggal dihapuskan.')">
            @csrf @method('DELETE')
        </form>
    @endunless
@endsection

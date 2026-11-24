@extends('layouts.app')

@section('judul', 'Kas Lain-lain')
@section('keterangan', 'Semua uang koperasi di luar simpanan, pinjaman, jasa, dan provisi anggota. Yang bertanda "di luar SHU" (modal warung, pinjaman di luar anggota, paket munggahan, inventaris, dana sosial) tidak ikut dihitung dalam SHU. Paket munggahan cukup dicatat saat dibeli; tahun berikutnya otomatis dibebankan ke SHU.')

@section('isi')
    @php $bisaUbah = auth()->user()->can('ubah-data'); @endphp
    <div @class(['grid gap-6', 'lg:grid-cols-3' => $bisaUbah])>
        <div @class(['space-y-6', 'lg:col-span-2' => $bisaUbah])>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-stat label="Pendapatan lain" :nilai="rupiah_singkat($pendapatan)" ikon="down-left" catatan="Yang dihitung dalam SHU" />
                <x-stat label="Biaya usaha" :nilai="rupiah_singkat($biaya)" ikon="up-right" catatan="Yang dihitung dalam SHU" />
                <x-stat utama label="Selisih" :nilai="rupiah_singkat($pendapatan - $biaya)" ikon="wallet" />
            </div>

            <div class="card">
                <div class="card-head"><h2 class="card-title">Catatan tahun buku {{ $tahun->nama }}</h2></div>
                <div class="overflow-x-auto">
                    <table class="tbl tbl-stack">
                        <thead><tr><th>Tanggal</th><th>Kategori</th><th>Jenis</th><th class="num">Jumlah</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($kas as $k)
                                <tr>
                                    <td class="whitespace-nowrap text-slate-500">{{ $k->tanggal->translatedFormat('j M Y') }}</td>
                                    <td data-label="Kategori">
                                        <span class="min-w-0">
                                            <span class="block font-medium text-slate-900">{{ $k->kategori }}</span>
                                            @if ($k->keterangan) <span class="block text-xs text-slate-500">{{ $k->keterangan }}</span> @endif
                                        </span>
                                    </td>
                                    <td data-label="Jenis">
                                        <span @class(['badge', 'badge-blue' => $k->tipe === 'pendapatan', 'badge-slate' => $k->tipe === 'biaya'])>{{ $k->tipe === 'pendapatan' ? 'Pendapatan' : 'Biaya' }}</span>
                                        @unless ($k->masuk_shu) <span class="badge badge-amber">Di luar SHU</span> @endunless
                                    </td>
                                    <td data-label="Jumlah" @class(['num font-medium', 'text-slate-900' => $k->tipe === 'pendapatan', 'text-slate-500' => $k->tipe === 'biaya'])>{{ $k->tipe === 'biaya' ? '−' : '+' }}{{ rupiah($k->jumlah, false) }}</td>
                                    <td class="text-right">
                                        @can('ubah-data')
                                        <form method="POST" action="{{ route('kas.destroy', $k) }}" onsubmit="return confirm('Hapus catatan ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger" aria-label="Hapus catatan"><x-icon name="trash" class="size-3.5" /></button>
                                        </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-12 text-center text-slate-500">Belum ada catatan kas pada tahun buku ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if ($bisaUbah)
        <form method="POST" action="{{ route('kas.store') }}" class="card h-fit p-5" x-data="{ tipe: @js(old('tipe', 'biaya')), kategori: @js(old('kategori')), daftar: @js(\App\Models\KasLain::KATEGORI), luarShu: @js(\App\Models\KasLain::DI_LUAR_SHU) }"
            x-init="$watch('tipe', () => kategori = daftar[tipe][0]); kategori ??= daftar[tipe][0]">
            @csrf
            <h2 class="card-title">Tambah catatan</h2>
            <div class="mt-4 space-y-4">
                <div class="grid grid-cols-2 gap-1 rounded-xl bg-slate-100 p-1">
                    <label class="tab cursor-pointer text-center" :class="tipe === 'biaya' && 'tab-on'"><input type="radio" name="tipe" value="biaya" x-model="tipe" class="sr-only"> Biaya</label>
                    <label class="tab cursor-pointer text-center" :class="tipe === 'pendapatan' && 'tab-on'"><input type="radio" name="tipe" value="pendapatan" x-model="tipe" class="sr-only"> Pendapatan</label>
                </div>
                <div>
                    <label for="kategori" class="label">Kategori</label>
                    <select id="kategori" name="kategori" class="input" x-model="kategori">
                        <template x-for="k in daftar[tipe]" :key="k"><option x-text="k" :value="k" :selected="k === kategori"></option></template>
                    </select>
                    <p class="hint" x-show="luarShu.includes(kategori)" x-cloak>Perpindahan modal atau dana. Tercatat di kas, tetapi tidak dihitung dalam SHU.</p>
                </div>
                <div>
                    <label class="label">Jumlah</label>
                    <x-rupiah name="jumlah" required />
                    @error('jumlah') <p class="galat">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tanggal" class="label">Tanggal</label>
                    <input id="tanggal" type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="input" required>
                </div>
                <div>
                    <label for="keterangan" class="label">Catatan <span class="font-normal text-slate-400">(boleh kosong)</span></label>
                    <input id="keterangan" name="keterangan" value="{{ old('keterangan') }}" class="input" maxlength="255">
                </div>
            </div>
            <button class="btn btn-primary mt-5 w-full">Simpan catatan</button>
        </form>
        @endif
    </div>
@endsection

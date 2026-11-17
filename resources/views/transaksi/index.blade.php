@extends('layouts.app')

@section('judul', 'Setor & Tarik')
@section('keterangan', 'Semua uang masuk dan keluar anggota, yang terbaru di atas.')

@section('aksi')
    <a href="{{ route('transaksi.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Catat setor / tarik</a>
@endsection

@section('isi')
    <div class="card">
        <form method="GET" class="card-head">
            <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:gap-3">
                <div class="relative w-full sm:w-auto">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"><x-icon name="search" class="size-4" /></span>
                    <input type="search" name="cari" value="{{ request('cari') }}" class="input w-full pl-9 sm:w-56" placeholder="Nama atau ID anggota" aria-label="Cari anggota">
                </div>
                <input type="month" name="bulan" value="{{ request('bulan') }}" max="{{ now()->format('Y-m') }}" class="input w-auto flex-1 sm:flex-none" aria-label="Bulan">
                <select name="jenis" class="input w-auto flex-1 sm:flex-none" aria-label="Jenis transaksi">
                    <option value="">Semua jenis</option>
                    @foreach (\App\Models\Transaksi::JENIS as $kunci => $j)
                        <option value="{{ $kunci }}" @selected(request('jenis') === $kunci)>{{ $j['label'] }}</option>
                    @endforeach
                </select>
                <button class="btn btn-soft max-sm:flex-1">Terapkan</button>
                @if (request()->hasAny(['cari', 'bulan', 'jenis']))
                    <a href="{{ route('transaksi.index') }}" class="text-sm text-slate-500 hover:text-slate-900">Hapus saringan</a>
                @endif
            </div>
            <p class="text-sm text-slate-500">{{ number_format($transaksi->total(), 0, ',', '.') }} transaksi</p>
        </form>

        <div class="overflow-x-auto">
            <table class="tbl tbl-stack">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Anggota</th>
                        <th>Jenis</th>
                        <th>Cara bayar</th>
                        <th class="num">Jumlah</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transaksi as $t)
                        <tr>
                            <td class="whitespace-nowrap text-slate-500">{{ $t->tanggal->translatedFormat('j M Y') }}</td>
                            <td data-label="Anggota">
                                <span class="min-w-0">
                                    <a href="{{ route('anggota.show', $t->anggota) }}" class="font-medium text-slate-900 hover:text-blue-700">{{ $t->anggota->nama }}</a>
                                    @if ($t->keterangan)
                                        <span class="block text-xs text-slate-500">{{ $t->keterangan }}</span>
                                    @endif
                                </span>
                            </td>
                            <td data-label="Jenis"><span @class(['badge', 'badge-blue' => $t->arah === 'masuk', 'badge-slate' => $t->arah === 'keluar'])>{{ $t->label }}</span></td>
                            <td class="text-slate-500" data-label="Cara bayar">{{ $t->label_metode ?? '–' }}</td>
                            <td data-label="Jumlah" @class(['num font-medium', 'text-slate-900' => $t->arah === 'masuk', 'text-slate-500' => $t->arah === 'keluar'])>{{ $t->arah === 'keluar' ? '−' : '+' }}{{ rupiah($t->jumlah, false) }}</td>
                            <td class="text-right">
                                @if ($t->bisa_dihapus)
                                    <form method="POST" action="{{ route('transaksi.destroy', $t) }}" onsubmit="return confirm('Hapus transaksi ini?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger" aria-label="Hapus transaksi"><x-icon name="trash" class="size-3.5" /></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-12 text-center text-slate-500">Tidak ada transaksi yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($transaksi->hasPages())
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 text-sm">
                <p class="text-slate-500">Halaman {{ $transaksi->currentPage() }} dari {{ $transaksi->lastPage() }}</p>
                <div class="flex gap-2">
                    @if ($transaksi->onFirstPage())
                        <span class="btn btn-ghost btn-sm opacity-40">Sebelumnya</span>
                    @else
                        <a href="{{ $transaksi->previousPageUrl() }}" class="btn btn-ghost btn-sm">Sebelumnya</a>
                    @endif
                    @if ($transaksi->hasMorePages())
                        <a href="{{ $transaksi->nextPageUrl() }}" class="btn btn-ghost btn-sm">Berikutnya</a>
                    @else
                        <span class="btn btn-ghost btn-sm opacity-40">Berikutnya</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection

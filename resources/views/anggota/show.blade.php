@extends('layouts.app')

@section('judul', $anggota->nama)
@section('keterangan', $anggota->kode.($anggota->jabatan ? ' · '.$anggota->jabatan : '').' · '.($anggota->potong_gaji ? 'Potong gaji' : 'Setor langsung').($anggota->status === 'keluar' ? ' · Sudah keluar' : ''))

@section('aksi')
    <a href="{{ route('anggota.edit', $anggota) }}" class="btn btn-ghost"><x-icon name="edit" class="size-4" /> Ubah data</a>
    <a href="{{ route('transaksi.create', ['anggota' => $anggota->id]) }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Setor / tarik</a>
@endsection

@section('isi')
    @include('partials.saldo-kartu', ['saldo' => $buku['akhir']])

    <div class="mt-6">
        @include('partials.buku-tabungan')
    </div>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Pinjaman</h2>
                <a href="{{ route('pinjaman.create', ['anggota' => $anggota->id]) }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">Pinjaman baru</a>
            </div>
            @forelse ($pinjaman as $p)
                <a href="{{ route('pinjaman.show', $p) }}" class="block border-t border-slate-100 px-5 py-4 first:border-t-0 hover:bg-blue-50/30">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1.5">
                        <p class="text-sm font-medium text-slate-900">{{ rupiah($p->pokok) }} <span class="font-normal text-slate-500">· {{ $p->tanggal->translatedFormat('M Y') }}</span></p>
                        <span @class(['badge', 'badge-blue' => $p->status === 'aktif', 'badge-green' => $p->status === 'lunas'])>{{ $p->status === 'aktif' ? 'Sisa '.rupiah($p->sisa) : 'Lunas' }}</span>
                    </div>
                    <div class="mt-2.5 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-600" style="width: {{ $p->persen_lunas }}%"></div></div>
                </a>
            @empty
                <p class="px-5 py-10 text-center text-sm text-slate-500">Belum pernah meminjam.</p>
            @endforelse
        </div>

        <div class="card">
            <div class="card-head"><h2 class="card-title">Transaksi terakhir</h2></div>
            @forelse ($transaksi as $t)
                <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 text-sm first:border-t-0">
                    <div class="min-w-0">
                        <p class="font-medium text-slate-900">{{ $t->label }}</p>
                        <p class="text-xs text-slate-500">{{ $t->tanggal->translatedFormat('j M Y') }}{{ $t->label_metode ? ' · '.$t->label_metode : '' }}{{ $t->keterangan ? ' · '.$t->keterangan : '' }}</p>
                    </div>
                    <span @class(['shrink-0 font-medium whitespace-nowrap tabular-nums', 'text-slate-900' => $t->arah === 'masuk', 'text-slate-500' => $t->arah === 'keluar'])>{{ $t->arah === 'keluar' ? '−' : '+' }}{{ rupiah($t->jumlah, false) }}</span>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-sm text-slate-500">Belum ada transaksi.</p>
            @endforelse
        </div>
    </div>

    <div class="card mt-6 flex flex-wrap items-center justify-between gap-4 p-5">
        <div>
            <h2 class="card-title">Login anggota</h2>
            <p class="mt-1 text-sm text-slate-500">ID anggota: <span class="font-mono text-base font-semibold tracking-wider text-slate-900">{{ $anggota->kode }}</span></p>
            <p class="mt-1 text-xs text-slate-500">Anggota masuk cukup dengan ID ini, hanya untuk melihat datanya sendiri.</p>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('judul', 'Pinjaman '.$pinjaman->anggota->nama)
@section('keterangan', 'Dipinjam '.$pinjaman->tanggal->translatedFormat('j F Y').($pinjaman->keterangan ? ' · '.$pinjaman->keterangan : ''))

@section('aksi')
    <a href="{{ route('pinjaman.index') }}" class="btn btn-ghost"><x-icon name="left" class="size-4" /> Semua pinjaman</a>
@endsection

@section('isi')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card p-5 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500">Sisa pinjaman</p>
                        <p class="mt-1 text-2xl font-semibold sm:text-3xl tracking-tight text-slate-900 tabular-nums">{{ rupiah($pinjaman->sisa) }}</p>
                    </div>
                    <span @class(['badge', 'badge-blue' => $pinjaman->status === 'aktif', 'badge-green' => $pinjaman->status === 'lunas'])>{{ $pinjaman->status === 'aktif' ? 'Masih berjalan' : 'Lunas' }}</span>
                </div>
                <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-600" style="width: {{ $pinjaman->persen_lunas }}%"></div></div>
                <p class="mt-2 text-xs text-slate-500">Terbayar {{ rupiah($pinjaman->terbayar) }} dari {{ rupiah($pinjaman->pokok) }} ({{ $pinjaman->persen_lunas }}%)</p>

                <dl class="mt-6 grid grid-cols-2 gap-x-6 gap-y-4 border-t border-slate-100 pt-5 text-sm sm:grid-cols-3">
                    <div><dt class="text-slate-500">Angsuran pokok per bulan</dt><dd class="mt-0.5 font-medium text-slate-900 tabular-nums">{{ rupiah($pinjaman->angsuran_pokok) }}</dd></div>
                    <div><dt class="text-slate-500">Jasa per bulan</dt><dd class="mt-0.5 font-medium text-slate-900 tabular-nums">{{ rupiah($pinjaman->tagihan_jasa) }}</dd></div>
                    <div><dt class="text-slate-500">Lama angsuran</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $pinjaman->tenor }} bulan</dd></div>
                    <div><dt class="text-slate-500">Tarif jasa</dt><dd class="mt-0.5 font-medium text-slate-900">{{ persen($pinjaman->jasa_persen) }}, {{ $pinjaman->jasa_metode === 'menurun' ? 'menurun' : 'tetap' }}</dd></div>
                    <div><dt class="text-slate-500">Provisi saat cair</dt><dd class="mt-0.5 font-medium text-slate-900 tabular-nums">{{ rupiah($pinjaman->provisi) }}</dd></div>
                    <div><dt class="text-slate-500">Total jasa dibayar</dt><dd class="mt-0.5 font-medium text-slate-900 tabular-nums">{{ rupiah($jasaDibayar) }}</dd></div>
                </dl>
            </div>

            <div class="card">
                <div class="card-head"><h2 class="card-title">Riwayat pinjaman</h2></div>
                <div class="overflow-x-auto">
                    <table class="tbl tbl-stack">
                        <thead><tr><th>Tanggal</th><th>Jenis</th><th>Cara bayar</th><th class="num">Jumlah</th></tr></thead>
                        <tbody>
                            @foreach ($riwayat as $t)
                                <tr>
                                    <td class="whitespace-nowrap text-slate-500">{{ $t->tanggal->translatedFormat('j M Y') }}</td>
                                    <td data-label="Jenis"><span>{{ $t->label }} @if ($t->keterangan)<span class="text-xs text-slate-500">· {{ $t->keterangan }}</span>@endif</span></td>
                                    <td class="text-slate-500" data-label="Cara bayar">{{ $t->label_metode ?? '–' }}</td>
                                    <td class="num font-medium text-slate-900" data-label="Jumlah">{{ rupiah($t->jumlah, false) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            @if ($pinjaman->status === 'aktif' && auth()->user()->can('ubah-data'))
                <form method="POST" action="{{ route('pinjaman.bayar', $pinjaman) }}" class="card p-5">
                    @csrf
                    <h2 class="card-title">Bayar di luar tagihan</h2>
                    <p class="hint">Untuk angsuran tambahan atau pelunasan lebih cepat. Angsuran rutin dicatat lewat Tagihan Bulanan.</p>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="label">Angsuran pokok</label>
                            <x-rupiah name="angsuran" :value="$pinjaman->sisa" />
                            <p class="hint">Terisi sisa pinjaman untuk pelunasan. Ubah jika hanya sebagian.</p>
                        </div>
                        <div>
                            <label class="label">Jasa <span class="font-normal text-slate-400">(boleh kosong)</span></label>
                            <x-rupiah name="jasa" />
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                            <div>
                                <label for="tanggal" class="label">Tanggal</label>
                                <input id="tanggal" type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="input" required>
                            </div>
                            <div>
                                <label for="metode" class="label">Cara bayar</label>
                                <select id="metode" name="metode" class="input">
                                    <option value="setor">Setor langsung</option>
                                    <option value="potong_gaji">Potong gaji</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-5 w-full">Catat pembayaran</button>
                </form>
            @endif

            <div class="card p-5">
                <h2 class="card-title">Peminjam</h2>
                <a href="{{ route('anggota.show', $pinjaman->anggota) }}" class="mt-3 flex items-center gap-3 rounded-xl border border-slate-200 p-3 hover:border-blue-200 hover:bg-blue-50/40">
                    <span class="avatar">{{ $pinjaman->anggota->inisial }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-slate-900">{{ $pinjaman->anggota->nama }}</span>
                        <span class="block text-xs text-slate-500">{{ $pinjaman->anggota->kode }} · lihat buku tabungan</span>
                    </span>
                    <x-icon name="right" class="size-4 text-slate-400" />
                </a>
                @if ($pinjaman->terbayar === 0 && $jasaDibayar === 0 && auth()->user()->can('ubah-data'))
                    <form method="POST" action="{{ route('pinjaman.destroy', $pinjaman) }}" class="mt-4" onsubmit="return confirm('Hapus pinjaman ini? Tindakan ini tidak bisa dibatalkan.')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm"><x-icon name="trash" class="size-3.5" /> Hapus pinjaman (salah input)</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection

@extends('layouts.portal')

@section('judul', 'Buku Tabungan Saya')

@section('isi')
    <div class="flex flex-wrap items-center gap-4">
        <span class="flex size-12 items-center justify-center rounded-full bg-blue-600 text-base font-semibold text-white">{{ $anggota->inisial }}</span>
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">Halo, {{ $anggota->nama }}</h1>
            <p class="text-sm text-slate-500">No. anggota {{ $anggota->nomor }} · Tahun buku {{ $tahun->nama }}</p>
        </div>
    </div>

    <div class="mt-6">
        @include('partials.saldo-kartu', ['saldo' => $buku['akhir']])
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Tagihan bulan ini --}}
        <div class="card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="card-title">Tagihan {{ $periode->translatedFormat('F Y') }}</h2>
                @if ($bayar)
                    <span class="badge badge-green"><x-icon name="check" class="size-3" /> Sudah dibayar {{ $bayar['tanggal']->translatedFormat('j M') }}</span>
                @else
                    <span class="badge badge-amber">Belum dibayar</span>
                @endif
            </div>
            @php $rincian = $bayar ?? $tagihan; @endphp
            <dl class="mt-4 space-y-2.5 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Simpanan wajib</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($rincian['wajib']) }}</dd></div>
                @if ($rincian['manasuka'] > 0)
                    <div class="flex justify-between"><dt class="text-slate-500">Simpanan manasuka</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($rincian['manasuka']) }}</dd></div>
                @endif
                @if ($rincian['angsuran'] + $rincian['jasa'] > 0)
                    <div class="flex justify-between"><dt class="text-slate-500">Angsuran pinjaman</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($rincian['angsuran']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Jasa pinjaman</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($rincian['jasa']) }}</dd></div>
                @endif
                <div class="flex justify-between border-t border-slate-100 pt-2.5 font-semibold text-slate-900"><dt>Total</dt><dd class="tabular-nums">{{ rupiah($rincian['total']) }}</dd></div>
            </dl>
            <p class="hint">{{ $anggota->potong_gaji ? 'Dibayar lewat potong gaji.' : 'Dibayar dengan setor langsung ke bendahara.' }}</p>
        </div>

        {{-- Perkiraan SHU dan pinjaman --}}
        <div class="space-y-6">
            <div class="card p-5">
                <h2 class="card-title">Perkiraan bagian SHU</h2>
                <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 tabular-nums">{{ rupiah($shu['total'] ?? 0) }}</p>
                <p class="hint">Dihitung sampai hari ini. Angka akhir ditetapkan saat Rapat Anggota Tahunan.</p>
            </div>

            @foreach ($pinjaman->where('status', 'aktif') as $p)
                <div class="card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="card-title">Pinjaman {{ $p->tanggal->translatedFormat('F Y') }}</h2>
                        <span class="badge badge-blue">{{ $p->persen_lunas }}% terbayar</span>
                    </div>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 tabular-nums">{{ rupiah($p->sisa) }}</p>
                    <p class="text-xs text-slate-500">sisa dari pinjaman {{ rupiah($p->pokok) }}</p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-600" style="width: {{ $p->persen_lunas }}%"></div></div>
                    <p class="hint">Angsuran {{ rupiah($p->tagihan_angsuran) }} ditambah jasa {{ rupiah($p->tagihan_jasa) }} per bulan.</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-6">
        @include('partials.buku-tabungan')
    </div>

    <div class="card mt-6">
        <div class="card-head"><h2 class="card-title">Transaksi terakhir</h2></div>
        @forelse ($transaksi as $t)
            <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 text-sm first:border-t-0">
                <div>
                    <p class="font-medium text-slate-900">{{ $t->label }}</p>
                    <p class="text-xs text-slate-500">{{ $t->tanggal->translatedFormat('j M Y') }}{{ $t->label_metode ? ' · '.$t->label_metode : '' }}{{ $t->keterangan ? ' · '.$t->keterangan : '' }}</p>
                </div>
                <span @class(['font-medium tabular-nums', 'text-slate-900' => $t->arah === 'masuk', 'text-slate-500' => $t->arah === 'keluar'])>{{ $t->arah === 'keluar' ? '−' : '+' }}{{ rupiah($t->jumlah, false) }}</span>
            </div>
        @empty
            <p class="px-5 py-10 text-center text-sm text-slate-500">Belum ada transaksi.</p>
        @endforelse
    </div>

    <p class="mt-6 text-center text-xs text-slate-400">Ada angka yang tidak sesuai? Hubungi bendahara koperasi.</p>
@endsection

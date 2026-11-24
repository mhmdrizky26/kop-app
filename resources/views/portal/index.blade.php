@extends('layouts.portal')

@section('judul', 'Buku Tabungan Saya')

@section('isi')
    <div class="flex items-center gap-4">
        <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-blue-600 text-base font-semibold text-white">{{ $anggota->inisial }}</span>
        <div class="min-w-0">
            <h1 class="text-lg font-semibold tracking-tight break-words text-slate-900 sm:text-xl">Halo, {{ $anggota->nama }}</h1>
            <p class="text-sm text-slate-500">ID anggota {{ $anggota->kode }} · Tahun buku {{ $tahun->nama }}</p>
        </div>
    </div>

    <div class="mt-6">
        @include('partials.saldo-kartu', ['saldo' => $buku['akhir'], 'denganPinjaman' => false])
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Tagihan bulan ini --}}
        <div class="card flex flex-col p-5">
            @php
                $status = $sisa['status'];
                $posTagihan = ['pokok' => 'Simpanan pokok', 'wajib' => 'Simpanan wajib', 'manasuka' => 'Simpanan manasuka', 'angsuran' => 'Angsuran pinjaman', 'jasa' => 'Jasa pinjaman'];
                $dibayar = $bayar['total'] ?? 0;
            @endphp
            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
                <h2 class="card-title">Tagihan {{ $periode->translatedFormat('F Y') }}</h2>
                @if ($status === 'lunas')
                    <span class="badge badge-green"><x-icon name="check" class="size-3" /> Lunas</span>
                @elseif ($status === 'sebagian')
                    <span class="badge badge-amber">Dibayar sebagian</span>
                @else
                    <span class="badge badge-amber">Belum dibayar</span>
                @endif
            </div>
            <dl class="mt-4 space-y-3 text-sm">
                @foreach ($posTagihan as $pos => $label)
                    @php $nilai = max($tagihan[$pos], $bayar[$pos] ?? 0); @endphp
                    @continue($nilai === 0)
                    @php
                        $rincian = $bayar['rincian'][$pos] ?? null;
                        $lunasPos = $sisa[$pos] === 0;
                    @endphp
                    <div class="flex justify-between gap-3">
                        <dt>
                            <span class="text-slate-500">{{ $label }}</span>
                            @if ($lunasPos && $rincian)
                                <span class="flex items-center gap-1 text-xs text-emerald-700"><x-icon name="check" class="size-3" /> {{ collect($rincian['metode'])->map(fn ($m) => \App\Models\Transaksi::METODE[$m] ?? $m)->join(' + ') }} · {{ $rincian['tanggal']->translatedFormat('j M') }}</span>
                            @else
                                <span class="block text-xs text-amber-700">Belum · {{ \App\Models\Transaksi::METODE[$anggota->caraBayar($pos)] ?? 'Setor langsung' }}</span>
                            @endif
                        </dt>
                        <dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($nilai) }}</dd>
                    </div>
                @endforeach
                <div class="flex justify-between border-t border-slate-100 pt-2.5 font-semibold text-slate-900"><dt>Total</dt><dd class="tabular-nums">{{ rupiah(max($tagihan['total'], $dibayar)) }}</dd></div>
            </dl>

            {{-- Ringkasan pembayaran, menempel di bawah supaya kartu terisi penuh saat sejajar dengan kartu pinjaman --}}
            <div class="mt-auto pt-5">
                <dl class="space-y-2.5 rounded-lg bg-slate-50 p-4 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Sudah dibayar</dt><dd class="font-medium text-emerald-700 tabular-nums">{{ rupiah($dibayar) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Belum dibayar</dt><dd @class(['font-medium tabular-nums', 'text-amber-700' => $sisa['total'] > 0, 'text-slate-900' => $sisa['total'] === 0])>{{ rupiah($sisa['total']) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Cara bayar</dt><dd class="font-medium text-slate-900">{{ $anggota->label_cara_bayar }}</dd></div>
                </dl>
            </div>
        </div>

        {{-- Pinjaman aktif --}}
        <div class="space-y-6">
            @forelse ($pinjaman->where('status', 'aktif') as $p)
                <div class="card p-5">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
                        <h2 class="card-title">Pinjaman {{ $p->tanggal->translatedFormat('F Y') }}</h2>
                        <span class="badge badge-blue">{{ $p->persen_lunas }}% terbayar</span>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Sisa pinjaman</p>
                    <p class="text-2xl font-semibold tracking-tight text-slate-900 tabular-nums">{{ rupiah($p->sisa) }}</p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-600" style="width: {{ $p->persen_lunas }}%"></div></div>
                    <dl class="mt-4 space-y-2.5 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Tanggal pinjam</dt><dd class="font-medium text-slate-900">{{ $p->tanggal->translatedFormat('j F Y') }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Pokok pinjaman</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($p->pokok) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Sudah dibayar</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($p->terbayar) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Lama pinjaman</dt><dd class="font-medium text-slate-900">{{ $p->tenor }} bulan</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Jasa</dt><dd class="font-medium text-slate-900">{{ rtrim(rtrim(number_format($p->jasa_persen, 2, ',', '.'), '0'), ',') }}% per bulan, {{ $p->jasa_metode === 'menurun' ? 'menurun' : 'tetap' }}</dd></div>
                        <div class="flex justify-between border-t border-slate-100 pt-2.5"><dt class="text-slate-500">Angsuran pokok / bulan</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($p->tagihan_angsuran) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Jasa / bulan</dt><dd class="font-medium text-slate-900 tabular-nums">{{ rupiah($p->tagihan_jasa) }}</dd></div>
                        <div class="flex justify-between font-semibold text-slate-900"><dt>Total / bulan</dt><dd class="tabular-nums">{{ rupiah($p->tagihan_angsuran + $p->tagihan_jasa) }}</dd></div>
                    </dl>
                </div>
            @empty
                <div class="card flex h-full flex-col items-center justify-center p-5 text-center">
                    <h2 class="card-title">Pinjaman</h2>
                    <p class="mt-2 text-sm text-slate-500">Tidak ada pinjaman aktif.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-6">
        @include('partials.buku-tabungan')
    </div>

    <div class="card mt-6">
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

    <p class="mt-6 text-center text-xs text-slate-400">Ada angka yang tidak sesuai? Hubungi bendahara koperasi.</p>
@endsection

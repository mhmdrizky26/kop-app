{{-- Tombol cetak dan export untuk halaman laporan. Butuh $rute; filter di alamat (misalnya ?bulan=) ikut terbawa. --}}
@php $tautan = fn (string $format) => route($rute, array_merge(request()->except('format'), ['format' => $format])); @endphp
<a href="{{ $tautan('cetak') }}" target="_blank" rel="noopener" class="btn btn-ghost">Cetak</a>
<a href="{{ $tautan('pdf') }}" class="btn btn-ghost">PDF</a>
<a href="{{ $tautan('xlsx') }}" class="btn btn-ghost">Excel</a>

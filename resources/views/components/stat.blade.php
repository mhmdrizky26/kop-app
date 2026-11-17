@props(['label', 'nilai', 'ikon' => null, 'catatan' => null, 'utama' => false])
<div @class(['min-w-0 rounded-2xl border p-4 sm:p-5', 'border-blue-600 bg-blue-600 text-white' => $utama, 'border-slate-200 bg-white' => ! $utama])>
    <div class="flex items-center justify-between gap-3">
        <p @class(['text-sm', 'text-blue-100' => $utama, 'text-slate-500' => ! $utama])>{{ $label }}</p>
        @if ($ikon)
            <span @class(['flex size-8 items-center justify-center rounded-lg', 'bg-white/15 text-white' => $utama, 'bg-blue-50 text-blue-600' => ! $utama])>
                <x-icon :name="$ikon" class="size-4" />
            </span>
        @endif
    </div>
    <p @class(['mt-3 text-xl font-semibold tracking-tight break-words tabular-nums sm:text-2xl', 'text-slate-900' => ! $utama]) title="{{ $attributes->get('title') }}">{{ $nilai }}</p>
    @if ($catatan)
        <p @class(['mt-1 text-xs', 'text-blue-100' => $utama, 'text-slate-500' => ! $utama])>{{ $catatan }}</p>
    @endif
</div>

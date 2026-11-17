{{-- Logo aplikasi: gedung koperasi di dalam kotak membulat. Varian "terang" untuk latar biru. --}}
@props(['terang' => false])
<span {{ $attributes->class([
    'flex size-9 shrink-0 items-center justify-center rounded-xl shadow-sm',
    'bg-linear-to-br from-blue-500 to-blue-700 text-white shadow-blue-600/30' => ! $terang,
    'bg-white text-blue-600' => $terang,
]) }}>
    <svg class="size-[60%]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 3 3.5 8.5h17z" fill="currentColor" fill-opacity=".25" />
        <path d="M6 11.5v6M10 11.5v6M14 11.5v6M18 11.5v6M3 21h18M4.5 11.5h15" />
    </svg>
</span>

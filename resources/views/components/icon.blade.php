@props(['name'])
@php
    $ikon = [
        'home' => 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z',
        'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
        'user' => 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
        'clipboard' => 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2zM9 14l2 2 4-4',
        'arrows' => 'M7 7h13M16 3l4 4-4 4M17 17H4M8 13l-4 4 4 4',
        'banknote' => 'M3 6h18v12H3zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM6.5 9.5v.01M17.5 14.5v.01',
        'wallet' => 'M3 7a2 2 0 0 1 2-2h13v4M3 7v10a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-8a1 1 0 0 0-1-1H5a2 2 0 0 1-2-2zM17 14h.01',
        'chart' => 'M4 20V10M10 20V4M16 20v-7M22 20H2',
        'pie' => 'M12 3v9h9M21 12a9 9 0 1 1-9-9',
        'settings' => 'M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0M16 4v4M10 10v4M18 16v4',
        'logout' => 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9',
        'plus' => 'M12 5v14M5 12h14',
        'check' => 'M5 12.5l4.5 4.5L19 7.5',
        'search' => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.3-4.3',
        'menu' => 'M4 6h16M4 12h16M4 18h16',
        'x' => 'M6 6l12 12M18 6 6 18',
        'book' => 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5zM4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5',
        'lock' => 'M6 11h12v10H6zM8 11V7a4 4 0 0 1 8 0v4',
        'right' => 'M9 6l6 6-6 6',
        'left' => 'M15 6l-6 6 6 6',
        'down' => 'M6 9l6 6 6-6',
        'trash' => 'M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13',
        'edit' => 'M4 20h4L19 9l-4-4L4 16z',
        'up-right' => 'M7 17 17 7M8 7h9v9',
        'down-left' => 'M17 7 7 17M16 17H7V8',
        'info' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 11v5M12 8v.01',
        'box' => 'M21 8 12 3 3 8v8l9 5 9-5zM3 8l9 5 9-5M12 13v8',
        'scale' => 'M12 3v18M7 21h10M5 7h14M5 7l-3 7a3 3 0 0 0 6 0zM19 7l-3 7a3 3 0 0 0 6 0z',
    ];
@endphp
<svg {{ $attributes->merge(['class' => 'size-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="{{ $ikon[$name] ?? '' }}" />
</svg>

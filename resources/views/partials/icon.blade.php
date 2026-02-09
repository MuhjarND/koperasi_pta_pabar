@php
    $name = $name ?? 'circle';
@endphp

<span class="icon" aria-hidden="true">
    @if($name === 'dashboard')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
            <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
            <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
            <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
        </svg>
    @elseif($name === 'wallet')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="6" width="18" height="13" rx="2"></rect>
            <path d="M16 10h5v5h-5"></path>
            <circle cx="16.5" cy="12.5" r="1"></circle>
            <path d="M3 9h18"></path>
        </svg>
    @elseif($name === 'coins')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <ellipse cx="12" cy="6" rx="6" ry="3"></ellipse>
            <path d="M6 6v6c0 1.7 2.7 3 6 3s6-1.3 6-3V6"></path>
            <path d="M6 12c0 1.7 2.7 3 6 3s6-1.3 6-3"></path>
        </svg>
    @elseif($name === 'file')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"></path>
            <path d="M14 3v5h5"></path>
            <path d="M8 13h8"></path>
            <path d="M8 17h5"></path>
        </svg>
    @elseif($name === 'plus')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 5v14"></path>
            <path d="M5 12h14"></path>
        </svg>
    @elseif($name === 'clipboard')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 3h6l1 2h3v16H5V5h3z"></path>
            <path d="M9 3v4h6V3"></path>
            <path d="M8 11h8"></path>
            <path d="M8 15h6"></path>
        </svg>
    @elseif($name === 'check')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M8 12l2.5 2.5L16 9"></path>
        </svg>
    @elseif($name === 'star')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3l2.6 5.3 5.9.9-4.3 4.2 1 6-5.2-2.7-5.2 2.7 1-6L3.5 9.2l5.9-.9z"></path>
        </svg>
    @elseif($name === 'box')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 7l9-4 9 4-9 4-9-4z"></path>
            <path d="M3 7v10l9 4 9-4V7"></path>
            <path d="M12 11v10"></path>
        </svg>
    @elseif($name === 'cart')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="20" r="1.5"></circle>
            <circle cx="18" cy="20" r="1.5"></circle>
            <path d="M4 4h2l2.5 11h10l2-7H7"></path>
        </svg>
    @elseif($name === 'users')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="8" cy="8" r="3"></circle>
            <circle cx="16" cy="8" r="3"></circle>
            <path d="M3 19c0-2.2 2.2-4 5-4"></path>
            <path d="M21 19c0-2.2-2.2-4-5-4"></path>
        </svg>
    @elseif($name === 'user')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="8" r="4"></circle>
            <path d="M4 20c0-3.3 3.6-6 8-6s8 2.7 8 6"></path>
        </svg>
    @elseif($name === 'history')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 12a9 9 0 1 0 3-6.7"></path>
            <path d="M3 3v6h6"></path>
            <path d="M12 7v5l3 2"></path>
        </svg>
    @elseif($name === 'barcode')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 6v12"></path>
            <path d="M7 6v12"></path>
            <path d="M10 6v12"></path>
            <path d="M13 6v12"></path>
            <path d="M16 6v12"></path>
            <path d="M19 6v12"></path>
        </svg>
    @elseif($name === 'qr')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
            <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
            <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
            <path d="M14 14h3v3h-3z"></path>
            <path d="M20 14v2"></path>
            <path d="M14 20h2"></path>
            <path d="M18 18h3v3h-3z"></path>
        </svg>
    @elseif($name === 'chart')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 19h18"></path>
            <path d="M7 16V9"></path>
            <path d="M12 16V5"></path>
            <path d="M17 16v-3"></path>
        </svg>
    @elseif($name === 'eye')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        </svg>
    @elseif($name === 'edit')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 20h9"></path>
            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"></path>
        </svg>
    @elseif($name === 'trash')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18"></path>
            <path d="M8 6V4h8v2"></path>
            <path d="M6 6l1 14h10l1-14"></path>
        </svg>
    @else
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="5"></circle>
        </svg>
    @endif
</span>

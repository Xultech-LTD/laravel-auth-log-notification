@php
    $user = auth()->user();
    $isSuspicious = $user?->lastLoginWasSuspicious();
@endphp

@if ($isSuspicious)
    <div class="bg-yellow-100 text-yellow-800 text-sm p-2 rounded">
        ⚠️ Your last login was flagged as suspicious (new device or location).
    </div>
@endif

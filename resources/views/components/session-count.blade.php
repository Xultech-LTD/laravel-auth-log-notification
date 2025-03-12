@php
    $user = auth()->user();
    $count = $user?->activeSessions()?->count() ?? 0;
@endphp

<span>
    Active Sessions: <strong>{{ $count }}</strong>
</span>

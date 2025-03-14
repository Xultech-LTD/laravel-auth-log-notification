@if ($lastLogin)
    <span title="{{ $lastLogin->toDateTimeString() }}">
        Last login: {{ $lastLogin->diffForHumans() }}
    </span>
@else
    <span>No login history yet.</span>
@endif

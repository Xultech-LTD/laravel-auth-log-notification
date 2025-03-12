@php
    $user = auth()->user();
    $logins = $user?->logins($count ?? 5);
@endphp

@if ($logins && $logins->count())
    <table class="w-full text-sm border">
        <thead class="bg-gray-100 text-left">
        <tr>
            <th class="p-2">Time</th>
            <th class="p-2">Location</th>
            <th class="p-2">Device</th>
            <th class="p-2">IP</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($logins as $log)
            <tr class="border-t">
                <td class="p-2">{{ $log->login_at?->diffForHumans() }}</td>
                <td class="p-2">{{ $log->formatted_location }}</td>
                <td class="p-2">{{ $log->device_summary }}</td>
                <td class="p-2">{{ $log->ip_address }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <p class="text-sm text-gray-500">No recent login history.</p>
@endif

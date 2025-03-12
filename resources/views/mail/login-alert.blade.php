{{-- Package: Xultech Auth Log Notification --}}
{{-- View: Login Alert Notification Email --}}

@component('mail::message')
    # New Login Detected

    Hi {{ $user->name ?? 'there' }},

    A new login to your account was detected:

    - **IP Address:** {{ $log->ip_address }}
    - **Location:** {{ $log->formatted_location }}
    - **Device:** {{ $log->device_summary }}
    - **Time:** {{ $log->login_at_formatted }}

    If this was you, no further action is needed.

    If you suspect this was not you, please change your password immediately or contact support.

    Thanks,
    {{ config('app.name') }}
@endcomponent

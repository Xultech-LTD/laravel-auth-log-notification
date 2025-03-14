<?php

namespace Xultech\AuthLogNotification\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class SuspiciousAlert extends Component
{
    public function __construct(public string $guard = 'web') {}

    public function render(): View
    {
        $user = Auth::guard($this->guard)->user();

        return $this->view('authlog::components.suspicious-alert', [
            'user' => $user,
            'isSuspicious' => $user?->lastLoginWasSuspicious(),
        ]);
    }
}

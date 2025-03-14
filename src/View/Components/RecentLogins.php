<?php

namespace Xultech\AuthLogNotification\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class RecentLogins extends Component
{
    public function __construct(
        public int $count = 5,
        public string $guard = 'web'
    ) {}

    public function render(): View
    {
        $user = Auth::guard($this->guard)->user();

        return $this->view('authlog::components.recent-logins', [
            'user' => $user,
            'logins' => $user?->logins($this->count),
        ]);
    }
}

<?php

namespace Xultech\AuthLogNotification\Events;

class ReAuthenticated
{
    /**
     * The user that was re-authenticated.
     *
     * @var mixed
     */
    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }
}
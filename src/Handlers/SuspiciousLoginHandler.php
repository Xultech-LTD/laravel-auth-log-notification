<?php

namespace Xultech\AuthLogNotification\Handlers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SuspiciousLoginHandler
{
    /**
     * Handle the blocked login attempt.
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        return new Response(
            json_encode(['message' => 'Login blocked due to suspicious activity.']),
            Response::HTTP_FORBIDDEN,
            ['Content-Type' => 'application/json']
        );
    }
}
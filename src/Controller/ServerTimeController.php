<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Empty response used by the game timer to resync after a sleep or a clock
 * jump: the server send time is stamped by ServerTimeHeaderListener.
 */
final class ServerTimeController
{
    #[Route('/server-time', name: 'app_server_time', methods: ['GET'])]
    public function __invoke(): Response
    {
        $response = new Response(status: Response::HTTP_NO_CONTENT);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}

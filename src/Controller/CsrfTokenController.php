<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Called periodically by the csrf-refresh Stimulus controller on pages left
 * open for a long time: reading the session keeps it (and the CSRF tokens it
 * stores) alive, and the returned tokens replace the ones rendered in the page,
 * which are cleared when the user logs in again in another tab.
 */
final class CsrfTokenController
{
    private const int MAX_TOKENS = 20;

    /**
     * @param list<string> $ids
     */
    #[Route('/csrf-tokens', name: 'app_csrf_tokens', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED')]
    public function __invoke(
        CsrfTokenManagerInterface $csrfTokenManager,
        #[MapQueryParameter(
            filter: \FILTER_VALIDATE_REGEXP,
            options: ['regexp' => '/^[a-z0-9-]{1,64}$/'],
            validationFailedStatusCode: Response::HTTP_BAD_REQUEST,
        )]
        array $ids = [],
    ): JsonResponse {
        if (count($ids) > self::MAX_TOKENS) {
            return new JsonResponse(status: Response::HTTP_BAD_REQUEST);
        }

        $tokens = [];
        foreach (array_unique($ids) as $id) {
            $tokens[$id] = $csrfTokenManager->getToken($id)->getValue();
        }

        $response = new JsonResponse($tokens);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use SensitiveParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Rejects a mutation whose CSRF token does not match, with a 400 response rather than
 * the logout that Symfony's #[IsCsrfTokenValid] would trigger behind the firewall.
 */
final readonly class CsrfGuard
{
    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    public function assertValid(string $id, #[SensitiveParameter] ?string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($id, $token))) {
            throw new BadRequestHttpException('Token invalid');
        }
    }
}

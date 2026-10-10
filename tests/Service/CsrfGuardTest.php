<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\CsrfGuard;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfGuardTest extends TestCase
{
    public function testValidTokenPasses(): void
    {
        $manager = $this->createMock(CsrfTokenManagerInterface::class);
        $manager->expects(self::once())
            ->method('isTokenValid')
            ->with(new CsrfToken('delete-game', 'abc'))
            ->willReturn(true);

        new CsrfGuard($manager)->assertValid('delete-game', 'abc');
    }

    public function testInvalidTokenIsABadRequest(): void
    {
        $manager = $this->createStub(CsrfTokenManagerInterface::class);
        $manager->method('isTokenValid')->willReturn(false);

        $this->expectException(BadRequestHttpException::class);

        new CsrfGuard($manager)->assertValid('delete-game', 'wrong');
    }

    public function testMissingTokenIsABadRequest(): void
    {
        $manager = $this->createStub(CsrfTokenManagerInterface::class);
        $manager->method('isTokenValid')->willReturn(false);

        $this->expectException(BadRequestHttpException::class);

        new CsrfGuard($manager)->assertValid('delete-game', null);
    }
}

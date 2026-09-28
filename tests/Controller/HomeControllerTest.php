<?php

declare(strict_types=1);

namespace App\Tests\Controller;

class HomeControllerTest extends ControllerTestCase
{
    public function testHomePageIsPublic(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }
}

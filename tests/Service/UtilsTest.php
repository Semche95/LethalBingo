<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    #[DataProvider('num2alphaProvider')]
    public function testNum2alpha(int $input, string $expected): void
    {
        $this->assertSame($expected, Utils::num2alpha($input));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function num2alphaProvider(): array
    {
        return [
            '0 is A' => [0, 'A'],
            '1 is B' => [1, 'B'],
            '25 is Z' => [25, 'Z'],
            '26 is AA' => [26, 'AA'],
            '27 is AB' => [27, 'AB'],
            '51 is AZ' => [51, 'AZ'],
            '52 is BA' => [52, 'BA'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

class Utils
{
    public static function num2alpha(int $n): string
    {
        for ($r = ''; $n >= 0; $n = intval($n / 26) - 1) {
            $r = chr($n % 26 + 0x41) . $r;
        }
        return $r;
    }
}

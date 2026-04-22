<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class NavbarItem
{
    public string $label;
    public string $tooltip = '';
    public string $route;
    public array $routeParams = [];
    public string $icon = '';
}

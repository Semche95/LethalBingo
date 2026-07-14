<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class DataTable
{
    use DefaultActionTrait;

    public string $table_classes = 'table card-table table-vcenter table-hover text-nowrap datatable';

    /**
     * @var list<string> $columns
     */
    public array $columns = [];

    #[LiveProp(useSerializerForHydration: true)]
    public array $data = [];

    public array $options = [];

    public bool $show_header = true;
}

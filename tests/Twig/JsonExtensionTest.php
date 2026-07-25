<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Twig\JsonExtension;
use PHPUnit\Framework\TestCase;

class JsonExtensionTest extends TestCase
{
    private JsonExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new JsonExtension();
    }

    public function testGetFiltersReturnsOneFilter(): void
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertSame('json_decode', $filters[0]->getName());
    }

    public function testJsonDecodeFilterDecodesValidJson(): void
    {
        $filters = $this->extension->getFilters();
        $filter = $filters[0];
        $callable = $filter->getCallable();

        $result = $callable('{"key": "value", "number": 42}');

        $this->assertSame(['key' => 'value', 'number' => 42], $result);
    }

    public function testJsonDecodeFilterDecodesArray(): void
    {
        $filters = $this->extension->getFilters();
        $callable = $filters[0]->getCallable();

        $result = $callable('[1, 2, 3]');

        $this->assertSame([1, 2, 3], $result);
    }

    public function testJsonDecodeFilterDecodesNestedObjects(): void
    {
        $filters = $this->extension->getFilters();
        $callable = $filters[0]->getCallable();

        $result = $callable('{"a": {"b": "c"}}');

        $this->assertSame(['a' => ['b' => 'c']], $result);
    }

    public function testJsonDecodeFilterThrowsOnInvalidJson(): void
    {
        $filters = $this->extension->getFilters();
        $callable = $filters[0]->getCallable();

        $this->expectException(\JsonException::class);

        $callable('not valid json');
    }
}

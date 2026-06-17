<?php

namespace Tests\Unit;

use App\Services\ProductMatchingService;
use PHPUnit\Framework\TestCase;

class IdentifyCompanyProductsJobTest extends TestCase
{
    public function test_find_item_by_name(): void
    {
        $service = new class extends ProductMatchingService
        {
            public function __construct() {}

            public function publicFindItemByName(array $items, string $name)
            {
                return $this->findItemByName($items, $name);
            }
        };

        $items = [
            ['name' => 'Товар 1', 'price' => 100],
            ['name' => 'Товар 2', 'price' => 200],
            ['name' => 'Продукт 3', 'price' => 300],
        ];

        $result = $service->publicFindItemByName($items, 'Товар 2');

        $this->assertNotNull($result);
        $this->assertEquals('Товар 2', $result['item']['name']);
        $this->assertCount(2, $result['remaining_items']);
    }
}

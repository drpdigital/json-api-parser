<?php

namespace Tests\Unit;

use Drp\JsonApiParser\Collection;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    /** @test */
    public function it_can_get_keys()
    {
        $collection = new Collection();

        $collection->add('test', 'test');
        $collection->add('test2', 'test');

        $this->assertEquals(['test', 'test2'], $collection->keys());
    }

    /** @test */
    public function can_get_by_array_access()
    {
        $collection = new Collection();
        $collection->add('test', 'value');

        $this->assertEquals('value', $collection['test']);
    }

    /** @test */
    public function can_unset_value()
    {
        $collection = new Collection();
        $collection->add('test', 'value');
        unset($collection['test']);

        $this->assertFalse($collection->has('test'));
    }
}

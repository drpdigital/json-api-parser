<?php

namespace Tests\Unit;

use Drp\JsonApiParser\Arr;
use PHPUnit\Framework\TestCase;

class ArrTest extends TestCase
{
    /** @test */
    public function it_can_get_item_from_array()
    {
        $array = ['test' => 'value'];
        $this->assertEquals('value', Arr::get($array, 'test'));
        $this->assertEquals(null, Arr::get($array, 'not-a-key'));
        $this->assertEquals('DEFAULT', Arr::get($array, 'not-a-key', 'DEFAULT'));
    }

    /** @test */
    public function it_can_check_for_associative_array()
    {
        $nonAssoc = [1,2,3];
        $assoc = ['test' => 1, 'test2' => 2];
        $mixedAssoc = [1, 'test' => 1];

        $this->assertFalse(Arr::is_assoc($nonAssoc));
        $this->assertTrue(Arr::is_assoc($assoc));
        $this->assertTrue(Arr::is_assoc($mixedAssoc));
    }

    /** @test */
    public function it_can_flatten_array()
    {
        $array = ['test', ['foo' => 1], ['bar' => 2]];
        $this->assertEquals([
            'test',
            1,
            2,
        ], Arr::flatten($array));
    }

    /** @test */
    public function it_can_wrap_values()
    {
        $this->assertEquals(['test'], Arr::wrap('test'));
        $this->assertEquals([null], Arr::wrap(null));
        $this->assertEquals([], Arr::wrap([]));
        $this->assertEquals([1], Arr::wrap(1));
    }

    /** @test */
    public function it_can_collapse_arrays()
    {
        $array = ['test', ['foo' => 1], ['bar' => 2]];
        $this->assertEquals([
            'test',
            'foo' => 1,
            'bar' => 2,
        ], Arr::collapse($array));
    }
}

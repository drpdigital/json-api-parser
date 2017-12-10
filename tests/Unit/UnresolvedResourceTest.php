<?php

namespace Tests\Unit;

use Drp\JsonApiParser\UnresolvedResource;
use PHPUnit\Framework\TestCase;

class UnresolvedResourceTest extends TestCase
{
    public function testGetType()
    {
        $resource = new UnresolvedResource('test', ['test' => 1]);

        $this->assertEquals('test', $resource->getType());
    }

    public function testGetData()
    {
        $resource = new UnresolvedResource('test', ['test' => 1]);

        $this->assertEquals(['test' => 1], $resource->getData());
    }
}

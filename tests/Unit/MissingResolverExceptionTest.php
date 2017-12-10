<?php

namespace Tests\Unit;

use Drp\JsonApiParser\Exceptions\MissingResolverException;
use Drp\JsonApiParser\UnresolvedResource;
use PHPUnit\Framework\TestCase;

class MissingResolverExceptionTest extends TestCase
{
    public function testGetResolverType()
    {
        $exception = new MissingResolverException(new UnresolvedResource('test', ['test' => 1]));

        $this->assertEquals('test', $exception->getResolverType());
    }

    public function testGetResource()
    {
        $resource = new UnresolvedResource('test', ['test' => 1]);
        $exception = new MissingResolverException($resource);

        $this->assertEquals($resource, $exception->getResource());
    }
}

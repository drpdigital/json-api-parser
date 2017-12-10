<?php

namespace Tests\Unit;

use Drp\JsonApiParser\ResolvedCollection;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeModel1;

class ResolvedCollectionTest extends TestCase
{
    /** @test */
    public function can_get_by_classname()
    {
        $collection = new ResolvedCollection();
        $resolved = new FakeModel1([]);
        $collection->add('fake', $resolved);

        $this->assertEquals($resolved, $collection->getByClass(FakeModel1::class));
    }

    /** @test */
    public function can_get_by_type()
    {
        $collection = new ResolvedCollection();
        $resolved = new FakeModel1([]);
        $collection->add('fake', $resolved);

        $this->assertEquals($resolved, $collection->get('fake'));
    }

    /** @test */
    public function will_return_collection_if_multiple_resolved()
    {
        $collection = new ResolvedCollection();
        $resolved = new FakeModel1([]);
        $resolved2 = new FakeModel1([]);
        $collection->add('fake', $resolved);
        $collection->add('fake', $resolved2);

        $this->assertCount(2, $collection->getByClass(FakeModel1::class));
        $this->assertEquals($resolved, $collection->getByClass(FakeModel1::class)[0]);
        $this->assertEquals($resolved2, $collection->getByClass(FakeModel1::class)[1]);
    }

    /** @test */
    public function will_return_collection_on_get_by_type_if_multiple_resolved()
    {
        $collection = new ResolvedCollection();
        $resolved = new FakeModel1([]);
        $resolved2 = new FakeModel1([]);
        $collection->add('fake', $resolved);
        $collection->add('fake', $resolved2);

        $this->assertCount(2, $collection->get('fake'));
        $this->assertEquals($resolved, $collection->get('fake')[0]);
        $this->assertEquals($resolved2, $collection->get('fake')[1]);
    }
}

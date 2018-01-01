<?php

namespace Tests\Unit;

use Drp\JsonApiParser\Exceptions\MissingResolverException;
use Drp\JsonApiParser\ResourceResolver;
use Drp\JsonApiParser\UnresolvedResource;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeContainer;
use Tests\Fakes\FakeDependency;
use Tests\Fakes\FakeModel1;
use Tests\Fakes\FakeModel2;

class ResourceResolverTest extends TestCase
{
    /** @test */
    public function can_resolve_static_callable()
    {
        $resolver = new ResourceResolver(new FakeContainer());

        $resolve = new FakeChildDependency();

        $resolver->bind('fake', get_class($resolve) . '::handle');

        $resolver->resolve('fake', [
            'id' => 1,
            'type' => 'fake',
            'attributes' => [
                'test' => 1,
            ]
        ]);

        $this->assertTrue($resolve::$called);
    }

    /** @test */
    public function can_remove_resolver()
    {
        $resolver = new ResourceResolver(new FakeContainer());

        $resolver->bind('test', function () {});
        $resolver->remove('test');

        try {
            $resolver->resolve('test', []);
        } catch (MissingResolverException $exception) {
            $this->assertEquals('test', $exception->getResolverType());

            return true;
        }

        $this->fail('Did not remove the resolver');
    }

    /** @test */
    public function can_called_resolver_with_over_5_dependencies()
    {
        $called = false;
        $resolver = new ResourceResolver(new FakeContainer());
        $resolver->bind(
            'test',
            function (
                FakeDependency $one,
                FakeDependency $two,
                FakeDependency $three,
                $data,
                $id
            ) use (&$called) { $called = true; }
        );

        $resolver->resolve('test', [
            'id' => 1,
            'attributes' => [
                'test' => 1,
            ]
        ]);

        $this->assertTrue($called);
    }

    /** @test */
    public function can_find_parent_that_is_an_instanceof_parameter()
    {
        $resolver = new ResourceResolver(new FakeContainer());

        $parent = new FakeChildDependency();

        $resolver->bind('test', function (FakeDependency $dependency) {
            $this->assertTrue($dependency->isBuilt());
        });

        $resolver->resolve('test', [], [$parent]);
    }

    /** @test */
    public function will_just_give_null_if_cant_resolve_parameter()
    {
        $resolver = new ResourceResolver(new FakeContainer());

        $resolver->bind('test', function ($data, $id, FakeModel2 $unresolved = null) {
            $this->assertEquals(['test' => 1], $data);
            $this->assertEquals(1, $id);
            $this->assertNull($unresolved);
        });

        $resolver->resolve('test', [
            'id' => 1,
            'attributes' => [
                'test' => 1,
            ]
        ]);
    }

    /** @test */
    public function does_not_have_to_throw_missing_resolver()
    {
        $resolver = new ResourceResolver(new FakeContainer());
        $called = false;

        $resolver->onMissingResolver(function ($resource) use (&$called) {
            $this->assertInstanceOf(UnresolvedResource::class, $resource);
            $called = true;

            return false;
        });
        $resolver->resolve('test', []);

        $this->assertTrue($called);
    }

    /** @test */
    public function can_fetch_with_snake_case_parameter_name()
    {
        $resolver = new ResourceResolver(new FakeContainer());

        $resolver->bind('test', function (FakeModel1 $test_child) {
            return $test_child;
        });

        $resolver->bindFetcher(FakeModel1::class, function ($id) {
            $this->assertEquals(5, $id);

            return new FakeModel1(['id' => 5]);
        });

        $resolved = $resolver->resolve('test', [
            'id' => 5,
            'type' => 'test',
            'relationships' => [
                'test-relationship' => [
                    'data' => [
                        'id' => 5,
                        'type' => 'test-child'
                    ]
                ]
            ]
        ]);

        $this->assertInstanceOf(FakeModel1::class, $resolved);
    }

    /** @test */
    public function can_fetch_with_snake_case_relationship_type()
    {
        $resolver = new ResourceResolver(new FakeContainer());

        $resolver->bind('test', function (FakeModel1 $testChild) {
            return $testChild;
        });

        $resolver->bindFetcher(FakeModel1::class, function ($id) {
            $this->assertEquals(5, $id);

            return new FakeModel1(['id' => 5]);
        });

        $resolved = $resolver->resolve('test', [
            'id' => 5,
            'type' => 'test',
            'relationships' => [
                'test-relationship' => [
                    'data' => [
                        'id' => 5,
                        'type' => 'test_child'
                    ]
                ]
            ]
        ]);

        $this->assertInstanceOf(FakeModel1::class, $resolved);
    }

    /** @test */
    public function can_fetch_with_camel_case_relationship_type()
    {
        $resolver = new ResourceResolver(new FakeContainer());

        $resolver->bind('test', function (FakeModel1 $test_child) {
            return $test_child;
        });

        $resolver->bindFetcher(FakeModel1::class, function ($id) {
            $this->assertEquals(5, $id);

            return new FakeModel1(['id' => 5]);
        });

        $resolved = $resolver->resolve('test', [
            'id' => 5,
            'type' => 'test',
            'relationships' => [
                'test-relationship' => [
                    'data' => [
                        'id' => 5,
                        'type' => 'testChild'
                    ]
                ]
            ]
        ]);

        $this->assertInstanceOf(FakeModel1::class, $resolved);
    }

    /** @test */
    public function it_can_specify_relationship_name_so_parameter_can_be_anything()
    {
        $resolver = new ResourceResolver(new FakeContainer());

        $resolver->bind('test', function (FakeModel1 $random) {
            return $random;
        });

        $resolver->bindFetcher(FakeModel1::class, 'test.test-relationship.test-child', function ($id) {
            $this->assertEquals(5, $id);

            return new FakeModel1(['id' => 5]);
        });

        $resolved = $resolver->resolve('test', [
            'id' => 5,
            'type' => 'test',
            'relationships' => [
                'test-relationship' => [
                    'data' => [
                        'id' => 5,
                        'type' => 'test-child'
                    ]
                ]
            ]
        ]);

        $this->assertInstanceOf(FakeModel1::class, $resolved);
    }

    /** @test */
    public function must_specify_full_relationship_for_fetcher()
    {
        $resolver = new ResourceResolver(new FakeContainer());

        $resolver->bind('test', function (FakeModel1 $random = null) {
            return $random;
        });

        $resolver->bindFetcher(FakeModel1::class, 'test-relationship.test-child', function ($id) {
            $this->assertEquals(5, $id);

            return new FakeModel1(['id' => 5]);
        });

        $resolved = $resolver->resolve('test', [
            'id' => 5,
            'type' => 'test',
            'relationships' => [
                'test-relationship' => [
                    'data' => [
                        'id' => 5,
                        'type' => 'test-child'
                    ]
                ]
            ]
        ]);

        $this->assertNull($resolved);
    }

    /** @test */
    public function wont_resolve_if_the_current_resource_type_doesnt_match_specified()
    {
        $resolver = new ResourceResolver(new FakeContainer());

        $resolver->bind('test-bad', function (FakeModel1 $random = null) {
            return $random;
        });

        $resolver->bindFetcher(FakeModel1::class, 'test.test-relationship.test-child', function ($id) {
            $this->assertEquals(5, $id);

            return new FakeModel1(['id' => 5]);
        });

        $resolved = $resolver->resolve('test-bad', [
            'id' => 5,
            'type' => 'test-bad',
            'relationships' => [
                'test-relationship' => [
                    'data' => [
                        'id' => 5,
                        'type' => 'test-child'
                    ]
                ]
            ]
        ]);

        $this->assertNull($resolved);
    }

    /** @test */
    public function wont_fetch_if_relationship_name_doesnt_match()
    {
        $resolver = new ResourceResolver(new FakeContainer());

        $resolver->bind('test', function (FakeModel1 $random = null) {
            return $random;
        });

        $resolver->bindFetcher(FakeModel1::class, 'test.test-relationship.test-child', function ($id) {
            $this->assertEquals(5, $id);

            return new FakeModel1(['id' => 5]);
        });

        $resolved = $resolver->resolve('test', [
            'id' => 5,
            'type' => 'test',
            'relationships' => [
                'test-relationship-bad' => [
                    'data' => [
                        'id' => 5,
                        'type' => 'test-child'
                    ]
                ]
            ]
        ]);

        $this->assertNull($resolved);
    }

    /** @test */
    public function wont_fetch_resource_if_relationship_type_doesnt_match()
    {
        $resolver = new ResourceResolver(new FakeContainer());

        $resolver->bind('test', function (FakeModel1 $random = null) {
            return $random;
        });

        $resolver->bindFetcher(FakeModel1::class, 'test.test-relationship.test-child', function ($id) {
            $this->assertEquals(5, $id);

            return new FakeModel1(['id' => 5]);
        });

        $resolved = $resolver->resolve('test', [
            'id' => 5,
            'type' => 'test',
            'relationships' => [
                'test-relationship' => [
                    'data' => [
                        'id' => 5,
                        'type' => 'test-bad'
                    ]
                ]
            ]
        ]);

        $this->assertNull($resolved);
    }
}

class FakeChildDependency extends FakeDependency
{
    static $called = false;

    public static function handle()
    {
        self::$called = true;
    }
}

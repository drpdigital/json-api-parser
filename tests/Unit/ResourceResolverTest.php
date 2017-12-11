<?php

namespace Tests\Unit;

use Drp\JsonApiParser\Exceptions\MissingResolverException;
use Drp\JsonApiParser\ResourceResolver;
use Drp\JsonApiParser\UnresolvedResource;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeContainer;
use Tests\Fakes\FakeDependency;
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
}

class FakeChildDependency extends FakeDependency
{
    static $called = false;

    public static function handle()
    {
        self::$called = true;
    }
}

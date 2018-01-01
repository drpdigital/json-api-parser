<?php

namespace Tests\Unit;

use Drp\JsonApiParser\JsonApiParser;
use Drp\JsonApiParser\ResourceResolver;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeContainer;

class JsonApiParserTest extends TestCase
{
    /** @test */
    public function it_will_skip_over_badly_formed_relationship()
    {
        $parser = new JsonApiParser(new ResourceResolver(new FakeContainer()));
        $parser->resolver('test', function () {
            return 'GOOD';
        });

        $called = false;
        $parser->resolver('test-bad', function () use (&$called) {
            $called = true;
        });

        $parser->parse([
            'data' => [
                'id' => 1,
                'type' => 'test',
                'attributes' => [
                    'test' => 1,
                ],
                'relationships' => [
                    'id' => 1,
                    'type' => 'test-bad'
                ]
            ]
        ]);

        $parser->parse([
            'data' => [
                'id' => 1,
                'type' => 'test',
                'attributes' => [
                    'test' => 1,
                ],
                'relationships' => [
                    'relation' => [
                       'data' => 1,
                    ]
                ]
            ]
        ]);

        $parser->parse([
            'data' => [
                'id' => 1,
                'type' => 'test',
                'attributes' => [
                    'test' => 1,
                ],
                'relationships' => [
                    'relation' => [
                        'data' => [
                            'test' => 'test',
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertFalse($called);
    }

    /** @test */
    public function wont_resolve_child_relationship_if_parent_doesnt()
    {
        $resolver = new ResourceResolver(new FakeContainer());
        $resolver->onMissingResolver(function () {
            return false;
        });
        $parser = new JsonApiParser($resolver);

        $parser
            ->resolver('test-relation', function () {
                $this->fail('This resolver should not be called');
            })
            ->parse([
                'data' => [
                    'id' => 1,
                    'type' => 'test',
                    'attributes' => [
                        'test' => 1,
                    ],
                    'relationships' => [
                        'test-relationship' => [
                            'data' => [
                                'id' => 1,
                                'type' => 'test-relation'
                            ]
                        ]
                    ]
                ]
            ]);
    }
}

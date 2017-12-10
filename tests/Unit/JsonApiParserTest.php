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
        $parser->addResolver('test', function () {
            return 'GOOD';
        });

        $called = false;
        $parser->addResolver('test-bad', function () use (&$called) {
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
}

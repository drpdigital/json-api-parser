<?php

namespace Tests\Features;

use Drp\JsonApiParser\Exceptions\MissingResolverException;
use Drp\JsonApiParser\JsonApiParser;
use Drp\JsonApiParser\ResourceResolver;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeContainer;
use Tests\Fakes\FakeDependency;
use Tests\Fakes\FakeModel1;
use Tests\Fakes\FakeModel2;

class CanParseAndCreateObjectsTest extends TestCase
{
    /**
     * @var JsonApiParser
     */
    protected $parser;

    /**
     * JsonApiParserTest constructor.
     */
    public function setUp()
    {
        $this->parser = new JsonApiParser(new ResourceResolver(new FakeContainer()));
    }

    /** @test */
    public function canParseSimpleData()
    {
        $simple = [
            'data' => [
                'id' => 31,
                'type' => 'test',
                'attributes' => [
                    'example' => 'data',
                ],
            ],
        ];
        $called = false;
        $this->parser
            ->addResolver('test', function ($data, $id) use (&$called) {
                $this->assertEquals(31, $id);

                $this->assertEquals([
                    'example' => 'data',
                ], $data);

                $called = true;

                return [
                    'model'
                ];
            })
            ->parse($simple);

        $this->assertTrue($called, 'First resolver was not called.');
    }

    /** @test */
    public function canParseRelationships()
    {
        $simple = [
            'data' => [
                'id' => 31,
                'type' => 'test',
                'attributes' => [
                    'example' => 'data',
                ],
                'relationships' => [
                    'test2' => [
                        'data' => [
                            'type' => 'test2',
                            'id' => 32
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'id' => 32,
                    'type' => 'test2',
                    'attributes' => [
                        'example' => 'data2'
                    ]
                ]
            ]
        ];

        $called = false;
        $this->parser
            ->addResolver('test', function ($data, $id) {
                $this->assertEquals(31, $id);

                $this->assertEquals([
                    'example' => 'data',
                ], $data);

                return [
                    'model'
                ];
            })
            ->addResolver('test2.test2', function ($data, $id) use (&$called) {
                $this->assertEquals(32, $id);
                $this->assertEquals([
                    'example' => 'data2'
                ], $data);

                $called = true;

                return [
                    'model2'
                ];
            })
            ->parse($simple);

        $this->assertTrue($called, 'Second resolver was not called.');
    }

    /** @test */
    public function canParseMultilevelRelationships()
    {
        $simple = [
            'data' => [
                'id' => 31,
                'type' => 'test',
                'attributes' => ['example' => 'data'],
                'relationships' => [
                    'test2Relationship' => [
                        'data' => [
                            'type' => 'test2',
                            'id' => 32
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'id' => 32,
                    'type' => 'test2',
                    'attributes' => ['example' => 'data2'],
                    'relationships' => [
                        'test3Relationship' => [
                            'data' => [
                                'id' => 33,
                                'type' => 'test3'
                            ]
                        ]
                    ]
                ],
                [
                    'id' => 33,
                    'type' => 'test3',
                    'attributes' => ['example' => 'data3']
                ]
            ]
        ];

        $called = false;
        $this->parser
            ->addResolver('test', function ($data, $id) {
                $this->assertEquals(31, $id);

                $this->assertEquals([
                    'example' => 'data',
                ], $data);

                return [
                    'model'
                ];
            })
            ->addResolver('test2Relationship.test2', function ($data, $id) {
                $this->assertEquals(32, $id);
                $this->assertEquals([
                    'example' => 'data2'
                ], $data);

                return [
                    'model2'
                ];
            })
            ->addResolver('test3', function ($data, $id) use (&$called) {
                $this->assertEquals(33, $id);
                $this->assertEquals([
                    'example' => 'data3'
                ], $data);

                $called = true;

                return [
                    'model3'
                ];
            })
            ->parse($simple);

        $this->assertTrue($called, 'Third resolver was not called.');
    }

    /** @test */
    public function willInjectTheCorrectParentModels()
    {
        $simple = [
            'data' => [
                'id' => 31,
                'type' => 'fake1',
                'attributes' => ['firstName' => 'derek'],
                'relationships' => [
                    'children' => [
                        'data' => [
                            [
                                'type' => 'fake2',
                                'id' => 123
                            ],
                            [
                                'type' => 'fake2',
                                'id' => 456
                            ]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'id' => 123,
                    'type' => 'fake2',
                    'attributes' => ['other_id' => 123],
                    'relationships' => [
                        'parent' => [
                            'data' => [
                                'id' => 456,
                                'type' => 'fake2'
                            ]
                        ]
                    ]
                ],
                [
                    'id' => 456,
                    'type' => 'fake2',
                    'attributes' => ['example' => 'data3']
                ]
            ]
        ];

        $called = false;
        $resolved = $this->parser
            ->addResolver('fake1', function ($data, $id) {
                $fakeModel1 = new FakeModel1($data);
                $fakeModel1->id = $id;

                return $fakeModel1;
            })
            ->addResolver('fake1.children.fake2', function (FakeModel1 $fakeModel1, $data, $id) {
                $this->assertEquals(31, $fakeModel1->id);
                return new FakeModel2([
                    'other_id' => $id,
                    'parent_id' => $fakeModel1->id
                ]);
            })
            ->addResolver('parent.fake2', function (FakeModel1 $parent, FakeModel2 $child, $data, $parentId) use (&$called) {
                $this->assertEquals(31, $parent->id);
                $this->assertEquals(123, $child->other_id);
                $this->assertEquals(456, $parentId);
                $called = true;
            })
            ->parse($simple);

        $this->assertTrue($called, 'Third resolver was not called.');
        $this->assertInstanceOf(FakeModel1::class, $resolved->get('fake1'));
        $this->assertCount(2, $resolved->get('fake1.children.fake2'));
    }

    /** @test */
    public function willThrowWhenNoResolver()
    {
        $simple = [
            'data' => [
                'id' => 31,
                'type' => 'test',
                'attributes' => [
                    'example' => 'data',
                ],
            ],
        ];

        try {
            $this->parser->parse($simple);
        } catch (MissingResolverException $exception) {
            $this->assertEquals('test', $exception->getResolverType());

            return;
        }

        $this->fail('Found a resolver for a type.');
    }

    /** @test */
    public function willInjectContainerDependency()
    {
        $simple = [
            'data' => [
                'id' => 31,
                'type' => 'test',
                'attributes' => [
                    'example' => 'data',
                ],
            ],
        ];

        $called = false;
        $this->parser
            ->addResolver('test', function (FakeDependency $dependency) use (&$called) {
                $called = $dependency->isBuilt();
            })
            ->parse($simple);

        $this->assertTrue($called, 'Did not dependency inject');
    }
}

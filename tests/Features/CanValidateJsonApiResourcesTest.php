<?php

namespace Tests\Features;

use Drp\JsonApiParser\Exceptions\FailedValidationException;
use Drp\JsonApiParser\JsonApiValidator;
use Drp\JsonApiParser\ResourceResolver;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeContainer;
use Tests\Fakes\FakeFailingValidator;
use Tests\Fakes\FakePassingValidator;

class CanValidateJsonApiResourcesTest extends TestCase
{
    /**
     * @var JsonApiValidator
     */
    protected $validator;

    public function setUp()
    {
        $this->validator = new JsonApiValidator(new ResourceResolver(new FakeContainer()));
    }

    /** @test */
    public function canValidateMainResource()
    {
        $simple = [
            'data' => [
                'id' => 31,
                'type' => 'test',
                'attributes' => [
                    'test' => 'foo'
                ],
            ],
        ];

        $validator = new FakePassingValidator();

        $passed = $this->validator
            ->addValidator('test', $validator)
            ->validate($simple);

        $this->assertTrue($passed);
    }

    /**
     * @test
     */
    public function canValidateInvalidResource()
    {
        $simple = [
            'data' => [
                'id' => 31,
                'type' => 'test',
                'attributes' => [
                    'bar' => 'test'
                ],
            ],
        ];

        $validator = new FakeFailingValidator();

        try {
            $this->validator
                ->addValidator('test', $validator)
                ->validate($simple);
        } catch (FailedValidationException $exception) {
            $messages = $exception->getMessages();
            $this->assertCount(1, $messages);
            $this->assertEquals([
                'test_31' => [
                    'foo' => [
                        'bar'
                    ]
                ]
            ], $messages);

            return;
        }

        $this->fail('Validator did not throw an exception');
    }

    /**
     * @test
     */
    public function canValidateRelatedResource()
    {
        $simple = [
            'data' => [
                'id' => 31,
                'type' => 'simple',
                'relationships' => [
                    'address' => [
                        'data' => [
                            'id' => 3,
                            'type' => 'address'
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'id' => 3,
                    'type' => 'test',
                    'attributes' => [
                        'foo' => 'bar'
                    ],
                ]
            ]
        ];

        $validator = new FakePassingValidator();

        $passed = $this->validator
            ->addValidator('test', $validator)
            ->validate($simple);

        $this->assertTrue($passed);
    }

    /** @test */
    public function will_throw_when_resource_is_not_found()
    {
        $simple = [
            'data' => [
                'id' => 31,
                'type' => 'simple',
                'relationships' => [
                    'foo' => [
                        'data' => [
                            'type' => 'bar',
                            'id' => 12
                        ]
                    ]
                ]
            ]
        ];

        try {
            $this->validator
                ->addPresenceChecker('simple.address.test', function () {
                    return true;
                })
                ->validate($simple);
        } catch (FailedValidationException $exception) {
            $this->assertEquals([
                'simple.address.test' => 'Missing api resource from the request.'
            ], $exception->getMessages());

            return;
        }

        $this->fail('Validator should have thrown a FailedValidationException');
    }
}

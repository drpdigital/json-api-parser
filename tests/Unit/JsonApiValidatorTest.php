<?php

namespace Tests\Unit;

use Drp\JsonApiParser\Exceptions\FailedValidationException;
use Drp\JsonApiParser\Exceptions\InvalidJsonException;
use Drp\JsonApiParser\JsonApiValidator;
use Drp\JsonApiParser\ResourceResolver;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeContainer;
use Tests\Fakes\FakeFailingValidator;
use Tests\Fakes\FakePassingValidator;

class JsonApiValidatorTest extends TestCase
{
    /**
     * @var \Drp\JsonApiParser\JsonApiValidator
     */
    private $validator;

    protected function setUp()
    {
        parent::setUp();

        $this->validator = new JsonApiValidator(new ResourceResolver(new FakeContainer()));
    }

    /** @test */
    public function can_get_validator()
    {
        $resourceValidator = new FakePassingValidator();
        $this->validator->addValidator('test', $resourceValidator);

        $this->assertEquals($resourceValidator, $this->validator->getValidator('test'));
        $this->assertEquals($resourceValidator, $this->validator->test);
    }

    /** @test */
    public function can_get_all_validators()
    {
        $passingValidator = new FakePassingValidator();
        $failingValidator = new FakeFailingValidator();

        $this->validator->addValidator('pass', $passingValidator)
            ->addValidator('fail', $failingValidator);

        $validators = $this->validator->all();

        $this->assertCount(2, $validators);
        $this->assertEquals($passingValidator, $validators['pass']);
        $this->assertEquals($failingValidator, $validators['fail']);
    }

    /** @test */
    public function can_remove_validator()
    {
        $passingValidator = new FakePassingValidator();
        $failingValidator = new FakeFailingValidator();

        $this->validator->addValidator('pass', $passingValidator)
            ->addValidator('fail', $failingValidator);

        $this->validator->removeValidator(['pass']);
        $validators = $this->validator->all();

        $this->assertCount(1, $validators);
        $this->assertNull($this->validator->pass);
        $this->assertEquals($failingValidator, $validators['fail']);

        $this->validator->removeValidator('fail');
        $this->assertEmpty($this->validator->all());
    }

    /** @test */
    public function resource_checker_can_throw_an_error()
    {
        $this->validator->addPresenceChecker('test', function () {
            throw new FailedValidationException(['TEST']);
        });

        try {
            $this->validator->validate([
                'data' => [
                    'id' => 1,
                    'type' => 'test',
                    'attributes' => [
                        'test' => 1
                    ]
                ]
            ]);
        } catch (FailedValidationException $exception) {
            $this->assertArrayHasKey('test', $exception->getMessages());
            $this->assertEquals(['TEST'], $exception->getMessages()['test']);

            return;
        }

        $this->fail('Should have thrown a failed validation exception');
    }

    /** @test */
    public function wont_validate_if_not_in_the_correct_format()
    {
        try {
            $this->validator->validate([]);
        } catch (InvalidJsonException $exception) {
            $this->assertEquals(
                'Parser was unable to process the JSON due to there not being a data key.',
                $exception->getMessage()
            );

            return;
        }

        $this->fail('Should have thrown invalid json exception');
    }

    /** @test */
    public function can_get_all_errors()
    {
        $this->validator->addValidator('fail', new FakeFailingValidator());
        $this->validator->addPresenceChecker('fail-not-exist');

        try {
            $this->validator->validate([
                'data' => [
                    'id' => 1,
                    'type' => 'fail',
                    'attributes' => [
                        'test' => 1
                    ]
                ]
            ]);
        } catch (FailedValidationException $exception) {}

        $this->assertNotEmpty($this->validator->getErrors());
    }

    /** @test */
    public function can_check_for_type_exists_without_callable()
    {
        $this->validator->addPresenceChecker('test');
        $this->validator->addPresenceChecker('test-relation');

        $result = $this->validator->validate([
            'data' => [
                'type' => 'test',
                'id' => 1,
                'attributes' => [
                    'test' => 1,
                ],
                'relationships' => [
                    'relation' => [
                        'data' => [
                            'type' => 'test-relation',
                            'id' => 1
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'id' => 1,
                    'type' => 'test-relation',
                    'attributes' => [
                        'relation' => 1,
                    ]
                ]
            ]
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function wont_fail_if_resource_not_required()
    {
        $this->validator->addPresenceChecker('not-required', function () {
            return false;
        });

        $result = $this->validator->validate([
            'data' => [
                'type' => 'test',
                'id' => 1,
                'attributes' => [
                    'test' => 1,
                ]
            ]
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function reflection_exceptions_are_caught()
    {
        $this->validator->addResolver('test', 'UnknownValidator::handle');
        $result = $this->validator->validate([
            'data' => [
                'id' => 1,
                'type' => 'test',
                'attributes' => [
                    'test' => 1,
                ]
            ]
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_check_for_other_resources_in_resource_checker()
    {
        $this->validator->addPresenceChecker('test', function ($resources) {
            return $resources->hasNot('test-checker');
        });

        $result = $this->validator->validate([
            'data' => [
                'id' => 1,
                'type' => 'test-checker',
                'attributes' => [
                    'test' => 1,
                ],
                'relationships' => [
                    'relation' => [
                        'data' => [
                            'id' => 1,
                            'type' => 'test',
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'id' => 1,
                    'type' => 'test',
                    'attributes' => [
                        'relation' => 1,
                    ]
                ]
            ]
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_will_fail_if_cant_find_other_resources()
    {
        $this->validator->addPresenceChecker('test', function ($resources) {
            return $resources->has('test-checker');
        });

        try {
            $this->validator->validate([
                'data' => [
                    'id' => 1,
                    'type' => 'test-checker',
                    'attributes' => [
                        'test' => 1,
                    ]
                ]
            ]);
        } catch (FailedValidationException $exception) {
            $this->assertArrayHasKey('test', $exception->getMessages());

            return;
        }

        $this->fail('Did not fail when trying to find missing resource');
    }
}

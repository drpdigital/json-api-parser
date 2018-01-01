<?php

namespace Tests\Unit;

use Drp\JsonApiParser\Str;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{
    public function testSnakeCase()
    {
        $this->assertEquals('test_case', Str::snakeCase('Test Case'));
        $this->assertEquals('test_case', Str::snakeCase('TestCase'));
        $this->assertEquals('test_case', Str::snakeCase('test Case'));
        $this->assertEquals('test_case', Str::snakeCase('Test case'));
        $this->assertEquals('test_case', Str::snakeCase('testCase'));
        $this->assertEquals('t_e_s_t_c_a_s_e', Str::snakeCase('TEST CASE'));
        $this->assertEquals('test|_case', Str::snakeCase('test| case'));
        $this->assertEquals('t_e_s_t', Str::snakeCase('TEST'));
        $this->assertEquals('test_case', Str::snakeCase('test-case'));
    }
}

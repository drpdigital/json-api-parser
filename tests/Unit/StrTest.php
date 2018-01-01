<?php

namespace Tests\Unit;

use Drp\JsonApiParser\Str;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{
    public function testSnakeCase()
    {
        $this->assertEquals('test_case', Str::snakeCase('Test Case'));
        $this->assertEquals('test_case', Str::snakeCase('test Case'));
        $this->assertEquals('test_case', Str::snakeCase('Test case'));
        $this->assertEquals('test_case', Str::snakeCase('testCase'));
        $this->assertEquals('t_e_s_t_c_a_s_e', Str::snakeCase('TEST CASE'));
        $this->assertEquals('test|_case', Str::snakeCase('test| case'));
        $this->assertEquals('t_e_s_t', Str::snakeCase('TEST'));
        $this->assertEquals('test_case', Str::snakeCase('test-case'));
    }

    public function testCamelCase()
    {
        $this->assertEquals('testCase', Str::camelCase('Test Case'));
        $this->assertEquals('testCase', Str::camelCase('Test case'));
        $this->assertEquals('testCase', Str::camelCase('test Case'));
        $this->assertEquals('testCase', Str::camelCase('test_case'));
        $this->assertEquals('tESTCASE', Str::camelCase('TEST CASE'));
        $this->assertEquals('test|Case', Str::camelCase('test| case'));
        $this->assertEquals('tEST', Str::camelCase('TEST'));
        $this->assertEquals('testCase', Str::camelCase('test-case'));
    }
}

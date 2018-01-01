<?php

namespace Tests\Fakes;

use Drp\JsonApiParser\Arr;

class FakeModel1
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function __get($name)
    {
        return Arr::get($this->data, $name);
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }
}

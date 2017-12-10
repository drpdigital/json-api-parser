<?php

namespace Drp\JsonApiParser;

class UnresolvedResource
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $data;

    /**
     * UnresolvedResource constructor.
     *
     * @param string $type
     * @param array $data
     */
    public function __construct($type, array $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}

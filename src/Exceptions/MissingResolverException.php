<?php

namespace Drp\JsonApiParser\Exceptions;

class MissingResolverException extends \RuntimeException
{
    /**
     * @var \Drp\JsonApiParser\UnresolvedResource
     */
    private $resource;

    /**
     * @param \Drp\JsonApiParser\UnresolvedResource $resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;

        parent::__construct('Could not find a resolver for type: ' . $resource->getType());
    }

    /**
     * Get the type that the resource resolver tried to resolve.
     *
     * @return string
     */
    public function getResolverType()
    {
        return $this->resource->getType();
    }

    /**
     * @return \Drp\JsonApiParser\UnresolvedResource
     */
    public function getResource()
    {
        return $this->resource;
    }
}

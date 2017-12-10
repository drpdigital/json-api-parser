<?php

namespace Drp\JsonApiParser;

class JsonApiParser
{
    /**
     * The full data to parse
     *
     * @var array
     */
    protected $data;

    /**
     * The relationship data
     *
     * @var array
     */
    protected $included;

    /**
     * Resolver any json api resources
     *
     * @var ResourceResolver
     */
    protected $resolver;

    /**
     * JsonApiParser constructor.
     * @param ResourceResolver $resolver
     */
    public function __construct(ResourceResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Parse the data given
     *
     * Returns all the models that were resolved.
     *
     * @param array $input
     * @return \Drp\JsonApiParser\ResolvedCollection|null
     * @throws \ReflectionException
     * @throws \Drp\JsonApiParser\Exceptions\MissingResolverException
     */
    public function parse(array $input)
    {
        $this->data = Arr::get($input, 'data');

        if ($this->data === null) {
            return null;
        }

        $this->included = Arr::get($input, 'included', []);
        $this->start();

        return $this->resolver->getResolved();
    }

    /**
     * Adds the callbacks for resolve the different objects in the response
     *
     * @param array|string $relationshipName
     * @param callable|string $callback
     * @return JsonApiParser
     */
    public function addResolver($relationshipName, $callback)
    {
        $relationshipName = Arr::wrap($relationshipName);

        foreach ($relationshipName as $name) {
            $this->resolver->bind($name, $callback);
        }

        return $this;
    }

    /**
     * Starts the parsing and creating of the relationships.
     *
     * @return void
     * @throws \ReflectionException
     * @throws \Drp\JsonApiParser\Exceptions\MissingResolverException
     */
    private function start()
    {
        if (Arr::is_assoc($this->data)) {
            $this->data = [$this->data];
        }

        foreach ($this->data as $data) {
            $relationshipsToProcess = $this->resolveResource($data);
            $this->resolveRelationships($relationshipsToProcess);
        }
    }

    /**
     * @param array $resourceData
     * @param array $parentModels
     * @param string $type
     * @return array
     * @throws \ReflectionException
     * @throws \Drp\JsonApiParser\Exceptions\MissingResolverException
     */
    private function resolveResource(array $resourceData, array $parentModels = [], $type = null)
    {
        if ($type === null) {
            $type = Arr::get($resourceData, 'type');
        }

        $resource = $this->processResourceData($resourceData, $parentModels, $type);

        if ($this->shouldNotProcessRelationships($resource)) {
            return [];
        }

        if (is_object($resource)) {
            $parentModels[] = $resource;
        }

        return $this->getRelationshipResolvers($resourceData, $parentModels);
    }

    /**
     * Whether or not the parser should process the relationships of the given resource.
     *
     * @param mixed $resource
     * @return boolean
     */
    public function shouldNotProcessRelationships($resource)
    {
        return $resource instanceof UnresolvedResource;
    }

    /**
     * @param array $resourceData
     * @param array $parentModels
     * @return array
     * @throws \ReflectionException
     * @throws \Drp\JsonApiParser\Exceptions\MissingResolverException
     */
    private function getRelationshipResolvers(array $resourceData, array $parentModels)
    {
        $relationships = Arr::get($resourceData, 'relationships', []);
        $parentType = Arr::get($resourceData, 'type');

        $relationshipResources = array_map(function ($data) {
            if (is_array($data) === false) {
                return null;
            }

            $relationshipData = Arr::get($data, 'data', []);

            if (is_array($relationshipData) === false) {
                return null;
            }

            // If it is associative array then make it a index based
            // so we only have to handle 1 type of array.
            if (Arr::is_assoc($relationshipData)) {
                $relationshipData = [$relationshipData];
            }

            return $relationshipData;
        }, $relationships);
        $relationshipResources = array_filter($relationshipResources);

        // Map over the relationships and create a function to resolve the relationship later
        $relationFunctions = array_map(function (array $data, $relationshipType) use ($parentModels, $parentType) {
            return array_map(function ($resourceData) use ($relationshipType, $parentModels, $parentType) {
                $resourceId = Arr::get($resourceData, 'id');
                $resourceType = Arr::get($resourceData, 'type');

                if ($resourceId === null || $resourceType === null) {
                    return null;
                }

                $includedResource = $this->getIncludedResource($resourceId, $resourceType);
                $resolverType = $parentType . '.' . $relationshipType . '.' . $resourceType;

                return function () use ($includedResource, $parentModels, $resolverType) {
                    return $this->resolveResource(
                        $includedResource,
                        $parentModels,
                        $resolverType
                    );
                };
            }, $data);
        }, $relationshipResources, array_keys($relationshipResources));

        return Arr::flatten($relationFunctions);
    }

    /**
     * Get a resource that is in the included array
     *
     * Returns default resource if it was not able to find a resource with
     * the given type and id.
     *
     * @param integer $id
     * @param string $type
     * @return bool|mixed
     */
    private function getIncludedResource($id, $type)
    {
        foreach ($this->included as $included) {
            $includedId = Arr::get($included, 'id');
            $includedType = Arr::get($included, 'type');

            if ((string) $includedType === (string) $type && (string) $includedId === (string) $id) {
                return $included;
            }
        }

        return $this->getDefaultIncludedResource($id, $type);
    }

    /**
     * Get default included resource.
     *
     * @param mixed $resourceId
     * @param string $resourceType
     * @return array
     */
    private function getDefaultIncludedResource($resourceId, $resourceType)
    {
        return [
            'id' => $resourceId,
            'type' => $resourceType,
        ];
    }

    /**
     * Iterate over the relationship resolver (closures) that have been returned.
     *
     * @param array $relationshipsToProcess
     * @return void
     */
    private function resolveRelationships(array $relationshipsToProcess)
    {
        $iterator = new \ArrayIterator($relationshipsToProcess);
        while ($iterator->valid()) {
            $resolver = $iterator->current();
            if (is_callable($resolver) === false) {
                $iterator->next();
                continue;
            }
            $relationships = $resolver();

            foreach ($relationships as $relationship) {
                $iterator->append($relationship);
            }

            $iterator->next();
        }
    }

    /**
     * Create model from data given.
     *
     * @param array $resourceData
     * @param array $parentModels
     * @param string $type
     * @return mixed
     * @throws \ReflectionException
     * @throws \Drp\JsonApiParser\Exceptions\MissingResolverException
     */
    protected function processResourceData(array $resourceData, array $parentModels, $type)
    {
        return $this->resolver->resolve($type, $resourceData, $parentModels);
    }
}

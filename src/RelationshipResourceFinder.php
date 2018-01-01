<?php

namespace Drp\JsonApiParser;

class RelationshipResourceFinder
{
    /**
     * Resource array to get the relationships from.
     *
     * @var array
     */
    protected $resource;

    /**
     * RelationshipResourceFinder constructor.
     * @param array $resource
     */
    public function __construct(array $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Get a resource from just the relationship type.
     *
     * @param string $type
     * @return \Drp\JsonApiParser\RelationshipResource|null
     */
    public function fromRelationshipType($type)
    {
        $relationships = $this->getRelationshipsThatMatchType($type);

        if (count($relationships) === 0) {
            return null;
        }

        $resource = reset($relationships);

        return new RelationshipResource(
            key($relationships),
            $resource
        );
    }

    /**
     * Get all the relationships that their type matches the given type.
     *
     * @param string $type
     * @return array|mixed
     */
    protected function getRelationshipsThatMatchType($type)
    {
        return array_filter(
            $this->mapRelationshipsToIdAndData(),
            function ($relationship) use ($type) {
                $relationshipType = Arr::get($relationship, 'type');

                return $type === Str::snakeCase($relationshipType) || $type === Str::camelCase($relationshipType);
            }
        );
    }

    /**
     * Get a relationship resource from one of the fully qualified relationship referneces.
     * E.g. ['test.test-relationship.test-child']
     *
     * @param array $relationshipNames
     * @return \Drp\JsonApiParser\RelationshipResource|null
     */
    public function fromFullyQualifiedRelationships(array $relationshipNames)
    {
        foreach ($relationshipNames as $relationshipName) {
            $relationship = $this->getRelationshipResourceForReference($relationshipName);

            if ($relationship !== null) {
                return $relationship;
            }
        }

        return null;
    }

    /**
     * Get a relationship resource for a fully qualified reference.
     *
     * @param string $fullRelationshipReference
     * @return \Drp\JsonApiParser\RelationshipResource|null
     */
    protected function getRelationshipResourceForReference($fullRelationshipReference)
    {
        $relationshipParts = explode('.', $fullRelationshipReference);

        if (count($relationshipParts) !== 3) {
            return null;
        }

        if ($this->resourceType() !== array_shift($relationshipParts)) {
            return null;
        }

        $relationshipName = array_shift($relationshipParts);
        $relationshipReference = Arr::get(
            $this->mapRelationshipsToIdAndData(),
            $relationshipName
        );
        if ($relationshipReference === null) {
            return null;
        }

        if (Arr::get($relationshipReference, 'type') === array_shift($relationshipParts)) {
            return new RelationshipResource($relationshipName, $relationshipReference);
        }

        return null;
    }

    /**
     * @return mixed
     */
    protected function resourceType()
    {
        return Arr::get($this->resource, 'type');
    }

    /**
     * @return array
     */
    protected function mapRelationshipsToIdAndData()
    {
        return array_map(function ($relationship) {
            return Arr::get($relationship, 'data');
        }, Arr::get($this->resource, 'relationships', []));
    }
}

<?php

namespace Drp\JsonApiParser;

class RelationshipResource
{
    /**
     * Relationship name.
     *
     * @var string
     */
    protected $name;

    /**
     * Id of the relationship.
     *
     * @var mixed
     */
    protected $id;

    /**
     * Type of the relationship resource.
     *
     * @var string
     */
    protected $type;

    /**
     * RelationshipResource constructor.
     * @param string $relationshipName
     * @param array $relationshipArray
     */
    public function __construct($relationshipName, array $relationshipArray)
    {
        $this->name = $relationshipName;
        $this->type = Arr::get($relationshipArray, 'type');
        $this->id = Arr::get($relationshipArray, 'id');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}

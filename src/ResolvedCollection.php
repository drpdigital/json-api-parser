<?php

namespace Drp\JsonApiParser;

class ResolvedCollection extends Collection
{
    /**
     * This is a key=>value array of the class to the json api type.
     *
     * @var array
     */
    protected $classMapping;

    /**
     * Get the resolved resource by type.
     *
     * This will return an array of that resource if multiple resources of that type were
     * resolved. If only 1 item in the array then that 1 item will be returned instead.
     *
     * @param string $className
     * @return mixed|array
     */
    public function getByClass($className)
    {
        $types = array_unique(Arr::wrap(Arr::get($this->classMapping, $className, [])));
        $resolved = array_map([$this, 'get'], $types);

        return $this->firstOrAll(Arr::collapse($resolved));
    }

    /**
     * Add resolved resource to the collection.
     *
     * @param string $key
     * @param mixed $value
     */
    public function add($key, $value)
    {
        parent::add($key, $value);

        if (is_object($value) === false) {
            return;
        }

        $this->classMapping[get_class($value)][] = $key;
    }
}

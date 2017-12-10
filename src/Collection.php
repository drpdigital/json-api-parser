<?php

namespace Drp\JsonApiParser;

use Drp\JsonApiParser\Concerns\ChecksTypes;

class Collection implements \ArrayAccess
{
    use ChecksTypes;

    /**
     * Array of all resources data.
     *
     * @var array
     */
    protected $items;

    /**
     * Get the resolved resource by key.
     *
     * This will return an array of that resource if multiple resources are contained with the key.
     * If only 1 item in the array then that 1 item will be returned instead.
     *
     * @param string $key
     * @return mixed|array
     */
    public function get($key)
    {
        $items = Arr::get($this->items, $key);

        return $this->firstOrAll($items);
    }

    /**
     * Add resolved resource to the collection.
     *
     * @param string $key
     * @param mixed $value
     */
    public function add($key, $value)
    {
        $this->items[$key][] = $value;
    }

    /**
     * Will return the first item in the array if it is the only item. If not then all
     * items will be returned.
     *
     * @param array $items
     * @return mixed
     */
    protected function firstOrAll(array $items)
    {
        if (count($items) > 1) {
            return $items;
        }

        return array_shift($items);
    }

    /**
     * Get the keys for the items in this collection.
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->items);
    }

    /**
     * Does this collection have a value under the given key.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Does this collection not have a value under the given key.
     *
     * @param string $key
     * @return bool
     */
    public function hasNot($key)
    {
        return $this->offsetExists($key) === false;
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return $this->isTypeWithinArray($offset, array_keys($this->items));
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->add($offset, $value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }
}

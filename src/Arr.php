<?php

namespace Drp\JsonApiParser;

class Arr
{
    /**
     * Determines if an array is associative.
     *
     * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
     *
     * @param  array $array
     * @return bool
     */
    public static function is_assoc(array $array)
    {
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }

    /**
     * Get a value from an array. Return null if it doesn't exist.
     *
     * @param array $array
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function get(array $array, $key, $default = null)
    {
        return array_key_exists($key, $array) ? $array[$key] : $default;
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param array $array
     * @return array
     */
    public static function flatten(array $array)
    {
        $return = [];

        array_walk_recursive($array, function($x) use (&$return) { $return[] = $x; });

        return $return;
    }

    /**
     * If the given value is not an array, wrap it in one.
     *
     * @param  mixed  $value
     * @return array
     */
    public static function wrap($value)
    {
        return !is_array($value) ? [$value] : $value;
    }


    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  array  $array
     * @return array
     */
    public static function collapse($array)
    {
        $results = [];
        foreach ($array as $key => $values) {
            if (is_array($values) === false) {
                $results[$key] = $values;

                continue;
            }
            $results = array_merge($results, $values);
        }

        return $results;
    }
}

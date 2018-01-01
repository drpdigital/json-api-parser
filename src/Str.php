<?php

namespace Drp\JsonApiParser;

class Str
{
    /**
     * Convert given input into snake case.
     *
     * @param string $value
     * @return string
     */
    public static function snakeCase($value)
    {
        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = preg_replace('/-/u', '_', $value);

            $value = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $value), 'UTF-8');
        }

        return $value;
    }
}

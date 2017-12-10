<?php

namespace Drp\JsonApiParser\Concerns;

trait ChecksTypes
{
    /**
     * Checks if the given type exists within the array of types.
     *
     * @param string $type
     * @param array $haystackTypes
     * @return boolean
     */
    protected function isTypeWithinArray($type, array $haystackTypes)
    {
        $iterator = new \ArrayIterator($haystackTypes);

        while($iterator->valid()) {
            $seenValue = $iterator->current();

            if ($seenValue === $type) {
                return true;
            }

            $parts = explode('.', $seenValue);
            if (count($parts) > 1) {
                array_shift($parts);

                $newKey = implode('.', $parts);
                $iterator->append($newKey);
            }

            $iterator->next();
        }

        return false;
    }
}

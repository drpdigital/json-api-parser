<?php

namespace Drp\JsonApiParser\Contracts;

interface ValidatorExecutor
{
    /**
     * Data to be validated.
     *
     * @param array $data
     * @return void
     */
    public function with(array $data);

    /**
     * Run the validation using the given data from with()
     * If the validation doesn't pass then return false.
     *
     * @return boolean
     */
    public function passes();

    /**
     * Returns an array of error messages with a key for each attribute that has failed validation.
     * E.g.
     * [
     *     'firstName' => [
     *         'The first name is required.',
     *         'Can not have numbers in your first name.',
     *     ],
     *     'age' => [
     *         'Must be over 18.',
     *     ]
     * ]
     *
     * @return array
     */
    public function errors();
}

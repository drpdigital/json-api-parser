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
     * @return mixed
     */
    public function errors();
}

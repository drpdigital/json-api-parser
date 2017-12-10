<?php

namespace Drp\JsonApiParser\Exceptions;

class FailedValidationException extends \RuntimeException
{
    /**
     * An array of $field => $message[] of resources that failed validation.
     *
     * @var array
     */
    private $messages;

    /**
     * @param array $messages An array of $field => $message[] of resources that failed validation.
     * @param \Throwable|null $previous
     */
    public function __construct(array $messages, \Throwable $previous = null)
    {
        $this->messages = $messages;

        parent::__construct('Failed to validate your payload.', 422, $previous);
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}

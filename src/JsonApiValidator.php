<?php

namespace Drp\JsonApiParser;

use Drp\JsonApiParser\Exceptions\FailedValidationException;
use Drp\JsonApiParser\Exceptions\InvalidJsonException;
use Drp\JsonApiParser\Exceptions\MissingResolverException;

class JsonApiValidator extends JsonApiParser
{
    /**
     * Array of all the errors
     *
     * @var array
     */
    protected $errors;

    /**
     * Validators for the different types.
     *
     * @var \Drp\JsonApiParser\Contracts\ValidatorExecutor[]
     */
    protected $validators = [];

    /**
     * Callable to check if a type is required.
     *
     * @var callable[]
     */
    protected $presenceCheckers = [];

    /**
     * All the types that have been seen by this validator.
     *
     * @var array
     */
    protected $seenTypes;

    /**
     * JsonApiValidator constructor.
     *
     * @param ResourceResolver $resolver
     */
    public function __construct(
        ResourceResolver $resolver
    ) {
        parent::__construct($resolver);

        $this->seenTypes = new Collection();
        $this->errors = [];
    }

    /**
     * Add a validator to be ran when the type is found.
     *
     * @param string $type
     * @param \Drp\JsonApiParser\Contracts\ValidatorExecutor|array $validator
     * @return $this
     * @throws \Drp\JsonApiParser\Exceptions\FailedValidationException
     */
    public function addValidator($type, $validator)
    {
        $this->validators[$type] = $validator;

        $this->addResolver($type, function ($data, $resourceId) use ($type) {
            /** @var \Drp\JsonApiParser\Contracts\ValidatorExecutor[] $validators */
            $validators = Arr::wrap($this->validators[$type]);

            foreach ($validators as $validator) {
                $data['id'] = $resourceId;
                $validator->with($data);

                $result = $validator->passes();

                if (!$result) {
                    $errors = $validator->errors();

                    $types = explode('.', $type);
                    $key = end($types);

                    if ($resourceId !== null) {
                        $key .= '_' . $resourceId;
                    }

                    $this->errors[$key] = array_merge(Arr::get($this->errors, $key, []), $errors);
                }
            }
        });

        return $this;
    }

    /**
     * Remove validator(s)
     *
     * @param string|array $validatorName
     */
    public function removeValidator($validatorName)
    {
        $validators = Arr::wrap($validatorName);
        $this->resolver->remove($validators);

        foreach ($validators as $validator) {
            if (array_key_exists($validator, $this->validators)) {
                unset($this->validators[$validator]);
            }
        }
    }

    /**
     * Validate input.
     *
     * @param array $input
     * @return bool
     * @throws \Drp\JsonApiParser\Exceptions\FailedValidationException
     * @throws \ReflectionException
     * @throws \Drp\JsonApiParser\Exceptions\MissingResolverException
     * @throws \Drp\JsonApiParser\Exceptions\InvalidJsonException
     */
    public function validate(array $input)
    {
        $result = $this->parse($input);

        if ($result === null) {
            throw new InvalidJsonException(
                'Parser was unable to process the JSON due to there not being a data key.'
            );
        }

        if (count($this->seenTypes) > 0) {
            array_walk($this->presenceCheckers, function (callable $callback = null, $type) {
                if ($callback !== null) {
                    try {
                        $isRequired = $callback($this->seenTypes);
                    } catch (FailedValidationException $exception) {
                        $this->errors = array_merge($this->errors, [
                            $type => $exception->getMessages()
                        ]);

                        return;
                    }

                    if ($isRequired === false) {
                        return;
                    }
                } elseif ($this->seenTypes->has($type)) {
                    return;
                }

                $this->errors[$type] = 'Missing api resource from the request.';
            });
        }

        if (count($this->errors) > 0) {
            throw new FailedValidationException($this->errors);
        }

        return true;
    }

    /**
     * Get validation errors.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Checks if the resource type provided is required.
     *
     * @param string $type
     * @param callable $callback
     * @return $this
     */
    public function addPresenceChecker($type, $callback = null)
    {
        $this->presenceCheckers[$type] = $callback;

        return $this;
    }

    /**
     * Whether or not the parser should process the relationships of the given resource.
     *
     * @param mixed $resource
     * @return boolean
     */
    public function shouldNotProcessRelationships($resource)
    {
        return false;
    }

    /**
     * Create model from data given.
     *
     * @param array $resourceData
     * @param array $parentModels
     * @param string $type
     * @return mixed|null
     */
    protected function processResourceData(array $resourceData, array $parentModels, $type)
    {
        try {
            $this->seenTypes[$type] = $resourceData;
            return parent::processResourceData($resourceData, $parentModels, $type);
        } catch (MissingResolverException $exception) {
        } catch (\ReflectionException $exception) {
        }

        return null;
    }

    /**
     * Magic function for getting validators.
     *
     * @param string $field
     * @return mixed
     */
    public function __get($field)
    {
        return $this->getValidator($field);
    }

    /**
     * Get the validator for the given name
     *
     * @param string $validator
     * @return mixed
     */
    public function getValidator($validator)
    {
        return Arr::get($this->validators, $validator);
    }



    /**
     * Returns all the validator registered.
     *
     * @return array
     */
    public function all()
    {
        return $this->validators;
    }
}

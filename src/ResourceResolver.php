<?php

namespace Drp\JsonApiParser;

use Drp\JsonApiParser\Exceptions\MissingResolverException;
use Psr\Container\ContainerInterface;
use ReflectionFunction;
use ReflectionMethod;

class ResourceResolver
{
    /**
     * Callbacks for resolving the resources.
     *
     * @var array
     */
    protected $resolvers = [];

    /**
     * Collection of all the resolved resources keyed by their type and id.
     *
     * @var \Drp\JsonApiParser\ResolvedCollection
     */
    protected $resolved = [];

    /**
     * Container to build the extra parameters needed.
     *
     * @var \Psr\Container\ContainerInterface
     */
    private $container;

    /**
     * Callable to execute when can't resolve a resource due to no resolver.
     *
     * @var callable
     */
    private $missingResolverCallback;

    /**
     * ResourceResolver constructor.
     * @param \Psr\Container\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->resolved = new ResolvedCollection();
        $this->container = $container;
        $this->missingResolverCallback = function ($resource) {
            throw new MissingResolverException($resource);
        };
    }

    /**
     * Adds the callbacks for resolve the different objects in the response
     *
     * @param string $resolverKey
     * @param callable|string $callback
     * @return void
     */
    public function bind($resolverKey, $callback)
    {
        $this->resolvers[$resolverKey] = $callback;
    }

    /**
     * Get resolved resources.
     *
     * @return \Drp\JsonApiParser\ResolvedCollection
     */
    public function getResolved()
    {
        return $this->resolved;
    }

    /**
     * @param string $type
     * @param array $resource
     * @param array|null $parents
     * @return mixed
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws \Drp\JsonApiParser\Exceptions\MissingResolverException
     */
    public function resolve($type, array $resource, array $parents = [])
    {
        $resolver = $this->getResolverForType($type);

        if ($resolver === null || (!is_callable($resolver) && !is_string($resolver))) {
            $unresolveResource = new UnresolvedResource($type, $resource);

            $callback = $this->missingResolverCallback;
            $shouldContinue = $callback($unresolveResource);

            if ($shouldContinue === false || $shouldContinue === null) {
                return $unresolveResource;
            }
        }

        $id = Arr::get($resource, 'id');

        $defaultParameters = [
            (array) Arr::get($resource, 'attributes', []),
            $id,
        ];
        $parameters = $this->buildParametersForResolver(
            $resolver,
            $parents,
            $defaultParameters
        );

        $resolved = $this->callResolver($resolver, $parameters);
        if (is_object($resolved)) {
            $this->resolved->add($type, $resolved);
        }

        return $resolved;
    }

    /**
     * Build the parameters needed for the function given
     *
     * @param callable|string $resolver
     * @param array $parents
     * @param array $defaultParameters
     * @return array
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \ReflectionException
     */
    protected function buildParametersForResolver($resolver, array $parents, array $defaultParameters)
    {
        $reflector = $this->getCallReflector($resolver);

        $parameters = $reflector->getParameters();
        $defaultParameters = array_reverse($defaultParameters);

        return array_map(function (\ReflectionParameter $parameter) use (&$defaultParameters, $parents) {
            $parameterClass = $parameter->getClass();

            // If the parameter doesn't have a class then it isn't type hinted
            // and thus no need for dependency injection.
            if ($parameterClass === null) {
                return array_pop($defaultParameters);
            }

            // If the parameter asks for a dependency then check parents first
            // and then fallback to the application IOC
            $parent = $this->findParent($parameterClass, $parents);

            if ($parent !== null) {
                return $parent;
            }

            if ($this->container->has($parameterClass->getName())) {
                return $this->container->get($parameterClass->getName());
            }

            return null;
        }, $parameters);
    }

    /**
     * Get the proper reflection instance for the given callback.
     *
     * @param  callable|string $callback
     * @return \ReflectionFunctionAbstract
     * @throws \ReflectionException
     */
    protected function getCallReflector($callback)
    {
        if (is_string($callback) && strpos($callback, '::') !== false) {
            $callback = explode('::', $callback);
        }

        if (is_array($callback)) {
            return new ReflectionMethod($callback[0], $callback[1]);
        }

        return new ReflectionFunction($callback);
    }

    /**
     * Try to find a parent that matches the class
     *
     * @param \ReflectionClass $class
     * @param array $parents
     * @return mixed|null
     */
    protected function findParent(\ReflectionClass $class, $parents)
    {
        $parents = array_reverse($parents);

        foreach (array_reverse($parents) as $parent) {
            if ($class->getName() === get_class($parent)) {
                return $parent;
            }
        }

        foreach (array_reverse($parents) as $parent) {
            if ($class->isInstance($parent)) {
                return $parent;
            }
        }

        return null;
    }

    /**
     * Find the resolver for the type given
     *
     * Fallback the specificity of the type until we find a match. For example
     * if a type of customer.products.customer_product is passed we first check
     * for that, then products.customer_product and then customer_product.
     *
     * @param string $type
     * @return mixed
     */
    private function getResolverForType($type)
    {
        $resolver = Arr::get($this->resolvers, $type);

        if ($resolver !== null) {
            return $resolver;
        }

        $parts = explode('.', $type);
        if (count($parts) > 1) {
            array_shift($parts);

            return $this->getResolverForType(implode('.', $parts));
        }

        return null;
    }

    /**
     * Remove a resolver(s)
     *
     * @param string|array $resolverType
     * @return void
     */
    public function remove($resolverType)
    {
        $resolverType = Arr::wrap($resolverType);

        foreach ($resolverType as $type) {
            unset($this->resolvers[$type]);
        }
    }

    /**
     * Call the resolver with the given parameters.
     *
     * @param callable $resolver
     * @param array $parameters
     * @return mixed
     */
    protected function callResolver($resolver, $parameters)
    {
        if (is_string($resolver)) {
            return call_user_func_array($resolver, $parameters);
        }

        if (count($parameters) === 0) {
            return $resolver();
        }

        if (count($parameters) === 1) {
            return $resolver($parameters[0]);
        }

        if (count($parameters) === 2) {
            return $resolver($parameters[0], $parameters[1]);
        }

        if (count($parameters) === 3) {
            return $resolver($parameters[0], $parameters[1], $parameters[2]);
        }

        if (count($parameters) === 4) {
            return $resolver($parameters[0], $parameters[1], $parameters[2], $parameters[3]);
        }

        return call_user_func_array($resolver, $parameters);
    }

    /**
     * Callback to execute when a resource can't be resolved.
     * The callback needs to return true or false on whether to continue processing this resource.
     *
     * @param callable $callback
     * @return \Drp\JsonApiParser\ResourceResolver
     */
    public function onMissingResolver(callable $callback)
    {
        $this->missingResolverCallback = $callback;

        return $this;
    }
}

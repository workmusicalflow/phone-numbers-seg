<?php

namespace App\GraphQL;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

/**
 * A simple implementation of PSR-11 ContainerInterface
 */
class SimpleContainer implements ContainerInterface
{
    private array $instances = [];

    /**
     * Constructor
     *
     * @param array $instances Initial instances to register
     */
    public function __construct(array $instances = [])
    {
        $this->instances = $instances;
    }

    /**
     * Register a service in the container
     *
     * @param string $id Service identifier
     * @param mixed $instance Service instance
     * @return void
     */
    public function set(string $id, $instance): void
    {
        $this->instances[$id] = $instance;
    }

    /**
     * Finds an entry of the container by its identifier and returns it
     *
     * @param string $id Identifier of the entry to look for
     * @return mixed Entry
     * @throws NotFoundExceptionInterface No entry was found for **this** identifier
     * @throws ContainerExceptionInterface Error while retrieving the entry
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new class($id) extends \Exception implements NotFoundExceptionInterface {
                public function __construct(string $id)
                {
                    parent::__construct("Service '$id' not found in container");
                }
            };
        }

        return $this->instances[$id];
    }

    /**
     * Returns true if the container can return an entry for the given identifier
     *
     * @param string $id Identifier of the entry to look for
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id]);
    }
}

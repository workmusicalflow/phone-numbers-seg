<?php

namespace App\GraphQL;

use DI\Container;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * A PSR-11 compatible container implementation using PHP-DI
 */
class DIContainer implements ContainerInterface
{
    private Container $container;

    /**
     * Constructor
     *
     * @param array $additionalDefinitions Additional definitions to merge with the main configuration
     */
    public function __construct(array $additionalDefinitions = [])
    {
        $builder = new ContainerBuilder();

        // Enable compilation for better performance in production
        if (getenv('APP_ENV') === 'production') {
            $builder->enableCompilation(__DIR__ . '/../../var/cache');
            $builder->writeProxiesToFile(true, __DIR__ . '/../../var/cache/proxies');
        }

        // Load the main configuration
        $definitions = require __DIR__ . '/../config/di.php';

        // Merge with additional definitions
        if (!empty($additionalDefinitions)) {
            $definitions = array_merge($definitions, $additionalDefinitions);
        }

        $builder->addDefinitions($definitions);

        $this->container = $builder->build();
    }

    /**
     * Finds an entry of the container by its identifier and returns it
     *
     * @param string $id Identifier of the entry to look for
     * @return mixed Entry
     * @throws NotFoundExceptionInterface No entry was found for **this** identifier
     */
    public function get(string $id)
    {
        return $this->container->get($id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier
     *
     * @param string $id Identifier of the entry to look for
     * @return bool
     */
    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * Set a service in the container
     * 
     * This method is provided for backward compatibility with SimpleContainer
     *
     * @param string $id Service identifier
     * @param mixed $instance Service instance
     * @return void
     */
    public function set(string $id, $instance): void
    {
        // PHP-DI doesn't allow setting entries after container creation
        // We'll use a workaround by creating a new container with the additional definition
        $this->container = (new ContainerBuilder())
            ->addDefinitions(require __DIR__ . '/../config/di.php')
            ->addDefinitions([$id => $instance])
            ->build();
    }

    /**
     * Get the underlying PHP-DI container
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
}

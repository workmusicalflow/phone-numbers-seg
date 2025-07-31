<?php

namespace App\GraphQL\Context;

use App\GraphQL\DataLoaders\ContactGroupDataLoader;
use App\Services\Interfaces\AuthServiceInterface;

/**
 * GraphQL Context
 * 
 * This class represents the context for a GraphQL request.
 * It contains shared objects like the current user and data loaders.
 */
class GraphQLContext
{
    /**
     * @var object|null
     */
    private ?object $currentUser = null;

    /**
     * @var array<string, object>
     */
    private array $dataLoaders = [];

    /**
     * @var AuthServiceInterface
     */
    private AuthServiceInterface $authService;

    /**
     * Constructor
     *
     * @param AuthServiceInterface $authService The auth service
     */
    public function __construct(AuthServiceInterface $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Initialize the context
     *
     * @return self
     */
    public function initialize(): self
    {
        // Try to get the current user
        $this->currentUser = $this->authService->getCurrentUser();
        return $this;
    }

    /**
     * Get the current user
     *
     * @return object|null The current user
     */
    public function getCurrentUser(): ?object
    {
        return $this->currentUser;
    }

    /**
     * Register a data loader
     *
     * @param string $name The name of the data loader
     * @param object $dataLoader The data loader instance
     * @return self
     */
    public function registerDataLoader(string $name, object $dataLoader): self
    {
        $this->dataLoaders[$name] = $dataLoader;
        return $this;
    }

    /**
     * Get a data loader
     *
     * @param string $name The name of the data loader
     * @return object|null The data loader
     */
    public function getDataLoader(string $name): ?object
    {
        return $this->dataLoaders[$name] ?? null;
    }

    /**
     * Check if a data loader exists
     *
     * @param string $name The name of the data loader
     * @return bool Whether the data loader exists
     */
    public function hasDataLoader(string $name): bool
    {
        return isset($this->dataLoaders[$name]);
    }
}
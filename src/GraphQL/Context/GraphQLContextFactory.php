<?php

namespace App\GraphQL\Context;

use App\GraphQL\DataLoaders\ContactGroupDataLoader;
use App\Services\Interfaces\AuthServiceInterface;
use Psr\Container\ContainerInterface;

/**
 * GraphQL Context Factory
 * 
 * This class creates and configures a GraphQL context for each request.
 */
class GraphQLContextFactory
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var AuthServiceInterface
     */
    private AuthServiceInterface $authService;

    /**
     * Constructor
     *
     * @param ContainerInterface $container DI container
     * @param AuthServiceInterface $authService Auth service
     */
    public function __construct(
        ContainerInterface $container,
        AuthServiceInterface $authService
    ) {
        $this->container = $container;
        $this->authService = $authService;
    }

    /**
     * Create a new GraphQL context
     *
     * @return GraphQLContext The GraphQL context
     */
    public function create(): GraphQLContext
    {
        // Create and initialize the context
        $context = new GraphQLContext($this->authService);
        $context->initialize();

        // Register data loaders
        $this->registerDataLoaders($context);

        return $context;
    }

    /**
     * Register data loaders in the context
     *
     * @param GraphQLContext $context The GraphQL context
     * @return void
     */
    private function registerDataLoaders(GraphQLContext $context): void
    {
        // Get the user ID for security filtering
        $currentUser = $context->getCurrentUser();
        $userId = $currentUser ? $currentUser->getId() : null;

        // Register ContactGroupDataLoader - create a fresh instance for each request
        if ($this->container->has(ContactGroupDataLoader::class)) {
            $dataLoader = $this->container->get(ContactGroupDataLoader::class);
            
            // Clear any cached data from previous requests
            $dataLoader->clearCache();
            
            // Set user ID if available
            if ($userId !== null) {
                $dataLoader->setUserId($userId);
            }
            
            $context->registerDataLoader('contactGroups', $dataLoader);
        }

        // Register SMSHistoryDataLoader
        if ($this->container->has(\App\GraphQL\DataLoaders\SMSHistoryDataLoader::class)) {
            $dataLoader = $this->container->get(\App\GraphQL\DataLoaders\SMSHistoryDataLoader::class);
            
            // Clear any cached data from previous requests
            $dataLoader->clearCache();
            
            // Set user ID if available
            if ($userId !== null) {
                $dataLoader->setUserId($userId);
            }
            
            $context->registerDataLoader('smsHistory', $dataLoader);
        }

        // Register other data loaders as needed...
    }
}
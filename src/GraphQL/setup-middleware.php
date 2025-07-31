<?php

declare(strict_types=1);

use App\GraphQL\Extensions\GraphQLNullSafetyMiddleware;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\Promise;
use Psr\Log\LoggerInterface;
use TheCodingMachine\GraphQLite\Schema;

/**
 * Configure le middleware GraphQLNullSafetyMiddleware pour éviter les erreurs
 * "Cannot return null for non-nullable field" dans GraphQL.
 * 
 * Ce script doit être inclus dans le fichier de bootstrap GraphQL.
 * 
 * @param Schema $schema Le schéma GraphQL
 * @param LoggerInterface $logger Le logger
 * @return callable La fonction middleware
 */
function setupGraphQLNullSafetyMiddleware(Schema $schema, LoggerInterface $logger): callable
{
    $middleware = new GraphQLNullSafetyMiddleware($schema, $logger);
    
    return function (ExecutionResult $result) use ($middleware): ExecutionResult|Promise {
        return $middleware->process($result);
    };
}
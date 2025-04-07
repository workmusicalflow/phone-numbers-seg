<?php

namespace App\GraphQL\Controllers;

use TheCodingMachine\GraphQLite\Annotations\Query;

/**
 * A dummy controller to test GraphQL registration
 */
class DummyController
{
    /**
     * A simple test query
     */
    #[Query]
    public function test(): string
    {
        return "GraphQL is working!";
    }
}

<?php

namespace App\GraphQL\Controllers;

use TheCodingMachine\GraphQLite\Annotations\Query;

/**
 * A test controller
 */
class TestController
{
    /**
     * A simple test query
     */
    #[Query]
    public function hello(): string
    {
        return "Hello, world!";
    }
}

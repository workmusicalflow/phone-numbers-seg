<?php

namespace Tests\Fixtures;

use App\Entities\User;

/**
 * Mock User class for testing
 */
class MockUser extends User
{
    private int $mockId = 1;
    
    /**
     * Set the ID (for testing purposes only)
     * 
     * @param int $id
     * @return self
     */
    public function setId(int $id): self
    {
        $this->mockId = $id;
        return $this;
    }
    
    /**
     * Get the ID (overridden for testing)
     * 
     * @return int
     */
    public function getId(): int
    {
        return $this->mockId;
    }
}
<?php

namespace Tests\WhatsApp;

use App\Entities\User;

/**
 * Mock User class for testing
 */
class WhatsAppMockUser extends User
{
    /**
     * Set the ID (for testing purposes only)
     * 
     * @param int $id
     * @return self
     */
    public function setId(int $id): self
    {
        return $this;
    }
}
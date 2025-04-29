<?php

namespace App\Services\Interfaces;

/**
 * Interface for token cache service
 */
interface TokenCacheInterface
{
    /**
     * Get token from cache
     *
     * @return string|null The token or null if not found or expired
     */
    public function getToken(): ?string;

    /**
     * Store token in cache
     *
     * @param string $token The token to store
     * @param int $expiresIn Token expiration time in seconds
     * @return void
     */
    public function storeToken(string $token, int $expiresIn): void;

    /**
     * Check if a valid token exists in cache
     *
     * @return bool True if token exists and is valid
     */
    public function isTokenValid(): bool;

    /**
     * Invalidate the current token
     *
     * @return void
     */
    public function invalidateToken(): void;
}
<?php

namespace App\Services;

use App\Services\Interfaces\TokenCacheInterface;
use Psr\Log\LoggerInterface;

/**
 * Token cache service implementation
 */
class TokenCacheService implements TokenCacheInterface
{
    /**
     * @var string
     */
    private $cacheFile;

    /**
     * @var int
     */
    private $tokenLifetime;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param string|null $cacheDir Directory for cache file
     * @param int $tokenLifetime Default token lifetime in seconds
     */
    public function __construct(
        LoggerInterface $logger,
        ?string $cacheDir = null,
        int $tokenLifetime = 3600
    ) {
        $this->logger = $logger;
        $this->cacheFile = ($cacheDir ?? sys_get_temp_dir()) . '/orange_api_token.cache';
        $this->tokenLifetime = $tokenLifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): ?string
    {
        if (!file_exists($this->cacheFile)) {
            $this->logger->debug('Token cache file not found');
            return null;
        }

        try {
            $data = json_decode(file_get_contents($this->cacheFile), true);
            if (!$data || !isset($data['token']) || !isset($data['expires_at'])) {
                $this->logger->warning('Invalid token cache data', [
                    'data' => $data ?? 'null'
                ]);
                return null;
            }

            // Check if token is still valid with a safety margin of 5 minutes
            if ($data['expires_at'] - 300 < time()) {
                $this->logger->debug('Cached token has expired or will expire soon');
                return null;
            }

            $this->logger->debug('Retrieved valid token from cache', [
                'expires_in' => $data['expires_at'] - time()
            ]);
            return $data['token'];
        } catch (\Exception $e) {
            $this->logger->error('Error reading token from cache: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function storeToken(string $token, int $expiresIn): void
    {
        try {
            $data = [
                'token' => $token,
                'expires_at' => time() + $expiresIn,
            ];

            $cacheDir = dirname($this->cacheFile);
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }

            file_put_contents($this->cacheFile, json_encode($data));
            $this->logger->info('Token stored in cache', [
                'expires_at' => date('Y-m-d H:i:s', $data['expires_at']),
                'expires_in_seconds' => $expiresIn
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error storing token in cache: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            // If we can't store the token, log but don't throw
            // The system should still work without caching
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isTokenValid(): bool
    {
        return $this->getToken() !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateToken(): void
    {
        if (file_exists($this->cacheFile)) {
            try {
                unlink($this->cacheFile);
                $this->logger->info('Token cache invalidated');
            } catch (\Exception $e) {
                $this->logger->error('Error invalidating token cache: ' . $e->getMessage(), [
                    'exception' => $e
                ]);
            }
        } else {
            $this->logger->debug('Token cache file not found during invalidation');
        }
    }
}
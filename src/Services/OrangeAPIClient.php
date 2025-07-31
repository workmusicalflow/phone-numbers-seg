<?php

namespace App\Services;

use App\Services\Interfaces\OrangeAPIClientInterface;
use App\Services\Interfaces\TokenCacheInterface;
use Psr\Log\LoggerInterface;

/**
 * Client for Orange SMS API
 */
class OrangeAPIClient implements OrangeAPIClientInterface
{
    /**
     * @var string Orange API client ID
     */
    private string $clientId;

    /**
     * @var string Orange API client secret
     */
    private string $clientSecret;

    /**
     * @var string Sender address (phone number)
     */
    private string $senderAddress;

    /**
     * @var string Sender name
     */
    private string $senderName;
    
    /**
     * @var TokenCacheInterface
     */
    private TokenCacheInterface $tokenCache;
    
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    
    /**
     * @var string|null
     */
    private ?string $currentToken = null;

    /**
     * Constructor
     * 
     * @param string $clientId Orange API client ID
     * @param string $clientSecret Orange API client secret
     * @param string $senderAddress Sender address (phone number)
     * @param string $senderName Sender name
     * @param TokenCacheInterface $tokenCache Token cache service
     * @param LoggerInterface $logger Logger
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $senderAddress,
        string $senderName,
        TokenCacheInterface $tokenCache,
        LoggerInterface $logger
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->senderAddress = $senderAddress;
        $this->senderName = $senderName;
        $this->tokenCache = $tokenCache;
        $this->logger = $logger;
    }

    /**
     * Get an access token from the Orange API
     * 
     * @return string Access token
     * @throws \RuntimeException If the token cannot be obtained
     */
    public function getAccessToken(): string
    {
        // Check for token in memory first (request scope)
        if ($this->currentToken !== null) {
            $this->logger->debug('Using in-memory token');
            return $this->currentToken;
        }
        
        // Check for cached token
        $cachedToken = $this->tokenCache->getToken();
        if ($cachedToken !== null) {
            $this->logger->debug('Using cached token from TokenCacheService');
            $this->currentToken = $cachedToken; // Store in memory for this request
            return $cachedToken;
        }
        
        $this->logger->info('No valid token in cache, requesting new token from Orange API');
        
        // Implement a simple lock mechanism to avoid concurrent token requests
        $lockFile = sys_get_temp_dir() . '/orange_api_token.lock';
        $lockAcquired = false;
        
        try {
            // Try to acquire a lock
            $lockHandle = fopen($lockFile, 'w+');
            if ($lockHandle === false) {
                $this->logger->warning('Could not open lock file, proceeding without lock');
            } else {
                $lockAcquired = flock($lockHandle, LOCK_EX | LOCK_NB);
                if (!$lockAcquired) {
                    $this->logger->debug('Another process is obtaining a token, waiting briefly...');
                    // Wait a moment and check cache again
                    sleep(2);
                    $cachedToken = $this->tokenCache->getToken();
                    if ($cachedToken !== null) {
                        $this->logger->debug('Found token in cache after waiting');
                        $this->currentToken = $cachedToken;
                        return $cachedToken;
                    }
                    // If still no token, proceed to get one ourselves
                }
            }
            
            // Proceed with token request
            $url = 'https://api.orange.com/oauth/v3/token';
            $data = 'grant_type=client_credentials';

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                'Content-Type: application/x-www-form-urlencoded'
            ));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                $this->logger->error('cURL Error while requesting token: ' . $error);
                throw new \RuntimeException('cURL Error: ' . $error);
            }
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $responseData = json_decode($response, true);
            
            if ($httpCode !== 200) {
                $this->logger->error('API Error while requesting token', [
                    'http_code' => $httpCode,
                    'response' => $responseData
                ]);
                throw new \RuntimeException('API Error: HTTP ' . $httpCode . ' - Unable to obtain access token');
            }
            
            if (isset($responseData['access_token'])) {
                $token = $responseData['access_token'];
                $expiresIn = $responseData['expires_in'] ?? 3600; // Default 1 hour if not provided
                
                // Store in cache for future requests
                $this->tokenCache->storeToken($token, $expiresIn);
                
                // Store in memory for this request
                $this->currentToken = $token;
                
                $this->logger->info('Successfully obtained new token', [
                    'expires_in' => $expiresIn
                ]);
                
                return $token;
            } else {
                $this->logger->error('Invalid token response from API', [
                    'response' => $responseData
                ]);
                throw new \RuntimeException('Error: Unable to obtain access token - Invalid response format');
            }
        } finally {
            // Release lock if acquired
            if ($lockAcquired && isset($lockHandle)) {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
                @unlink($lockFile);
            } else if (isset($lockHandle)) {
                @fclose($lockHandle);
            }
        }
    }

    /**
     * Send an SMS via the Orange API
     * 
     * @param string $receiverNumber Receiver phone number (normalized with tel: prefix)
     * @param string $message SMS message
     * @return array API response
     * @throws \RuntimeException If the SMS cannot be sent
     */
    public function sendSMS(string $receiverNumber, string $message): array
    {
        // Get an access token
        $accessToken = $this->getAccessToken();

        // Prepare the URL and data
        $url = 'https://api.orange.com/smsmessaging/v1/outbound/' . urlencode($this->senderAddress) . '/requests';
        $smsData = array(
            'outboundSMSMessageRequest' => array(
                'address' => $receiverNumber,
                'outboundSMSTextMessage' => array(
                    'message' => $message,
                ),
                'senderAddress' => $this->senderAddress,
                'senderName' => $this->senderName,
            )
        );

        // Send the request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($smsData));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $errorMessage = 'cURL Error: ' . curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException($errorMessage);
        }
        curl_close($ch);

        $responseData = json_decode($response, true);

        // Check if the response indicates success
        $isSuccess = $httpCode >= 200 && $httpCode < 300 && isset($responseData['outboundSMSMessageRequest']);

        if (!$isSuccess) {
            throw new \RuntimeException('API Error: ' . ($httpCode . ' - ' . json_encode($responseData)));
        }

        return $responseData;
    }

    /**
     * Get the sender address
     * 
     * @return string Sender address
     */
    public function getSenderAddress(): string
    {
        return $this->senderAddress;
    }

    /**
     * Get the sender name
     * 
     * @return string Sender name
     */
    public function getSenderName(): string
    {
        return $this->senderName;
    }
}

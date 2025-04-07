<?php

namespace App\Services\Interfaces;

/**
 * Interface for Orange API client
 */
interface OrangeAPIClientInterface
{
    /**
     * Get an access token from the Orange API
     * 
     * @return string Access token
     * @throws \RuntimeException If the token cannot be obtained
     */
    public function getAccessToken(): string;

    /**
     * Send an SMS via the Orange API
     * 
     * @param string $receiverNumber Receiver phone number (normalized with tel: prefix)
     * @param string $message SMS message
     * @return array API response
     * @throws \RuntimeException If the SMS cannot be sent
     */
    public function sendSMS(string $receiverNumber, string $message): array;

    /**
     * Get the sender address
     * 
     * @return string Sender address
     */
    public function getSenderAddress(): string;

    /**
     * Get the sender name
     * 
     * @return string Sender name
     */
    public function getSenderName(): string;
}

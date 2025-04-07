<?php

namespace App\Services;

use App\Services\Interfaces\OrangeAPIClientInterface;

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
     * Constructor
     * 
     * @param string $clientId Orange API client ID
     * @param string $clientSecret Orange API client secret
     * @param string $senderAddress Sender address (phone number)
     * @param string $senderName Sender name
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $senderAddress,
        string $senderName
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->senderAddress = $senderAddress;
        $this->senderName = $senderName;
    }

    /**
     * Get an access token from the Orange API
     * 
     * @return string Access token
     * @throws \RuntimeException If the token cannot be obtained
     */
    public function getAccessToken(): string
    {
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
            throw new \RuntimeException('cURL Error: ' . curl_error($ch));
        }
        curl_close($ch);

        $responseData = json_decode($response, true);
        if (isset($responseData['access_token'])) {
            return $responseData['access_token'];
        } else {
            throw new \RuntimeException('Error: Unable to obtain access token');
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

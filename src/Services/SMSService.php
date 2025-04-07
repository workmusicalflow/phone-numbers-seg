<?php

namespace App\Services;

use App\Repositories\CustomSegmentRepository;
use App\Repositories\PhoneNumberRepository;
use App\Repositories\SMSHistoryRepository;
use App\Models\SMSHistory;

/**
 * SMSService
 * 
 * Service for sending SMS messages using the Orange API
 */
class SMSService
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
     * @var PhoneNumberRepository|null
     */
    private ?PhoneNumberRepository $phoneNumberRepository;

    /**
     * @var CustomSegmentRepository|null
     */
    private ?CustomSegmentRepository $customSegmentRepository;

    /**
     * @var SMSHistoryRepository|null
     */
    private ?SMSHistoryRepository $smsHistoryRepository;

    /**
     * Constructor
     * 
     * @param string $clientId Orange API client ID
     * @param string $clientSecret Orange API client secret
     * @param string $senderAddress Sender address (phone number)
     * @param string $senderName Sender name
     * @param PhoneNumberRepository|null $phoneNumberRepository
     * @param CustomSegmentRepository|null $customSegmentRepository
     * @param SMSHistoryRepository|null $smsHistoryRepository
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $senderAddress,
        string $senderName,
        ?PhoneNumberRepository $phoneNumberRepository = null,
        ?CustomSegmentRepository $customSegmentRepository = null,
        ?SMSHistoryRepository $smsHistoryRepository = null
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->senderAddress = $senderAddress;
        $this->senderName = $senderName;
        $this->phoneNumberRepository = $phoneNumberRepository;
        $this->customSegmentRepository = $customSegmentRepository;
        $this->smsHistoryRepository = $smsHistoryRepository;
    }

    /**
     * Get an access token from the Orange API
     * 
     * @return string Access token
     * @throws \RuntimeException If the token cannot be obtained
     */
    private function getAccessToken(): string
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
     * Send an SMS to a single phone number
     * 
     * @param string $receiverNumber Receiver phone number
     * @param string $message SMS message
     * @return array API response
     * @throws \RuntimeException If the SMS cannot be sent
     */
    public function sendSMS(string $receiverNumber, string $message): array
    {
        // Normalize the receiver number to international format with tel: prefix
        $receiverNumber = $this->normalizePhoneNumber($receiverNumber);
        $originalNumber = preg_replace('/^tel:/', '', $receiverNumber);

        // Get an access token
        try {
            $accessToken = $this->getAccessToken();
        } catch (\RuntimeException $e) {
            // Log the error to SMS history if repository is available
            if ($this->smsHistoryRepository !== null) {
                $smsHistory = new SMSHistory(
                    null,
                    $originalNumber,
                    $message,
                    'FAILED',
                    $this->senderAddress,
                    $this->senderName,
                    null,
                    null,
                    'Failed to obtain access token: ' . $e->getMessage()
                );
                $this->smsHistoryRepository->save($smsHistory);
            }
            throw $e;
        }

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

            // Log the error to SMS history if repository is available
            if ($this->smsHistoryRepository !== null) {
                $smsHistory = new SMSHistory(
                    null,
                    $originalNumber,
                    $message,
                    'FAILED',
                    $this->senderAddress,
                    $this->senderName,
                    null,
                    null,
                    $errorMessage
                );
                $this->smsHistoryRepository->save($smsHistory);
            }

            throw new \RuntimeException($errorMessage);
        }
        curl_close($ch);

        $responseData = json_decode($response, true);

        // Check if the response indicates success
        $isSuccess = $httpCode >= 200 && $httpCode < 300 && isset($responseData['outboundSMSMessageRequest']);

        // Get message ID if available
        $messageId = null;
        if ($isSuccess && isset($responseData['outboundSMSMessageRequest']['resourceURL'])) {
            // Extract message ID from resourceURL if possible
            $resourceUrl = $responseData['outboundSMSMessageRequest']['resourceURL'];
            $messageId = substr($resourceUrl, strrpos($resourceUrl, '/') + 1);
        }

        // Log to SMS history if repository is available
        if ($this->smsHistoryRepository !== null) {
            // Find phone number ID if possible
            $phoneNumberId = null;
            if ($this->phoneNumberRepository !== null) {
                $phoneNumber = $this->phoneNumberRepository->findByNumber($originalNumber);
                if ($phoneNumber !== null) {
                    $phoneNumberId = $phoneNumber->getId();
                }
            }

            $smsHistory = new SMSHistory(
                null,
                $originalNumber,
                $message,
                $isSuccess ? 'SENT' : 'FAILED',
                $this->senderAddress,
                $this->senderName,
                $phoneNumberId,
                $messageId,
                $isSuccess ? null : 'API Error: ' . ($httpCode . ' - ' . json_encode($responseData))
            );
            $this->smsHistoryRepository->save($smsHistory);
        }

        return $responseData;
    }

    /**
     * Send an SMS to multiple phone numbers
     * 
     * @param array $receiverNumbers Array of receiver phone numbers
     * @param string $message SMS message
     * @return array Results for each number
     */
    public function sendBulkSMS(array $receiverNumbers, string $message): array
    {
        $results = [];
        $segmentId = null;

        foreach ($receiverNumbers as $number) {
            try {
                $results[$number] = [
                    'status' => 'success',
                    'response' => $this->sendSMS($number, $message)
                ];
            } catch (\Exception $e) {
                $results[$number] = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Send an SMS to all phone numbers in a segment
     * 
     * @param int $segmentId Segment ID
     * @param string $message SMS message
     * @return array Results for each number
     * @throws \RuntimeException If the repositories are not provided
     */
    public function sendSMSToSegment(int $segmentId, string $message): array
    {
        if ($this->phoneNumberRepository === null || $this->customSegmentRepository === null) {
            throw new \RuntimeException('Phone number and custom segment repositories are required for sending to a segment');
        }

        // Get the segment
        $segment = $this->customSegmentRepository->findById($segmentId);
        if ($segment === null) {
            throw new \RuntimeException('Segment not found: ' . $segmentId);
        }

        // Get all phone numbers in the segment
        $phoneNumbers = $this->phoneNumberRepository->findByCustomSegment($segmentId);

        // Extract the phone numbers
        $numbers = array_map(function ($phoneNumber) {
            return $phoneNumber->getNumber();
        }, $phoneNumbers);

        // Send the SMS to all numbers
        $results = $this->sendBulkSMS($numbers, $message);

        // Update segment ID in SMS history records if repository is available
        if ($this->smsHistoryRepository !== null) {
            // This would be more efficient with a batch update query
            // but we'll use the repository interface for now
            foreach ($numbers as $number) {
                $originalNumber = preg_replace('/^tel:/', '', $this->normalizePhoneNumber($number));
                $history = $this->smsHistoryRepository->findByPhoneNumber($originalNumber, 1);
                if (!empty($history)) {
                    $latestHistory = $history[0];
                    $latestHistory->setSegmentId($segmentId);
                    $this->smsHistoryRepository->save($latestHistory);
                }
            }
        }

        return $results;
    }

    /**
     * Normalize a phone number to the format required by the Orange API
     * 
     * @param string $number Phone number
     * @return string Normalized phone number
     */
    private function normalizePhoneNumber(string $number): string
    {
        // Remove any non-numeric characters except the leading +
        $number = preg_replace('/[^0-9+]/', '', $number);

        // Handle different formats
        if (substr($number, 0, 1) === '+') {
            // Format: +2250777104936 - already in international format
            $normalizedNumber = $number;
        } elseif (substr($number, 0, 4) === '0022') {
            // Format: 002250777104936 - convert to +225...
            $normalizedNumber = '+' . substr($number, 3);
        } elseif (substr($number, 0, 1) === '0') {
            // Format: 0777104936 - convert to +225...
            $normalizedNumber = '+225' . $number;
        } else {
            // If none of the above, assume it's already normalized or invalid
            $normalizedNumber = $number;
        }

        // Add the 'tel:' prefix required by the Orange API
        return 'tel:' . $normalizedNumber;
    }
}

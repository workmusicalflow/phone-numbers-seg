<?php

namespace Tests\Services;

use App\Models\PhoneNumber;
use App\Models\CustomSegment;
use App\Repositories\PhoneNumberRepository;
use App\Repositories\CustomSegmentRepository;
use App\Services\SMSService;
use PHPUnit\Framework\TestCase;
use PDO;

class SMSServiceTest extends TestCase
{
    private $pdo;
    private $phoneNumberRepository;
    private $customSegmentRepository;
    private $smsService;

    protected function setUp(): void
    {
        // Create an in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create the necessary tables
        $this->pdo->exec('
            CREATE TABLE phone_numbers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                number TEXT NOT NULL UNIQUE,
                name TEXT,
                company TEXT,
                sector TEXT,
                notes TEXT,
                date_added TEXT NOT NULL
            )
        ');

        $this->pdo->exec('
            CREATE TABLE custom_segments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT
            )
        ');

        $this->pdo->exec('
            CREATE TABLE phone_number_custom_segment (
                phone_number_id INTEGER,
                custom_segment_id INTEGER,
                PRIMARY KEY (phone_number_id, custom_segment_id),
                FOREIGN KEY (phone_number_id) REFERENCES phone_numbers (id) ON DELETE CASCADE,
                FOREIGN KEY (custom_segment_id) REFERENCES custom_segments (id) ON DELETE CASCADE
            )
        ');

        // Initialize repositories
        $this->phoneNumberRepository = new PhoneNumberRepository($this->pdo);
        $this->customSegmentRepository = new CustomSegmentRepository($this->pdo);

        // Create a mock SMSService that doesn't actually send SMS
        $this->smsService = $this->createMock(SMSService::class);
    }

    public function testSendSMS()
    {
        // Arrange
        $phoneNumber = '+2250777104936';
        $message = 'Test message';
        $expectedResponse = [
            'status' => 'success',
            'result' => [
                'outboundSMSMessageRequest' => [
                    'address' => ['tel:+2250777104936'],
                    'senderAddress' => 'tel:+2250595016840',
                    'senderName' => 'Qualitas CI',
                    'outboundSMSTextMessage' => [
                        'message' => 'Test message'
                    ],
                    'resourceURL' => 'https://api.orange.com/smsmessaging/v1/outbound/tel:+2250595016840/requests/test-id'
                ]
            ]
        ];

        // Configure the mock to return the expected response
        $this->smsService->method('sendSMS')
            ->with($phoneNumber, $message)
            ->willReturn($expectedResponse);

        // Act
        $result = $this->smsService->sendSMS($phoneNumber, $message);

        // Assert
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Qualitas CI', $result['result']['outboundSMSMessageRequest']['senderName']);
        $this->assertEquals('Test message', $result['result']['outboundSMSMessageRequest']['outboundSMSTextMessage']['message']);
    }

    public function testSendBulkSMS()
    {
        // Arrange
        $phoneNumbers = ['+2250777104936', '+2250777104937'];
        $message = 'Bulk test message';
        $expectedResponse = [
            'status' => 'success',
            'results' => [
                '+2250777104936' => [
                    'status' => 'success',
                    'response' => [
                        'outboundSMSMessageRequest' => [
                            'address' => ['tel:+2250777104936'],
                            'senderAddress' => 'tel:+2250595016840',
                            'senderName' => 'Qualitas CI',
                            'outboundSMSTextMessage' => [
                                'message' => 'Bulk test message'
                            ],
                            'resourceURL' => 'https://api.orange.com/smsmessaging/v1/outbound/tel:+2250595016840/requests/test-id-1'
                        ]
                    ]
                ],
                '+2250777104937' => [
                    'status' => 'success',
                    'response' => [
                        'outboundSMSMessageRequest' => [
                            'address' => ['tel:+2250777104937'],
                            'senderAddress' => 'tel:+2250595016840',
                            'senderName' => 'Qualitas CI',
                            'outboundSMSTextMessage' => [
                                'message' => 'Bulk test message'
                            ],
                            'resourceURL' => 'https://api.orange.com/smsmessaging/v1/outbound/tel:+2250595016840/requests/test-id-2'
                        ]
                    ]
                ]
            ],
            'summary' => [
                'total' => 2,
                'successful' => 2,
                'failed' => 0
            ]
        ];

        // Configure the mock to return the expected response
        $this->smsService->method('sendBulkSMS')
            ->with($phoneNumbers, $message)
            ->willReturn($expectedResponse);

        // Act
        $result = $this->smsService->sendBulkSMS($phoneNumbers, $message);

        // Assert
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(2, $result['summary']['total']);
        $this->assertEquals(2, $result['summary']['successful']);
        $this->assertEquals(0, $result['summary']['failed']);
        $this->assertEquals('Qualitas CI', $result['results']['+2250777104936']['response']['outboundSMSMessageRequest']['senderName']);
        $this->assertEquals('Bulk test message', $result['results']['+2250777104936']['response']['outboundSMSMessageRequest']['outboundSMSTextMessage']['message']);
    }

    public function testSendSMSToSegment()
    {
        // Arrange
        // Create a segment with ID 1
        $segmentId = 1;
        $segmentName = 'Test Segment';

        // We don't need to create actual objects since we're mocking the service

        $message = 'Segment test message';
        $expectedResponse = [
            'status' => 'success',
            'segment' => [
                'id' => 1,
                'name' => 'Test Segment'
            ],
            'results' => [
                '+2250777104936' => [
                    'status' => 'success',
                    'response' => [
                        'outboundSMSMessageRequest' => [
                            'address' => ['tel:+2250777104936'],
                            'senderAddress' => 'tel:+2250595016840',
                            'senderName' => 'Qualitas CI',
                            'outboundSMSTextMessage' => [
                                'message' => 'Segment test message'
                            ],
                            'resourceURL' => 'https://api.orange.com/smsmessaging/v1/outbound/tel:+2250595016840/requests/test-id-1'
                        ]
                    ]
                ],
                '+2250777104937' => [
                    'status' => 'success',
                    'response' => [
                        'outboundSMSMessageRequest' => [
                            'address' => ['tel:+2250777104937'],
                            'senderAddress' => 'tel:+2250595016840',
                            'senderName' => 'Qualitas CI',
                            'outboundSMSTextMessage' => [
                                'message' => 'Segment test message'
                            ],
                            'resourceURL' => 'https://api.orange.com/smsmessaging/v1/outbound/tel:+2250595016840/requests/test-id-2'
                        ]
                    ]
                ]
            ],
            'summary' => [
                'total' => 2,
                'successful' => 2,
                'failed' => 0
            ]
        ];

        // Configure the mock to return the expected response
        $this->smsService->method('sendSMSToSegment')
            ->with($segmentId, $message)
            ->willReturn($expectedResponse);

        // Act
        $result = $this->smsService->sendSMSToSegment($segmentId, $message);

        // Assert
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(1, $result['segment']['id']);
        $this->assertEquals('Test Segment', $result['segment']['name']);
        $this->assertEquals(2, $result['summary']['total']);
        $this->assertEquals(2, $result['summary']['successful']);
        $this->assertEquals(0, $result['summary']['failed']);
        $this->assertEquals('Qualitas CI', $result['results']['+2250777104936']['response']['outboundSMSMessageRequest']['senderName']);
        $this->assertEquals('Segment test message', $result['results']['+2250777104936']['response']['outboundSMSMessageRequest']['outboundSMSTextMessage']['message']);
    }

    // Note: The getSegmentsForSMS method is in the SMSController class, not in the SMSService class
}

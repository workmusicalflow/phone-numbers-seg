<?php

namespace Tests\Controllers;

use App\Controllers\SMSController;
use App\Services\SMSService;
use PHPUnit\Framework\TestCase;
use PDO;

class SMSControllerTest extends TestCase
{
    private $pdo;
    private $smsService;
    private $smsController;

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

        // Create a mock SMSService
        $this->smsService = $this->createMock(SMSService::class);

        // Initialize the controller with the mock service
        $this->smsController = new SMSController($this->pdo);

        // Use reflection to replace the SMSService in the controller with our mock
        $reflection = new \ReflectionClass($this->smsController);
        $property = $reflection->getProperty('smsService');
        $property->setAccessible(true);
        $property->setValue($this->smsController, $this->smsService);
    }

    public function testGetSegmentsForSMS()
    {
        // Arrange
        $expectedResponse = [
            'status' => 'success',
            'segments' => [
                [
                    'id' => 1,
                    'name' => 'VIP Clients',
                    'description' => 'High-value clients with priority support',
                    'phoneNumberCount' => 5
                ],
                [
                    'id' => 2,
                    'name' => 'Entreprises',
                    'description' => 'Business clients',
                    'phoneNumberCount' => 10
                ]
            ]
        ];

        // Configure the mock to return the expected response
        $this->smsService->method('getSegmentsForSMS')
            ->willReturn($expectedResponse);

        // Act
        $result = $this->smsController->getSegmentsForSMS();

        // Assert
        $this->assertEquals('success', $result['status']);
        $this->assertCount(2, $result['segments']);
        $this->assertEquals('VIP Clients', $result['segments'][0]['name']);
        $this->assertEquals(5, $result['segments'][0]['phoneNumberCount']);
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
                    'senderName' => '225HBC',
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
        $result = $this->smsController->sendSMS($phoneNumber, $message);

        // Assert
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('225HBC', $result['result']['outboundSMSMessageRequest']['senderName']);
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
                            'senderName' => '225HBC',
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
                            'senderName' => '225HBC',
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
        $result = $this->smsController->sendBulkSMS($phoneNumbers, $message);

        // Assert
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(2, $result['summary']['total']);
        $this->assertEquals(2, $result['summary']['successful']);
        $this->assertEquals(0, $result['summary']['failed']);
    }

    public function testSendSMSToSegment()
    {
        // Arrange
        $segmentId = 1;
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
                            'senderName' => '225HBC',
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
                            'senderName' => '225HBC',
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
        $result = $this->smsController->sendSMSToSegment($segmentId, $message);

        // Assert
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(1, $result['segment']['id']);
        $this->assertEquals('Test Segment', $result['segment']['name']);
        $this->assertEquals(2, $result['summary']['total']);
        $this->assertEquals(2, $result['summary']['successful']);
        $this->assertEquals(0, $result['summary']['failed']);
    }
}

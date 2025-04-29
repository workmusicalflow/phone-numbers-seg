<?php

namespace Tests\GraphQL\Resolvers;

use App\Entities\Contact;
use App\Entities\SMSHistory;
use App\GraphQL\Resolvers\ContactResolver;
use App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface;
use App\Repositories\Interfaces\ContactGroupRepositoryInterface;
use App\Repositories\Interfaces\ContactRepositoryInterface;
use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use App\Services\Interfaces\AuthServiceInterface;
use App\GraphQL\Formatters\GraphQLFormatterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use App\GraphQL\DataLoaders\ContactGroupDataLoader;
use App\Models\User;

class ContactSMSHistoryResolverTest extends TestCase
{
    private ContactResolver $resolver;
    private $contactRepositoryMock;
    private $smsHistoryRepositoryMock;
    private $authServiceMock;
    private $formatterMock;
    private $loggerMock;
    
    public function setUp(): void
    {
        $this->contactRepositoryMock = $this->createMock(ContactRepositoryInterface::class);
        $this->contactGroupRepositoryMock = $this->createMock(ContactGroupRepositoryInterface::class);
        $this->membershipRepositoryMock = $this->createMock(ContactGroupMembershipRepositoryInterface::class);
        $this->smsHistoryRepositoryMock = $this->createMock(SMSHistoryRepositoryInterface::class);
        $this->authServiceMock = $this->createMock(AuthServiceInterface::class);
        $this->formatterMock = $this->createMock(GraphQLFormatterInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->dataLoaderMock = $this->createMock(ContactGroupDataLoader::class);
        
        $this->resolver = new ContactResolver(
            $this->contactRepositoryMock,
            $this->contactGroupRepositoryMock,
            $this->membershipRepositoryMock,
            $this->authServiceMock,
            $this->formatterMock,
            $this->loggerMock,
            $this->dataLoaderMock,
            $this->smsHistoryRepositoryMock
        );
    }
    
    public function testResolveSmsHistoryReturnsFormattedSmsHistory(): void
    {
        // Create a contact array as it would be passed to the resolver
        $contactArray = [
            'id' => 1,
            'phoneNumber' => '+2251234567890'
        ];
        
        // Create a mock user
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        
        // Create a mock Contact entity
        $contactEntity = $this->createMock(Contact::class);
        $contactEntity->method('getUserId')->willReturn(1);
        
        // Create mock SMS history entries
        $smsHistory1 = $this->createMock(SMSHistory::class);
        $smsHistory2 = $this->createMock(SMSHistory::class);
        
        // Configure the auth service to return the user
        $this->authServiceMock->method('getCurrentUser')->willReturn($user);
        
        // Configure the contact repository to return the contact
        $this->contactRepositoryMock->method('findById')->with(1)->willReturn($contactEntity);
        
        // Configure the SMS history repository to return the SMS history entries
        $this->smsHistoryRepositoryMock->method('findByPhoneNumber')
            ->with('+2251234567890', 100, 0)
            ->willReturn([$smsHistory1, $smsHistory2]);
        
        // Configure the formatter to return formatted SMS history entries
        $this->formatterMock->method('formatSMSHistory')
            ->willReturnCallback(function($sms) {
                static $id = 1;
                return [
                    'id' => $id++,
                    'phoneNumber' => '+2251234567890',
                    'message' => 'Test message',
                    'status' => 'SENT',
                    'createdAt' => '2023-01-01T12:00:00Z'
                ];
            });
        
        // Call the resolver
        $result = $this->resolver->resolveSmsHistory($contactArray, [], null);
        
        // Assert the result
        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals(2, $result[1]['id']);
        $this->assertEquals('+2251234567890', $result[0]['phoneNumber']);
        $this->assertEquals('SENT', $result[0]['status']);
    }
    
    public function testResolveSmsTotalCountReturnsCorrectCount(): void
    {
        // Create a contact array
        $contactArray = [
            'id' => 1,
            'phoneNumber' => '+2251234567890'
        ];
        
        // Create a mock user
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        
        // Configure the auth service to return the user
        $this->authServiceMock->method('getCurrentUser')->willReturn($user);
        
        // Configure the SMS history repository to return the count
        $this->smsHistoryRepositoryMock->method('countByPhoneNumber')
            ->with('+2251234567890')
            ->willReturn(5);
        
        // Call the resolver
        $result = $this->resolver->resolveSmsTotalCount($contactArray);
        
        // Assert the result
        $this->assertEquals(5, $result);
    }
    
    public function testResolveSmsSentCountReturnsCorrectCount(): void
    {
        // Create a contact array
        $contactArray = [
            'id' => 1,
            'phoneNumber' => '+2251234567890'
        ];
        
        // Create a mock user
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        
        // Configure the auth service to return the user
        $this->authServiceMock->method('getCurrentUser')->willReturn($user);
        
        // Configure the SMS history repository to return the count
        $this->smsHistoryRepositoryMock->method('countByPhoneNumberAndStatus')
            ->with('+2251234567890', 'SENT')
            ->willReturn(3);
        
        // Call the resolver
        $result = $this->resolver->resolveSmsSentCount($contactArray);
        
        // Assert the result
        $this->assertEquals(3, $result);
    }
    
    public function testResolveSmsFailedCountReturnsCorrectCount(): void
    {
        // Create a contact array
        $contactArray = [
            'id' => 1,
            'phoneNumber' => '+2251234567890'
        ];
        
        // Create a mock user
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        
        // Configure the auth service to return the user
        $this->authServiceMock->method('getCurrentUser')->willReturn($user);
        
        // Configure the SMS history repository to return the count
        $this->smsHistoryRepositoryMock->method('countByPhoneNumberAndStatus')
            ->with('+2251234567890', 'FAILED')
            ->willReturn(2);
        
        // Call the resolver
        $result = $this->resolver->resolveSmsFailedCount($contactArray);
        
        // Assert the result
        $this->assertEquals(2, $result);
    }
    
    public function testResolveSmsScoreCalculatesCorrectScore(): void
    {
        // Create a contact array
        $contactArray = [
            'id' => 1,
            'phoneNumber' => '+2251234567890'
        ];
        
        // Create a mock user
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        
        // Configure the auth service to return the user
        $this->authServiceMock->method('getCurrentUser')->willReturn($user);
        
        // Configure the SMS history repository to return the counts
        $this->smsHistoryRepositoryMock->method('countByPhoneNumber')
            ->with('+2251234567890')
            ->willReturn(5);
            
        $this->smsHistoryRepositoryMock->method('countByPhoneNumberAndStatus')
            ->with('+2251234567890', 'SENT')
            ->willReturn(3);
        
        // Call the resolver
        $result = $this->resolver->resolveSmsScore($contactArray);
        
        // Assert the result (3/5 = 0.6)
        $this->assertEquals(0.6, $result);
    }
    
    public function testResolveSmsScoreReturnsZeroWhenNoSMS(): void
    {
        // Create a contact array
        $contactArray = [
            'id' => 1,
            'phoneNumber' => '+2251234567890'
        ];
        
        // Create a mock user
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        
        // Configure the auth service to return the user
        $this->authServiceMock->method('getCurrentUser')->willReturn($user);
        
        // Configure the SMS history repository to return zero
        $this->smsHistoryRepositoryMock->method('countByPhoneNumber')
            ->with('+2251234567890')
            ->willReturn(0);
        
        // Call the resolver
        $result = $this->resolver->resolveSmsScore($contactArray);
        
        // Assert the result (0 when no SMS)
        $this->assertEquals(0.0, $result);
    }
}
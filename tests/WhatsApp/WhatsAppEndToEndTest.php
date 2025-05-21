<?php

namespace Tests\WhatsApp;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use App\Entities\User;
use App\Entities\PhoneNumber;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Services\WhatsApp\WhatsAppService;
use App\Services\WhatsApp\WhatsAppApiClient;
use App\Services\WhatsApp\WhatsAppTemplateService;
use App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppMessageHistoryRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;

/**
 * Tests de bout en bout pour le flux complet WhatsApp
 * 
 * Ces tests vérifient l'intégration des différents composants pour simuler 
 * le flux complet d'envoi de messages WhatsApp, de la demande initiale
 * jusqu'à la réponse et l'enregistrement dans l'historique.
 */
class WhatsAppEndToEndTest extends TestCase
{
    /**
     * @var MockHandler
     */
    private MockHandler $mockHandler;
    
    /**
     * @var array
     */
    private array $requestHistory = [];
    
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    
    /**
     * @var WhatsAppApiClientInterface
     */
    private WhatsAppApiClientInterface $apiClient;
    
    /**
     * @var WhatsAppMessageHistoryRepositoryInterface
     */
    private WhatsAppMessageHistoryRepositoryInterface $messageHistoryRepository;
    
    /**
     * @var WhatsAppTemplateRepositoryInterface
     */
    private WhatsAppTemplateRepositoryInterface $templateRepository;
    
    /**
     * @var WhatsAppService
     */
    private WhatsAppService $whatsAppService;
    
    /**
     * Configuration commune pour les tests
     */
    protected function setUp(): void
    {
        // Réinitialiser le handler et l'historique pour chaque test
        $this->mockHandler = new MockHandler();
        $this->requestHistory = [];
        
        // Créer un logger de test qui ne fait rien
        $this->logger = $this->createMock(LoggerInterface::class);
        
        // Configurer le client mock avec l'historique des requêtes
        $container = [];
        $history = Middleware::history($container);
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push($history);
        $httpClient = new Client(['handler' => $handlerStack]);
        $this->requestHistory = &$container;
        
        // Configurer les mocks pour les repositories
        $this->messageHistoryRepository = $this->createMock(WhatsAppMessageHistoryRepositoryInterface::class);
        $this->templateRepository = $this->createMock(WhatsAppTemplateRepositoryInterface::class);
        
        // Configuration WhatsApp API
        $config = [
            'base_url' => 'https://graph.facebook.com/',
            'api_version' => 'v19.0',
            'access_token' => 'mock_token',
            'phone_number_id' => 'mock_phone_id',
            'whatsapp_business_account_id' => 'mock_waba_id'
        ];
        
        // Créer l'API client mock
        $this->apiClient = $this->createMock(WhatsAppApiClientInterface::class);
        
        // Configurer le service WhatsApp pour les tests
        $serviceConfig = [
            'webhook_verify_token' => 'mock_verify_token',
            'default_language' => 'fr'
        ];
        
        $this->whatsAppService = new WhatsAppService(
            $this->apiClient,
            $this->messageHistoryRepository,
            $this->templateRepository,
            $this->logger,
            $serviceConfig
        );
    }
    
    /**
     * Crée un utilisateur mocké pour les tests
     */
    private function createMockUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(123);
        // Ne pas utiliser getName() car il n'existe pas dans l'entity User
        return $user;
    }
    
    /**
     * Crée un numéro de téléphone mocké pour les tests
     */
    private function createMockPhoneNumber(): PhoneNumber
    {
        $phoneNumber = $this->createMock(PhoneNumber::class);
        $phoneNumber->method('getId')->willReturn(456);
        $phoneNumber->method('getNumber')->willReturn('+33612345678');
        $phoneNumber->method('getCountryCode')->willReturn('33');
        $phoneNumber->method('getFormatted')->willReturn('+33 6 12 34 56 78');
        return $phoneNumber;
    }
    
    /**
     * Configure le repository pour sauvegarder les messages
     */
    private function configureMessageHistorySaving()
    {
        $this->messageHistoryRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function($messageHistory) {
                // Ne pas essayer de définir l'ID car la méthode setId() n'existe pas
                return $messageHistory;
            });
    }
    
    /**
     * Test de bout en bout pour l'envoi d'un message texte simple
     */
    public function testSendTextMessageEndToEnd(): void
    {
        // 1. Préparer les données de test
        $user = $this->createMockUser();
        $recipientNumber = '+33612345678';
        $messageText = "Ceci est un message de test";
        
        // 2. Configurer les mocks
        
        // 2.1 Configurer la réponse de l'API
        $apiResponse = [
            'messaging_product' => 'whatsapp',
            'contacts' => [
                [
                    'input' => '+33612345678',
                    'wa_id' => '33612345678'
                ]
            ],
            'messages' => [
                [
                    'id' => 'wamid.abcdef123456789'
                ]
            ]
        ];
        
        $this->apiClient->expects($this->once())
            ->method('sendMessage')
            ->willReturn($apiResponse);
        
        // 2.2 Configurer le repository pour sauvegarder les messages
        $this->configureMessageHistorySaving();
        
        // 3. Exécuter l'opération d'envoi
        $result = $this->whatsAppService->sendTextMessage(
            $user,
            $recipientNumber,
            $messageText
        );
        
        // 4. Vérifications
        $this->assertIsArray($result);
        $this->assertEquals('wamid.abcdef123456789', $result['messages'][0]['id']);
    }
    
    /**
     * Test de bout en bout pour l'envoi d'un message template
     * 
     * @group skip
     */
    public function testSendTemplateMessageEndToEnd(): void
    {
        // 1. Préparer les données de test
        $user = $this->createMockUser();
        $recipientNumber = '+33612345678';
        $templateName = "confirmation_commande";
        $language = "fr";
        $components = [
            [
                "type" => "header",
                "parameters" => [
                    [
                        "type" => "text",
                        "text" => "123456"
                    ]
                ]
            ],
            [
                "type" => "body",
                "parameters" => [
                    [
                        "type" => "text",
                        "text" => "John Doe"
                    ],
                    [
                        "type" => "text",
                        "text" => "25/05/2025"
                    ]
                ]
            ]
        ];
        
        // 2. Configurer les mocks
        
        // 2.1 Configurer la réponse de l'API
        $apiResponse = [
            'messaging_product' => 'whatsapp',
            'contacts' => [
                [
                    'input' => '+33612345678',
                    'wa_id' => '33612345678'
                ]
            ],
            'messages' => [
                [
                    'id' => 'wamid.abcdef123456789'
                ]
            ]
        ];
        
        $this->apiClient->expects($this->once())
            ->method('sendMessage')
            ->willReturn($apiResponse);
        
        // 2.2 Configurer le repository pour sauvegarder les messages
        $this->configureMessageHistorySaving();
        
        // 2.3 Configurer le repository de templates
        // La méthode findOneBy est utilisée dans le service, donc on doit la configurer
        $this->templateRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);
        
        // 3. Exécuter l'opération d'envoi
        $result = $this->whatsAppService->sendTemplateMessageWithComponents(
            $user,
            $recipientNumber,
            $templateName,
            $language,
            $components
        );
        
        // 4. Vérifications
        $this->assertIsArray($result);
        $this->assertEquals('wamid.abcdef123456789', $result['messages'][0]['id']);
    }
    
    /**
     * Test de bout en bout pour l'envoi d'un message média
     */
    public function testSendMediaMessageEndToEnd(): void
    {
        // 1. Préparer les données de test
        $user = $this->createMockUser();
        $recipientNumber = '+33612345678';
        $mediaType = 'image';
        $mediaId = '123456789';
        $caption = 'Une image de test';
        
        // 2. Configurer les mocks
        
        // 2.1 Configurer la réponse de l'API
        $apiResponse = [
            'messaging_product' => 'whatsapp',
            'contacts' => [
                [
                    'input' => '+33612345678',
                    'wa_id' => '33612345678'
                ]
            ],
            'messages' => [
                [
                    'id' => 'wamid.abcdef123456789'
                ]
            ]
        ];
        
        $this->apiClient->expects($this->once())
            ->method('sendMessage')
            ->willReturn($apiResponse);
        
        // 2.2 Configurer le repository pour sauvegarder les messages
        $this->configureMessageHistorySaving();
        
        // 3. Exécuter l'opération d'envoi
        $result = $this->whatsAppService->sendMediaMessage(
            $user,
            $recipientNumber,
            $mediaType,
            $mediaId,
            $caption
        );
        
        // 4. Vérifications
        $this->assertIsArray($result);
        $this->assertEquals('wamid.abcdef123456789', $result['messages'][0]['id']);
    }
    
    /**
     * Test de bout en bout pour la gestion des erreurs lors de l'envoi
     */
    public function testHandleApiErrorDuringMessageSend(): void
    {
        // 1. Préparer les données de test
        $user = $this->createMockUser();
        $recipientNumber = '+33612345678';
        $messageText = "Ceci est un message de test";
        
        // 2. Configurer les mocks pour simuler une erreur
        
        // 2.1 Simuler une erreur de l'API
        $this->apiClient->expects($this->once())
            ->method('sendMessage')
            ->willThrowException(new \Exception('Recipient phone number not valid'));
        
        // 3. Exécuter l'opération d'envoi qui va échouer
        try {
            $result = $this->whatsAppService->sendTextMessage(
                $user,
                $recipientNumber,
                $messageText
            );
            $this->fail('Une exception aurait dû être lancée');
        } catch (\Exception $e) {
            // 4. Vérifications
            $this->assertStringContainsString('Recipient phone number not valid', $e->getMessage());
        }
    }
    
    /**
     * Test de bout en bout pour la vérification du statut des messages
     */
    public function testProcessWebhookStatusUpdate(): void
    {
        // 1. Préparer les données de test pour le webhook
        $webhookData = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '+33612345678',
                                    'phone_number_id' => 'mock_phone_id'
                                ],
                                'statuses' => [
                                    [
                                        'id' => 'wamid.abcdef123456789',
                                        'status' => 'delivered',
                                        'timestamp' => '1621234567',
                                        'recipient_id' => '33612345678'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        // 2. Créer un mock de l'objet WhatsAppMessageHistory
        $mockMessageHistory = $this->createMock(WhatsAppMessageHistory::class);
        // L'ID est géré par Doctrine, pas besoin de setId
        $mockMessageHistory->method('getWabaMessageId')->willReturn('wamid.abcdef123456789');
        $mockMessageHistory->method('getStatus')->willReturn('sent');
        
        // Configurer le setStatus pour être appeléed avec 'delivered'
        $mockMessageHistory->expects($this->once())
            ->method('setStatus')
            ->with('delivered');
        
        // 3. Configurer le mock repository pour trouver et sauvegarder le message
        $this->messageHistoryRepository->expects($this->any())
            ->method('findOneBy')
            ->with(['wabaMessageId' => 'wamid.abcdef123456789'])
            ->willReturn($mockMessageHistory);
        
        $this->messageHistoryRepository->expects($this->once())
            ->method('save')
            ->with($mockMessageHistory)
            ->willReturn($mockMessageHistory);
        
        // 4. Exécuter le traitement du webhook
        $this->whatsAppService->processWebhook($webhookData);
        
        // Pas besoin de vérification explicite ici, les mocks vérifient déjà les appels
    }
    
    /**
     * Test de bout en bout pour la récupération de l'historique des messages
     */
    public function testGetMessageHistory(): void
    {
        // 1. Préparer les données de test
        $user = $this->createMockUser();
        $status = null;
        $limit = 100;
        $offset = 0;
        
        // 2. Configurer le mock pour la recherche des messages
        $mockMessages = [];
        for ($i = 1; $i <= 5; $i++) {
            $messageHistory = $this->createMock(WhatsAppMessageHistory::class);
            // Configurer les méthodes attendues
            $messageHistory->method('getId')->willReturn($i);
            $messageHistory->method('getWabaMessageId')->willReturn('wamid.test' . $i);
            $messageHistory->method('getContent')->willReturn('Message de test ' . $i);
            $messageHistory->method('getType')->willReturn('text');
            $messageHistory->method('getStatus')->willReturn(['sent', 'delivered', 'read', 'failed', 'pending'][$i-1]);
            $messageHistory->method('getTimestamp')->willReturn(new \DateTime('2025-05-' . (5 + $i)));
            
            $mockMessages[] = $messageHistory;
        }
        
        $this->messageHistoryRepository->expects($this->once())
            ->method('findBy')
            ->with(
                ['oracle_user_id' => 123],
                ['timestamp' => 'DESC'],
                $limit,
                $offset
            )
            ->willReturn($mockMessages);
        
        // 3. Exécuter la recherche
        $result = $this->whatsAppService->getMessageHistory(
            $user,
            null,
            $status,
            $limit,
            $offset
        );
        
        // 4. Vérifications
        $this->assertCount(5, $result);
        $this->assertEquals('Message de test 1', $result[0]->getContent());
        $this->assertEquals('wamid.test5', $result[4]->getWabaMessageId());
        
        // Vérifier que les statuts sont différents
        $statuses = array_map(function($msg) { return $msg->getStatus(); }, $result);
        $this->assertEquals(['sent', 'delivered', 'read', 'failed', 'pending'], $statuses);
    }
}
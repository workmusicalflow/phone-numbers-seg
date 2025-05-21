<?php

namespace Tests\Fixtures;

use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppApiMetric;
use App\Entities\WhatsApp\WhatsAppMessageHistory;
use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Entities\WhatsApp\WhatsAppTemplateHistory;
use Tests\Fixtures\MockUser;

/**
 * Fixtures for WhatsApp tests
 */
class WhatsAppFixtures
{
    /**
     * Create test user
     * 
     * @param int $id
     * @param string $username
     * @return User
     */
    public static function createTestUser(int $id = 1, string $username = 'test_user'): User
    {
        $user = new MockUser();
        $user->setId($id);
        $user->setUsername($username);
        $user->setEmail($username . '@example.com');
        
        return $user;
    }
    
    /**
     * Create test templates
     * 
     * @return array
     */
    public static function createTestTemplates(): array
    {
        $templates = [];
        
        // Template 1: marketing template in French
        $template1 = new WhatsAppTemplate();
        $template1->setMetaTemplateId('marketing_promo_fr');
        $template1->setName('Promotion Marketing');
        $template1->setLanguage('fr');
        $template1->setCategory('MARKETING');
        $template1->setStatus('APPROVED');
        $template1->setBodyText('Bonjour {{1}}, découvrez notre offre spéciale: {{2}}% de réduction sur {{3}}!');
        $template1->setBodyVariablesCount(3);
        $template1->setComponentsFromArray([
            [
                'type' => 'BODY',
                'text' => 'Bonjour {{1}}, découvrez notre offre spéciale: {{2}}% de réduction sur {{3}}!'
            ]
        ]);
        
        $templates[] = $template1;
        
        // Template 2: utility template in French
        $template2 = new WhatsAppTemplate();
        $template2->setMetaTemplateId('appointment_reminder_fr');
        $template2->setName('Rappel de Rendez-vous');
        $template2->setLanguage('fr');
        $template2->setCategory('UTILITY');
        $template2->setStatus('APPROVED');
        $template2->setBodyText('Rappel: Votre rendez-vous est confirmé pour le {{1}} à {{2}}. Répondez OUI pour confirmer ou NON pour annuler.');
        $template2->setBodyVariablesCount(2);
        $template2->setComponentsFromArray([
            [
                'type' => 'BODY',
                'text' => 'Rappel: Votre rendez-vous est confirmé pour le {{1}} à {{2}}. Répondez OUI pour confirmer ou NON pour annuler.'
            ]
        ]);
        
        $templates[] = $template2;
        
        // Template 3: utility template in English
        $template3 = new WhatsAppTemplate();
        $template3->setMetaTemplateId('appointment_reminder_en');
        $template3->setName('Appointment Reminder');
        $template3->setLanguage('en');
        $template3->setCategory('UTILITY');
        $template3->setStatus('APPROVED');
        $template3->setBodyText('Reminder: Your appointment is confirmed for {{1}} at {{2}}. Reply YES to confirm or NO to cancel.');
        $template3->setBodyVariablesCount(2);
        $template3->setComponentsFromArray([
            [
                'type' => 'BODY',
                'text' => 'Reminder: Your appointment is confirmed for {{1}} at {{2}}. Reply YES to confirm or NO to cancel.'
            ]
        ]);
        
        $templates[] = $template3;
        
        return $templates;
    }
    
    /**
     * Create test template history entries
     * 
     * @param User $user
     * @param array $templates
     * @return array
     */
    public static function createTestTemplateHistory(User $user, array $templates): array
    {
        $history = [];
        $now = new \DateTime();
        
        // Template usage 1: marketing template
        $history1 = new WhatsAppTemplateHistory();
        $history1->setOracleUser($user);
        $history1->setTemplateId($templates[0]->getMetaTemplateId());
        $history1->setTemplateName($templates[0]->getName());
        $history1->setTemplate($templates[0]);
        $history1->setLanguage($templates[0]->getLanguage());
        $history1->setCategory($templates[0]->getCategory());
        $history1->setPhoneNumber('22501020304');
        $history1->setParameters(['Jean', '20', 'nos produits']);
        $history1->setStatus('sent');
        $history1->setUsedAt(clone $now);
        
        $history[] = $history1;
        
        // Template usage 2: appointment reminder FR
        $history2 = new WhatsAppTemplateHistory();
        $history2->setOracleUser($user);
        $history2->setTemplateId($templates[1]->getMetaTemplateId());
        $history2->setTemplateName($templates[1]->getName());
        $history2->setTemplate($templates[1]);
        $history2->setLanguage($templates[1]->getLanguage());
        $history2->setCategory($templates[1]->getCategory());
        $history2->setPhoneNumber('22505060708');
        $history2->setParameters(['15 mai', '14h30']);
        $history2->setStatus('delivered');
        $history2->setUsedAt((clone $now)->modify('-1 day'));
        
        $history[] = $history2;
        
        // Template usage 3: appointment reminder EN
        $history3 = new WhatsAppTemplateHistory();
        $history3->setOracleUser($user);
        $history3->setTemplateId($templates[2]->getMetaTemplateId());
        $history3->setTemplateName($templates[2]->getName());
        $history3->setTemplate($templates[2]);
        $history3->setLanguage($templates[2]->getLanguage());
        $history3->setCategory($templates[2]->getCategory());
        $history3->setPhoneNumber('22509080706');
        $history3->setParameters(['May 16th', '2:30pm']);
        $history3->setStatus('failed');
        $history3->setUsedAt((clone $now)->modify('-2 days'));
        
        $history[] = $history3;
        
        return $history;
    }
    
    /**
     * Create test message history entries
     * 
     * @param User $user
     * @return array
     */
    public static function createTestMessageHistory(User $user): array
    {
        $messages = [];
        $now = new \DateTime();
        
        // Successful message
        $message1 = new WhatsAppMessageHistory();
        $message1->setOracleUser($user);
        $message1->setWabaMessageId('wamid.1234567890');
        $message1->setPhoneNumber('22501020304');
        $message1->setDirection('OUTGOING');
        $message1->setType('template');
        $message1->setContent('{"template":"marketing_promo_fr","language":"fr"}');
        $message1->setStatus('sent');
        $message1->setTimestamp(clone $now);
        
        $messages[] = $message1;
        
        // Delivered message
        $message2 = new WhatsAppMessageHistory();
        $message2->setOracleUser($user);
        $message2->setWabaMessageId('wamid.0987654321');
        $message2->setPhoneNumber('22505060708');
        $message2->setDirection('OUTGOING');
        $message2->setType('template');
        $message2->setContent('{"template":"appointment_reminder_fr","language":"fr"}');
        $message2->setStatus('delivered');
        $message2->setTimestamp((clone $now)->modify('-1 day'));
        
        $messages[] = $message2;
        
        // Failed message
        $message3 = new WhatsAppMessageHistory();
        $message3->setOracleUser($user);
        $message3->setWabaMessageId('wamid.1122334455');
        $message3->setPhoneNumber('22509080706');
        $message3->setDirection('OUTGOING');
        $message3->setType('template');
        $message3->setContent('{"template":"appointment_reminder_en","language":"en"}');
        $message3->setStatus('failed');
        $message3->setErrorCode('500');
        $message3->setErrorMessage('Internal server error');
        $message3->setTimestamp((clone $now)->modify('-2 days'));
        
        $messages[] = $message3;
        
        return $messages;
    }
    
    /**
     * Create test API metrics
     * 
     * @param User $user
     * @return array
     */
    public static function createTestApiMetrics(User $user): array
    {
        $metrics = [];
        $now = new \DateTime();
        
        // Successful API call: getApprovedTemplates
        $metric1 = new WhatsAppApiMetric();
        $metric1->setUserId($user->getId());
        $metric1->setOperation('getApprovedTemplates');
        $metric1->setDuration(125.5);
        $metric1->setSuccess(true);
        $metric1->setCreatedAt(clone $now);
        
        $metrics[] = $metric1;
        
        // Successful API call: getTemplateById
        $metric2 = new WhatsAppApiMetric();
        $metric2->setUserId($user->getId());
        $metric2->setOperation('getTemplateById');
        $metric2->setDuration(84.2);
        $metric2->setSuccess(true);
        $metric2->setCreatedAt((clone $now)->modify('-1 hour'));
        
        $metrics[] = $metric2;
        
        // Failed API call: getApprovedTemplates
        $metric3 = new WhatsAppApiMetric();
        $metric3->setUserId($user->getId());
        $metric3->setOperation('getApprovedTemplates');
        $metric3->setDuration(2500.0);
        $metric3->setSuccess(false);
        $metric3->setErrorMessage('Request timed out after 2500ms');
        $metric3->setCreatedAt((clone $now)->modify('-2 hours'));
        
        $metrics[] = $metric3;
        
        // Failed API call with network error
        $metric4 = new WhatsAppApiMetric();
        $metric4->setUserId($user->getId());
        $metric4->setOperation('getTemplateById');
        $metric4->setDuration(0);
        $metric4->setSuccess(false);
        $metric4->setErrorMessage('Could not connect to host');
        $metric4->setCreatedAt((clone $now)->modify('-1 day'));
        
        $metrics[] = $metric4;
        
        return $metrics;
    }
    
    /**
     * Create sample API responses for testing
     * 
     * @return array
     */
    public static function createSampleApiResponses(): array
    {
        return [
            // Successful templates response
            'GET /api.php?endpoint=whatsapp/templates/approved' => [
                'statusCode' => 200,
                'body' => json_encode([
                    'status' => 'success',
                    'templates' => self::getSampleTemplatesResponse(),
                    'meta' => [
                        'source' => 'api',
                        'usedFallback' => false
                    ],
                    'count' => 3
                ])
            ],
            
            // Template by ID response
            'GET /api.php?endpoint=whatsapp/templates/marketing_promo_fr' => [
                'statusCode' => 200,
                'body' => json_encode([
                    'status' => 'success',
                    'template' => self::getSampleTemplatesResponse()[0],
                    'meta' => [
                        'source' => 'api',
                        'usedFallback' => false
                    ]
                ])
            ],
            
            // Error response
            'GET /api.php?endpoint=whatsapp/templates/non_existent' => [
                'statusCode' => 404,
                'body' => json_encode([
                    'status' => 'error',
                    'message' => 'Template not found'
                ])
            ],
            
            // Timeout error will be simulated in tests
            
            // Fallback response
            'GET /api.php?endpoint=whatsapp/templates/approved&use_cache=1' => [
                'statusCode' => 200,
                'body' => json_encode([
                    'status' => 'success',
                    'templates' => self::getSampleTemplatesResponse(),
                    'meta' => [
                        'source' => 'cache',
                        'usedFallback' => true
                    ],
                    'count' => 3
                ])
            ],
        ];
    }
    
    /**
     * Get sample templates response
     * 
     * @return array
     */
    public static function getSampleTemplatesResponse(): array
    {
        return [
            [
                'id' => 'marketing_promo_fr',
                'name' => 'Promotion Marketing',
                'language' => 'fr',
                'category' => 'MARKETING',
                'status' => 'APPROVED',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => 'Bonjour {{1}}, découvrez notre offre spéciale: {{2}}% de réduction sur {{3}}!'
                    ]
                ],
                'bodyVariablesCount' => 3,
                'hasMediaHeader' => false,
                'hasButtons' => false,
                'buttonsCount' => 0,
                'hasFooter' => false
            ],
            [
                'id' => 'appointment_reminder_fr',
                'name' => 'Rappel de Rendez-vous',
                'language' => 'fr',
                'category' => 'UTILITY',
                'status' => 'APPROVED',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => 'Rappel: Votre rendez-vous est confirmé pour le {{1}} à {{2}}. Répondez OUI pour confirmer ou NON pour annuler.'
                    ]
                ],
                'bodyVariablesCount' => 2,
                'hasMediaHeader' => false,
                'hasButtons' => false,
                'buttonsCount' => 0,
                'hasFooter' => false
            ],
            [
                'id' => 'appointment_reminder_en',
                'name' => 'Appointment Reminder',
                'language' => 'en',
                'category' => 'UTILITY',
                'status' => 'APPROVED',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => 'Reminder: Your appointment is confirmed for {{1}} at {{2}}. Reply YES to confirm or NO to cancel.'
                    ]
                ],
                'bodyVariablesCount' => 2,
                'hasMediaHeader' => false,
                'hasButtons' => false,
                'buttonsCount' => 0,
                'hasFooter' => false
            ]
        ];
    }
}
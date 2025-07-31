<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;
use App\Services\Interfaces\AuthServiceInterface;

/**
 * Test d'intégration basique pour l'envoi en masse WhatsApp
 * 
 * MVP : On teste juste que ça marche de bout en bout
 */
class BulkSendBasicTest extends TestCase
{
    private string $apiUrl = '/api/whatsapp/bulk-send.php';
    
    public function testBulkSendRequiresAuthentication(): void
    {
        // Sans token
        $response = $this->postJson($this->apiUrl, [
            'recipients' => ['+22501234567'],
            'templateName' => 'hello_world'
        ]);
        
        $this->assertEquals(401, $response['status'] ?? 401);
    }
    
    public function testBulkSendWithValidDataWorks(): void
    {
        // Créer un utilisateur de test
        $user = $this->createTestUser();
        $token = $this->generateTestToken($user);
        
        // Données de test
        $data = [
            'recipients' => ['+22501234567', '+22507654321'],
            'templateName' => 'hello_world',
            'defaultParameters' => [
                'bodyParams' => ['name' => 'Test User']
            ]
        ];
        
        // Envoyer la requête
        $response = $this->postJson($this->apiUrl, $data, [
            'Authorization' => "Bearer $token"
        ]);
        
        // Vérifications basiques
        $this->assertEquals(200, $response['status'] ?? 500);
        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('data', $response);
        
        // Le handler devrait traiter 2 destinataires
        if (isset($response['data']['totalAttempted'])) {
            $this->assertEquals(2, $response['data']['totalAttempted']);
        }
    }
    
    public function testBulkSendRespectsMaxLimit(): void
    {
        $user = $this->createTestUser();
        $token = $this->generateTestToken($user);
        
        // Créer 501 destinataires (au-dessus de la limite)
        $recipients = [];
        for ($i = 1; $i <= 501; $i++) {
            $recipients[] = sprintf('+22501%06d', $i);
        }
        
        $response = $this->postJson($this->apiUrl, [
            'recipients' => $recipients,
            'templateName' => 'hello_world'
        ], [
            'Authorization' => "Bearer $token"
        ]);
        
        // Devrait retourner une erreur
        $this->assertNotEquals(200, $response['status'] ?? 200);
        $this->assertStringContainsString('limite', $response['error'] ?? $response['message'] ?? '');
    }
    
    /**
     * Helper pour créer un utilisateur de test
     */
    private function createTestUser(): \App\Entities\User
    {
        $user = new \App\Entities\User();
        $user->setUsername('test_bulk_' . uniqid());
        $user->setPassword(password_hash('test123', PASSWORD_DEFAULT));
        $user->setSmsCredit(100);
        
        // Sauvegarder via le repository
        $container = require __DIR__ . '/../../src/bootstrap-rest.php';
        $userRepo = $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class);
        $userRepo->save($user);
        
        return $user;
    }
    
    /**
     * Helper pour générer un token de test
     */
    private function generateTestToken(\App\Entities\User $user): string
    {
        $container = require __DIR__ . '/../../src/bootstrap-rest.php';
        $authService = $container->get(AuthServiceInterface::class);
        return $authService->generateToken($user);
    }
    
    /**
     * Helper pour faire une requête POST JSON
     */
    private function postJson(string $url, array $data, array $headers = []): array
    {
        $baseUrl = $_ENV['API_BASE_URL'] ?? 'http://localhost:8000';
        $fullUrl = $baseUrl . $url;
        
        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
            'Content-Type: application/json'
        ], array_map(fn($k, $v) => "$k: $v", array_keys($headers), $headers)));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $decoded = json_decode($response, true) ?? [];
        $decoded['status'] = $httpCode;
        
        return $decoded;
    }
}
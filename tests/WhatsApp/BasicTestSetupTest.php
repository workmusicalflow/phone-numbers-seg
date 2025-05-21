<?php

namespace Tests\WhatsApp;

use Tests\TestCase;

/**
 * Test de base pour vérifier que la configuration des tests fonctionne
 */
class BasicTestSetupTest extends TestCase
{
    /**
     * Test simple pour vérifier que PHPUnit fonctionne
     */
    public function testBasicSetup(): void
    {
        $this->assertTrue(true);
    }
    
    /**
     * Test que l'environnement de test est correctement configuré
     */
    public function testEnvironment(): void
    {
        $this->assertEquals('test', $_ENV['APP_ENV']);
    }
    
    /**
     * Test que le container est correctement configuré
     */
    public function testContainer(): void
    {
        $this->assertNotNull(static::$container);
    }
}
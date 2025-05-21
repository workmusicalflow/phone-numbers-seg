<?php
/**
 * Configuration de dépendances d'urgence
 */

use Psr\Log\LoggerInterface;

return [
    // Contrôleur d'urgence pour les templates WhatsApp
    'App\\GraphQL\\Controllers\\WhatsApp\\WhatsAppEmergencyController' => \DI\create('App\\GraphQL\\Controllers\\WhatsApp\\WhatsAppEmergencyController')
        ->constructor(
            \DI\get(LoggerInterface::class)
        )
];
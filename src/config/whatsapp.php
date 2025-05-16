<?php

/**
 * Configuration pour l'intégration WhatsApp Business API
 * Utilise les variables d'environnement pour la sécurité
 */
return [
    // Informations d'identification de l'API Meta
    'app_id' => $_ENV['WHATSAPP_APP_ID'] ?? null,
    'phone_number_id' => $_ENV['WHATSAPP_PHONE_NUMBER_ID'] ?? null,
    'whatsapp_business_account_id' => $_ENV['WHATSAPP_WABA_ID'] ?? null,
    'api_version' => $_ENV['WHATSAPP_API_VERSION'] ?? 'v22.0',
    
    // Token d'accès Meta (doit être défini dans l'environnement)
    'access_token' => $_ENV['WHATSAPP_ACCESS_TOKEN'] ?? $_ENV['WHATSAPP_API_TOKEN'] ?? null,
    
    // Sécurité du Webhook
    'webhook_verify_token' => $_ENV['WHATSAPP_WEBHOOK_VERIFY_TOKEN'] ?? 'oracle_whatsapp_webhook_verification_token',
    'webhook_callback_url' => $_ENV['WHATSAPP_WEBHOOK_CALLBACK_URL'] ?? null,
    
    // Configuration de l'implémentation
    'log_incoming_messages' => filter_var($_ENV['WHATSAPP_LOG_INCOMING_MESSAGES'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'auto_mark_as_read' => filter_var($_ENV['WHATSAPP_AUTO_MARK_AS_READ'] ?? true, FILTER_VALIDATE_BOOLEAN),
    
    // URL de base pour l'API Meta Graph
    'base_url' => 'https://graph.facebook.com/',
];

<?php

/**
 * Configuration pour l'intégration WhatsApp Business API
 * Utilise les variables d'environnement pour la sécurité
 */

// Charger les variables d'environnement si ce n'est pas déjà fait
if (file_exists(__DIR__ . '/../../.env') && !isset($_ENV['WHATSAPP_API_TOKEN']) && !isset($_ENV['WHATSAPP_ACCESS_TOKEN'])) {
    if (class_exists('\Dotenv\Dotenv')) {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
    }
}

// Fonction utilitaire pour récupérer les variables d'environnement depuis différentes sources
function get_env_var($name, $default = null) {
    if (isset($_ENV[$name])) {
        return $_ENV[$name];
    }
    
    if (isset($_SERVER[$name])) {
        return $_SERVER[$name];
    }
    
    $value = getenv($name);
    if ($value !== false) {
        return $value;
    }
    
    return $default;
}

// Configuration complète avec valeurs hardcodées de secours depuis .env
return [
    // Informations d'identification de l'API Meta
    'app_id' => get_env_var('WHATSAPP_APP_ID', '1193922949108494'),
    'phone_number_id' => get_env_var('WHATSAPP_PHONE_NUMBER_ID', '660953787095211'),
    'whatsapp_business_account_id' => get_env_var('WHATSAPP_WABA_ID', '664409593123173'),
    'api_version' => get_env_var('WHATSAPP_API_VERSION', 'v22.0'),
    
    // Token d'accès Meta (doit être défini dans l'environnement)
    'access_token' => get_env_var('WHATSAPP_ACCESS_TOKEN', get_env_var('WHATSAPP_API_TOKEN', 'EAAQ93dlFUw4BOZCu6OPmzQuo47pE8eYgGCJLWaQzeyHo03ZCmUWNOQZABt0NeJgVfx9zgurvJc3YynNmFZBgfsCslzydmfzdWZA3onZCyGQsgSo1ZAC6o7ZCgzukF10wmeCjfWcWItPeOw0hanzT0V5ShOIQZCEzVF9qP2aGALaD5ZCTvy95DhjlUwOwijVNAEXpGzEG0YKIsRI8ZCngj9BiXLltt3azinQQYgPBIs9bZA6K')),
    
    // Sécurité du Webhook
    'webhook_verify_token' => get_env_var('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'oracle_whatsapp_webhook_verification_token'),
    'webhook_callback_url' => get_env_var('WHATSAPP_WEBHOOK_CALLBACK_URL'),
    
    // Configuration de l'implémentation
    'log_incoming_messages' => filter_var(get_env_var('WHATSAPP_LOG_INCOMING_MESSAGES', 'true'), FILTER_VALIDATE_BOOLEAN),
    'auto_mark_as_read' => filter_var(get_env_var('WHATSAPP_AUTO_MARK_AS_READ', 'true'), FILTER_VALIDATE_BOOLEAN),
    
    // URL de base pour l'API Meta Graph
    'base_url' => 'https://graph.facebook.com/',
    
    // Timeouts
    'connect_timeout' => 5,    // Timeout de connexion en secondes
    'request_timeout' => 30,   // Timeout de requête en secondes
    
    // Cache et fallbacks
    'use_local_cache' => true,
    'cache_ttl' => 3600,      // 1 heure
    'use_fallback_templates' => true,
];

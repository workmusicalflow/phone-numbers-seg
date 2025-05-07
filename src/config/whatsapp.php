<?php

/**
 * Configuration pour l'intégration WhatsApp Business API
 */
return [
    // Informations d'identification de l'API Meta
    'app_id' => '1193922949108494',
    'phone_number_id' => '660953787095211',
    'whatsapp_business_account_id' => '664409593123173',
    'api_version' => 'v22.0',

    // Token d'accès Meta (à actualiser tous les 60 jours)
    'access_token' => 'EAAQ93dlFUw4BOZCu6OPmzQuo47pE8eYgGCJLWaQzeyHo03ZCmUWNOQZABt0NeJgVfx9zgurvJc3YynNmFZBgfsCslzydmfzdWZA3onZCyGQsgSo1ZAC6o7ZCgzukF10wmeCjfWcWItPeOw0hanzT0V5ShOIQZCEzVF9qP2aGALaD5ZCTvy95DhjlUwOwijVNAEXpGzEG0YKIsRI8ZCngj9BiXLltt3azinQQYgPBIs9bZA6K', // À remplacer par le jeton d'accès valide

    // Sécurité du Webhook
    'webhook_verify_token' => 'oracle_whatsapp_webhook_verification_token', // Token pour la vérification du webhook

    // Configuration de l'implémentation
    'log_incoming_messages' => true, // Logger les messages entrants (développement)
    'auto_mark_as_read' => true, // Marquer automatiquement les messages comme lus

    // Templates disponibles
    'templates' => [
        'hello_world' => [
            'name' => 'hello_world',
            'language' => 'fr',
            'components' => []
        ],
        'qshe_invitation1' => [
            'name' => 'qshe_invitation1',
            'language' => 'fr',
            'components' => [
                [
                    'type' => 'header',
                    'parameters' => [
                        [
                            'type' => 'image',
                            'image' => [
                                'link' => 'https://events-qualitas-ci.com/public/images/banner/QSHEf2025-1024.jpg'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
];

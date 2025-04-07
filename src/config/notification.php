<?php

/**
 * Configuration des notifications
 */
return [
    /**
     * Configuration des notifications en temps réel
     */
    'realtime' => [
        // Driver de diffusion (pusher, redis, log)
        'broadcast_driver' => 'log',

        // Préfixe pour les canaux utilisateur
        'user_channel_prefix' => 'user-',

        // Canal pour les administrateurs
        'admin_channel' => 'admin-notifications',

        // Configuration Pusher
        'pusher' => [
            'app_id' => $_ENV['PUSHER_APP_ID'] ?? null,
            'app_key' => $_ENV['PUSHER_APP_KEY'] ?? null,
            'app_secret' => $_ENV['PUSHER_APP_SECRET'] ?? null,
            'cluster' => $_ENV['PUSHER_APP_CLUSTER'] ?? 'eu',
            'encrypted' => true,
        ],

        // Configuration Redis
        'redis' => [
            'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['REDIS_PORT'] ?? 6379,
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
        ],
    ],

    /**
     * Configuration des notifications par email
     */
    'email' => [
        // Activer les notifications par email
        'enabled' => true,

        // Template par défaut
        'default_template' => 'default',

        // Répertoire des templates
        'templates_dir' => __DIR__ . '/../../templates/emails',
    ],

    /**
     * Configuration des notifications par SMS
     */
    'sms' => [
        // Activer les notifications par SMS
        'enabled' => true,

        // Template par défaut
        'default_template' => 'default',

        // Répertoire des templates
        'templates_dir' => __DIR__ . '/../../templates/sms',
    ],

    /**
     * Configuration des événements administratifs
     */
    'admin' => [
        // Approbation d'un nom d'expéditeur
        'sender_name_approval' => [
            'channels' => ['realtime', 'email'],
            'email_template' => 'sender_name_approved',
            'sms_template' => 'sender_name_approved',
        ],

        // Complétion d'une commande
        'order_completion' => [
            'channels' => ['realtime', 'email'],
            'email_template' => 'order_confirmation',
            'sms_template' => 'order_confirmation',
        ],

        // Ajout de crédits
        'credit_added' => [
            'channels' => ['realtime', 'email'],
            'email_template' => 'credit_added',
            'sms_template' => 'credit_added',
        ],
    ],

    /**
     * Configuration de la journalisation des erreurs
     */
    'error_logging' => [
        // Activer la journalisation des erreurs
        'enabled' => true,

        // Niveau de log minimum (debug, info, warning, error, critical)
        'log_level' => 'error',

        // Inclure la trace des exceptions
        'include_trace' => true,

        // Notifier l'administrateur en cas d'erreur
        'notify_admin' => true,

        // Email de l'administrateur
        'admin_email' => $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com',
    ],

    /**
     * Configuration des notifications de tableau de bord
     */
    'dashboard' => [
        // Nombre de notifications à afficher
        'max_notifications' => 10,

        // Types de notifications à afficher
        'notification_types' => [
            'sender_name_approval',
            'order_completion',
            'credit_added',
            'error',
        ],

        // Durée de rafraîchissement en secondes
        'refresh_interval' => 60,
    ],
];

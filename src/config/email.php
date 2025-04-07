<?php

/**
 * Configuration pour le service d'email
 */
return [
    // Paramètres SMTP
    'smtp' => [
        'host' => 'mail.topdigitalevel.site',
        'auth' => true,
        'username' => 'info@topdigitalevel.site',
        'password' => 'undPzZ3x3U',
        'secure' => 'tls',
        'port' => 587,
    ],

    // Paramètres d'expéditeur par défaut
    'from' => [
        'email' => 'info@topdigitalevel.site',
        'name' => 'Oracle SMS',
    ],

    // Répertoire des templates d'email
    'templates_dir' => __DIR__ . '/../../templates/emails',

    // Template par défaut
    'default_template' => 'default',

    // Paramètres de débogage
    'debug' => [
        'enabled' => false,
        'log_file' => __DIR__ . '/../../logs/email.log',
    ],
];

<?php

/**
 * Configuration des templates SMS
 */

return [
    // Chemins des templates SMS
    'templates' => [
        'credit_added' => __DIR__ . '/../../templates/sms/credit_added.php',
        'sender_name_approved' => __DIR__ . '/../../templates/sms/sender_name_approved.php',
        'order_confirmation' => __DIR__ . '/../../templates/sms/order_confirmation.php',
    ],

    // Configuration des notifications SMS
    'notifications' => [
        // Activer/désactiver les notifications SMS
        'enabled' => true,

        // Nom d'expéditeur par défaut pour les notifications
        'default_sender_name' => '225HBC',

        // Délai minimum entre deux notifications du même type (en secondes)
        'throttle' => 300, // 5 minutes
    ],
];

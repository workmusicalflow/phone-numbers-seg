<?php

/**
 * Template SMS pour confirmation de commande de crédits SMS
 * 
 * Variables disponibles:
 * - $username: Nom d'utilisateur
 * - $orderNumber: Numéro de la commande
 * - $quantity: Quantité de crédits SMS commandés
 * - $orderDate: Date de la commande
 */

return "ORACLE: Bonjour {$username}, votre commande #{$orderNumber} de {$quantity} crédits SMS a bien été enregistrée. Nous la traiterons dans les plus brefs délais. Merci de votre confiance!";

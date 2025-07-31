<?php

namespace App\Utils;

/**
 * Classe utilitaire pour gérer les en-têtes CORS (Cross-Origin Resource Sharing)
 *
 * Fournit des méthodes statiques pour configurer les en-têtes CORS
 * nécessaires aux API REST pour les requêtes cross-origin.
 *
 * @package App\Utils
 */
class CorsHelper
{
    /**
     * Active les en-têtes CORS pour permettre les requêtes cross-origin
     *
     * @param string $allowOrigin Domaines autorisés (default: '*')
     * @param string $allowMethods Méthodes HTTP autorisées 
     * @param string $allowHeaders En-têtes HTTP autorisés
     * @param int $maxAge Durée de validité du pre-flight (secondes)
     * @return void
     */
    public static function enableCors(
        string $allowOrigin = '*',
        string $allowMethods = 'GET, POST, PUT, DELETE, OPTIONS',
        string $allowHeaders = 'Content-Type, Authorization, X-Requested-With',
        int $maxAge = 86400
    ): void {
        // Domaines autorisés
        header("Access-Control-Allow-Origin: $allowOrigin");
        
        // Support des cookies pour les requêtes cross-origin spécifiques
        if ($allowOrigin !== '*') {
            header('Access-Control-Allow-Credentials: true');
        }
        
        // En-têtes autorisés
        header("Access-Control-Allow-Headers: $allowHeaders");
        
        // Méthodes HTTP autorisées
        header("Access-Control-Allow-Methods: $allowMethods");
        
        // Durée de mise en cache des résultats pre-flight
        header("Access-Control-Max-Age: $maxAge");
        
        // En-têtes exposés côté client
        header('Access-Control-Expose-Headers: Content-Length, X-JSON');
        
        // Si c'est une requête OPTIONS (pre-flight), terminer la réponse
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
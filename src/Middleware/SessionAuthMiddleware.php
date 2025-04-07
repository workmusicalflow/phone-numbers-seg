<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware pour l'authentification par session
 */
class SessionAuthMiddleware implements MiddlewareInterface
{
    /**
     * Traiter la requête
     * 
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Démarrer la session si elle n'est pas déjà démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Vérifier si l'utilisateur est authentifié
        if (isset($_SESSION['user_id'])) {
            // Ajouter les informations d'authentification à la requête
            $request = $request->withAttribute('auth', [
                'userId' => $_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? '',
                'isAdmin' => $_SESSION['is_admin'] ?? false,
            ]);
        }

        // Continuer le traitement de la requête
        return $handler->handle($request);
    }
}

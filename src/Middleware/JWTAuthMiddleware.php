<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

/**
 * Middleware pour l'authentification JWT
 */
class JWTAuthMiddleware implements MiddlewareInterface
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
        // Récupérer le token depuis l'en-tête Authorization
        $authHeader = $request->getHeaderLine('Authorization');
        $token = null;

        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }

        // Si aucun token n'est fourni, continuer sans authentification
        if (empty($token)) {
            return $handler->handle($request);
        }

        // Vérifier le token
        try {
            $secretKey = $_ENV['JWT_SECRET'] ?? 'default_secret_key_change_in_production';
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));

            // Ajouter les informations d'authentification à la requête
            $request = $request->withAttribute('auth', [
                'userId' => $decoded->userId,
                'username' => $decoded->username,
                'isAdmin' => $decoded->isAdmin ?? false,
                'exp' => $decoded->exp,
            ]);
        } catch (ExpiredException $e) {
            // Token expiré
            // On pourrait rediriger vers une page de connexion ou renvoyer une erreur
            // Pour l'instant, on continue sans authentification
        } catch (\Exception $e) {
            // Token invalide
            // On pourrait journaliser l'erreur
            // Pour l'instant, on continue sans authentification
        }

        // Continuer le traitement de la requête
        return $handler->handle($request);
    }
}

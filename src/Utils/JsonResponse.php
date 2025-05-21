<?php

namespace App\Utils;

/**
 * Classe utilitaire pour les réponses JSON des API REST
 *
 * Fournit des méthodes statiques pour générer des réponses JSON
 * cohérentes pour les API REST.
 *
 * @package App\Utils
 */
class JsonResponse
{
    /**
     * Envoie une réponse de succès au format JSON
     *
     * @param mixed $data Les données à renvoyer
     * @param int $statusCode Code HTTP (défaut: 200)
     * @return void
     */
    public static function success($data, int $statusCode = 200): void
    {
        self::send(['success' => true, ...(is_array($data) ? $data : ['data' => $data])], $statusCode);
    }

    /**
     * Envoie une réponse d'erreur au format JSON
     *
     * @param string $message Message d'erreur
     * @param int $statusCode Code HTTP (défaut: 400)
     * @param array $details Détails supplémentaires de l'erreur
     * @return void
     */
    public static function error(string $message, int $statusCode = 400, array $details = []): void
    {
        $response = [
            'success' => false,
            'error' => $message
        ];

        if (!empty($details)) {
            $response['details'] = $details;
        }

        self::send($response, $statusCode);
    }

    /**
     * Envoie une réponse JSON au client
     *
     * @param mixed $data Les données à encoder en JSON
     * @param int $statusCode Code HTTP
     * @return void
     */
    protected static function send($data, int $statusCode): void
    {
        // Nettoyer tout buffer de sortie existant
        if (ob_get_length()) {
            ob_clean();
        }

        // Définir les entêtes HTTP
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);

        // Encodage JSON avec options pour meilleure lisibilité
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if ($json === false) {
            // Erreur lors de l'encodage JSON, renvoyer une erreur simple
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur interne du serveur: impossible d\'encoder la réponse JSON',
                'jsonError' => json_last_error_msg()
            ]);
            exit;
        }

        echo $json;
        exit;
    }
}
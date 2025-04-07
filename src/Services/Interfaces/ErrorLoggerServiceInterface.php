<?php

namespace App\Services\Interfaces;

/**
 * Interface pour le service de journalisation des erreurs
 */
interface ErrorLoggerServiceInterface
{
    /**
     * Journalise une erreur
     *
     * @param string $message Message d'erreur
     * @param \Throwable $exception Exception
     * @param string $level Niveau de log (debug, info, warning, error, critical)
     * @param array $context Contexte supplémentaire
     * @param bool $notifyAdmin Si true, notifie l'administrateur
     * @return void
     */
    public function logError(
        string $message,
        \Throwable $exception,
        string $level = 'error',
        array $context = [],
        bool $notifyAdmin = false
    ): void;

    /**
     * Journalise une erreur d'API
     *
     * @param string $endpoint Point d'API
     * @param string $method Méthode HTTP
     * @param int $statusCode Code de statut HTTP
     * @param string $response Réponse de l'API
     * @param array $requestData Données de la requête
     * @param array $context Contexte supplémentaire
     * @return void
     */
    public function logApiError(
        string $endpoint,
        string $method,
        int $statusCode,
        string $response,
        array $requestData = [],
        array $context = []
    ): void;

    /**
     * Journalise une erreur de validation
     *
     * @param string $entity Entité concernée
     * @param array $errors Erreurs de validation
     * @param array $data Données soumises
     * @param array $context Contexte supplémentaire
     * @return void
     */
    public function logValidationError(
        string $entity,
        array $errors,
        array $data = [],
        array $context = []
    ): void;

    /**
     * Journalise une erreur d'accès
     *
     * @param string $resource Ressource concernée
     * @param string $action Action tentée
     * @param int $userId ID de l'utilisateur
     * @param array $context Contexte supplémentaire
     * @return void
     */
    public function logAccessError(
        string $resource,
        string $action,
        int $userId,
        array $context = []
    ): void;
}

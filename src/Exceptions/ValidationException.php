<?php

namespace App\Exceptions;

/**
 * Exception lancée lorsque la validation des données échoue
 */
class ValidationException extends \Exception
{
    /**
     * Tableau des erreurs de validation
     * 
     * @var array
     */
    private $errors;

    /**
     * Constructeur
     * 
     * @param string $message Message d'erreur
     * @param array $errors Tableau des erreurs de validation
     * @param int $code Code d'erreur
     * @param \Throwable|null $previous Exception précédente
     */
    public function __construct(string $message = "", array $errors = [], int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Récupère les erreurs de validation
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Vérifie si une erreur existe pour un champ donné
     * 
     * @param string $field Nom du champ
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Récupère l'erreur pour un champ donné
     * 
     * @param string $field Nom du champ
     * @return string|null
     */
    public function getError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Convertit les erreurs en tableau associatif pour une réponse API
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors
        ];
    }

    /**
     * Convertit les erreurs en JSON pour une réponse API
     * 
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}

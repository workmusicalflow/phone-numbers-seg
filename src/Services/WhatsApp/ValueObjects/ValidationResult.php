<?php

namespace App\Services\WhatsApp\ValueObjects;

/**
 * Résultat d'une validation
 * 
 * Value Object immutable représentant le résultat d'une validation
 */
class ValidationResult
{
    private bool $isValid;
    private array $errors;

    public function __construct(bool $isValid, array $errors = [])
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
    }

    /**
     * Vérifie si la validation a réussi
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Récupère les erreurs de validation
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Récupère les erreurs sous forme de chaîne
     */
    public function getErrorsAsString(string $separator = ', '): string
    {
        return implode($separator, $this->errors);
    }

    /**
     * Combine ce résultat avec un autre
     */
    public function andThen(ValidationResult $other): ValidationResult
    {
        if (!$this->isValid) {
            return $this;
        }
        
        return $other;
    }

    /**
     * Crée un résultat de succès
     */
    public static function success(): self
    {
        return new self(true, []);
    }

    /**
     * Crée un résultat d'échec
     */
    public static function failure(array $errors): self
    {
        return new self(false, $errors);
    }
}
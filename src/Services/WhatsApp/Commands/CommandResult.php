<?php

namespace App\Services\WhatsApp\Commands;

/**
 * Résultat de l'exécution d'une commande
 * 
 * Value Object immutable représentant le résultat d'une commande
 */
class CommandResult
{
    private bool $success;
    private ?string $message;
    private mixed $data;
    private array $errors;
    private array $metadata;

    public function __construct(
        bool $success,
        ?string $message = null,
        mixed $data = null,
        array $errors = [],
        array $metadata = []
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
        $this->errors = $errors;
        $this->metadata = $metadata;
    }

    /**
     * Crée un résultat de succès
     */
    public static function success(mixed $data = null, ?string $message = null, array $metadata = []): self
    {
        return new self(true, $message, $data, [], $metadata);
    }

    /**
     * Crée un résultat d'échec
     */
    public static function failure(string $message, array $errors = [], array $metadata = []): self
    {
        return new self(false, $message, null, $errors, $metadata);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Convertit le résultat en array pour la sérialisation
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'errors' => $this->errors,
            'metadata' => $this->metadata
        ];
    }
}
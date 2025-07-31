<?php

namespace App\Services\WhatsApp\Events;

/**
 * Classe abstraite de base pour tous les événements
 * 
 * Fournit l'implémentation commune pour les événements
 */
abstract class AbstractEvent implements EventInterface
{
    protected \DateTimeInterface $occurredAt;
    protected bool $propagate = true;
    protected array $metadata;

    public function __construct(array $metadata = [])
    {
        $this->occurredAt = new \DateTime();
        $this->metadata = array_merge([
            'event_id' => uniqid('evt_', true),
            'version' => '1.0'
        ], $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function getOccurredAt(): \DateTimeInterface
    {
        return $this->occurredAt;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldPropagate(): bool
    {
        return $this->propagate;
    }

    /**
     * {@inheritdoc}
     */
    public function stopPropagation(): void
    {
        $this->propagate = false;
    }

    /**
     * Récupère les métadonnées de l'événement
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Convertit l'événement en array pour la sérialisation
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'data' => $this->getData(),
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata
        ];
    }
}
<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Commands;

use App\Entities\User;

/**
 * Commande pour l'envoi en masse de messages template WhatsApp
 * 
 * Cette commande encapsule toutes les données nécessaires pour envoyer
 * un template WhatsApp à plusieurs destinataires en une seule opération.
 */
class BulkSendTemplateCommand implements CommandInterface
{
    /**
     * @param User $user L'utilisateur qui initie l'envoi
     * @param array<int, string> $recipients Liste des numéros de téléphone
     * @param string $templateName Nom du template à envoyer
     * @param string $templateLanguage Langue du template (défaut: fr)
     * @param array<int, string> $bodyVariables Variables pour le corps du message
     * @param array<int, string> $headerVariables Variables pour l'en-tête
     * @param string|null $headerMediaUrl URL du média d'en-tête
     * @param string|null $headerMediaId ID du média d'en-tête uploadé
     * @param array<string, mixed> $defaultParameters Paramètres par défaut pour le template (pour rétrocompatibilité)
     * @param array<string, array<string, mixed>> $recipientParameters Paramètres spécifiques par destinataire (clé = numéro)
     * @param array{batchSize?: int, delayBetweenBatches?: int, stopOnError?: bool} $options Options de traitement
     */
    public function __construct(
        private readonly User $user,
        private readonly array $recipients,
        private readonly string $templateName,
        private readonly string $templateLanguage = 'fr',
        private readonly array $bodyVariables = [],
        private readonly array $headerVariables = [],
        private readonly ?string $headerMediaUrl = null,
        private readonly ?string $headerMediaId = null,
        private readonly array $defaultParameters = [],
        private readonly array $recipientParameters = [],
        private readonly array $options = []
    ) {
        $this->validateRecipients();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return array<int, string>
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getRecipientCount(): int
    {
        return count($this->recipients);
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function getTemplateLanguage(): string
    {
        return $this->templateLanguage;
    }

    /**
     * @return array<int, string>
     */
    public function getBodyVariables(): array
    {
        return $this->bodyVariables;
    }

    /**
     * @return array<int, string>
     */
    public function getHeaderVariables(): array
    {
        return $this->headerVariables;
    }

    public function getHeaderMediaUrl(): ?string
    {
        return $this->headerMediaUrl;
    }

    public function getHeaderMediaId(): ?string
    {
        return $this->headerMediaId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultParameters(): array
    {
        return $this->defaultParameters;
    }

    /**
     * Obtient les paramètres pour un destinataire spécifique
     * 
     * @param string $recipient Numéro de téléphone
     * @return array<string, mixed>
     */
    public function getParametersForRecipient(string $recipient): array
    {
        return array_merge(
            $this->defaultParameters,
            $this->recipientParameters[$recipient] ?? []
        );
    }

    /**
     * @return array{batchSize: int, delayBetweenBatches: int, stopOnError: bool}
     */
    public function getOptions(): array
    {
        return array_merge([
            'batchSize' => 50,
            'delayBetweenBatches' => 1000, // milliseconds
            'stopOnError' => false
        ], $this->options);
    }

    public function getBatchSize(): int
    {
        return $this->getOptions()['batchSize'];
    }

    public function getDelayBetweenBatches(): int
    {
        return $this->getOptions()['delayBetweenBatches'];
    }

    public function shouldStopOnError(): bool
    {
        return $this->getOptions()['stopOnError'];
    }

    /**
     * Divise les destinataires en batches pour le traitement
     * 
     * @return array<int, array<int, string>>
     */
    public function getBatches(): array
    {
        return array_chunk($this->recipients, $this->getBatchSize());
    }

    private function validateRecipients(): void
    {
        if (empty($this->recipients)) {
            throw new \InvalidArgumentException('La liste des destinataires ne peut pas être vide');
        }

        foreach ($this->recipients as $recipient) {
            if (!is_string($recipient) || empty($recipient)) {
                throw new \InvalidArgumentException('Tous les destinataires doivent être des chaînes non vides');
            }
        }
    }

    /**
     * Exécute la commande
     * Note: Dans notre architecture, l'exécution est déléguée au handler via le CommandBus
     */
    public function execute(): CommandResult
    {
        throw new \RuntimeException('Cette commande doit être exécutée via le CommandBus');
    }

    /**
     * Vérifie si la commande peut être exécutée
     */
    public function canExecute(): bool
    {
        // Vérifier que l'utilisateur a assez de crédits
        $requiredCredits = $this->getRecipientCount();
        return $this->user->getSmsCredit() >= $requiredCredits;
    }

    /**
     * Récupère le nom de la commande pour le logging
     */
    public function getName(): string
    {
        return 'BulkSendTemplate';
    }

    /**
     * Récupère les métadonnées de la commande
     */
    public function getMetadata(): array
    {
        return [
            'userId' => $this->user->getId(),
            'userName' => $this->user->getUsername(),
            'template' => $this->templateName,
            'recipientCount' => $this->getRecipientCount(),
            'batchSize' => $this->getBatchSize(),
            'timestamp' => time()
        ];
    }
}
<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces\WhatsApp;

use App\Entities\WhatsApp\WhatsAppTemplateHistory;
use App\Repositories\Interfaces\RepositoryInterface;
use App\Entities\User;

interface WhatsAppTemplateHistoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Trouve l'historique des templates pour un utilisateur donné
     *
     * @param User $user L'utilisateur pour lequel récupérer l'historique
     * @param int|null $limit Limite du nombre de résultats
     * @param int|null $offset Point de départ des résultats
     * @return WhatsAppTemplateHistory[] L'historique des templates
     */
    public function findByUser(User $user, ?int $limit = null, ?int $offset = null): array;

    /**
     * Compte le nombre d'entrées dans l'historique pour un utilisateur donné
     * 
     * @param User $user L'utilisateur pour lequel compter l'historique
     * @return int Le nombre d'entrées
     */
    public function countByUser(User $user): int;

    /**
     * Trouve l'historique des templates pour un utilisateur donné avec des filtres avancés
     *
     * @param User $user L'utilisateur pour lequel récupérer l'historique
     * @param array $criteria Critères de filtrage (templateId, phoneNumber, etc.)
     * @param array $orderBy Critères de tri (usedAt, createdAt, etc.)
     * @param int|null $limit Limite du nombre de résultats
     * @param int|null $offset Point de départ des résultats
     * @return WhatsAppTemplateHistory[] L'historique des templates
     */
    public function findByUserWithFilters(
        User $user,
        array $criteria = [],
        array $orderBy = ['usedAt' => 'DESC'],
        ?int $limit = null,
        ?int $offset = null
    ): array;

    /**
     * Trouve l'historique pour un template spécifique
     *
     * @param string $templateId L'ID du template
     * @param User|null $user L'utilisateur (optionnel)
     * @param int|null $limit Limite du nombre de résultats
     * @return WhatsAppTemplateHistory[] L'historique du template
     */
    public function findByTemplateId(string $templateId, ?User $user = null, ?int $limit = null): array;

    /**
     * Récupère les templates les plus utilisés par un utilisateur
     *
     * @param User $user L'utilisateur
     * @param int $limit Nombre maximum de templates à récupérer
     * @return array Un tableau associatif avec les IDs de templates et leur nombre d'utilisation
     */
    public function getMostUsedTemplates(User $user, int $limit = 5): array;

    /**
     * Récupère les valeurs de paramètres souvent utilisées pour un template spécifique
     *
     * @param string $templateId L'ID du template
     * @param User $user L'utilisateur
     * @return array Un tableau avec les valeurs les plus couramment utilisées
     */
    public function getCommonParameterValues(string $templateId, User $user): array;
}
<?php

declare(strict_types=1);

namespace App\Services\Interfaces\WhatsApp;

use App\Entities\User;

/**
 * Interface pour le service de gestion des templates WhatsApp
 * 
 * Cette interface définit les méthodes pour l'approche de "chargement direct" des templates WhatsApp.
 * L'approche de chargement direct consiste à récupérer les templates directement depuis l'API Meta 
 * (Cloud API) sans synchronisation avec une base de données locale. Cela garantit que les templates 
 * sont toujours à jour avec les dernières modifications ou approbations de Meta.
 * 
 * Avantages de cette approche:
 * - Toujours à jour avec les dernières modifications/approbations de Meta
 * - Pas besoin de gérer la synchronisation ou le stockage local des templates
 * - Plus simple à maintenir et à déployer
 * 
 * Inconvénients:
 * - Dépend de la disponibilité de l'API Meta
 * - Consommation de quota d'API à chaque requête
 */
interface WhatsAppTemplateServiceInterface
{
    /**
     * Récupère les templates approuvés directement depuis l'API Meta
     * Sans stockage local (approche chargement direct)
     *
     * @param array $filters Filtres optionnels (name, language, category)
     * @return array Templates approuvés
     */
    public function fetchApprovedTemplatesFromMeta(array $filters = []): array;

    /**
     * Récupère les templates pour un utilisateur spécifique
     * En utilisant l'approche de chargement direct depuis Meta
     *
     * @param User $user
     * @param array $filters Filtres optionnels (name, language, category)
     * @return array
     */
    public function getUserTemplates(User $user, array $filters = []): array;

    /**
     * Récupère les catégories de templates disponibles
     *
     * @return array Liste des catégories
     */
    public function getTemplateCategories(): array;

    /**
     * Récupère les langues disponibles pour les templates
     *
     * @return array Liste des langues
     */
    public function getTemplateLanguages(): array;

    /**
     * Récupère un template spécifique par son nom et sa langue
     *
     * @param string $templateName
     * @param string $languageCode
     * @return array|null
     */
    public function getTemplate(string $templateName, string $languageCode): ?array;

    /**
     * Construit le payload de composants pour un template à partir des données dynamiques
     *
     * @param array $templateComponentsFromMeta Structure des composants du template depuis Meta
     * @param array $templateDynamicData Données dynamiques pour personnaliser le template
     * @return array Structure de composants pour l'API
     */
    public function buildTemplateComponents(array $templateComponentsFromMeta, array $templateDynamicData): array;
}
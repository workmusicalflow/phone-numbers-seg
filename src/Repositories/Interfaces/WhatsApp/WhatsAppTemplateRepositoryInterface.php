<?php

namespace App\Repositories\Interfaces\WhatsApp;

use App\Entities\WhatsApp\WhatsAppTemplate;

/**
 * Interface pour le repository des templates WhatsApp
 */
interface WhatsAppTemplateRepositoryInterface
{
    /**
     * Sauvegarder un template
     * 
     * @param mixed $template
     * @return mixed
     */
    public function save($template);
    
    /**
     * Trouver un template par nom Meta et langue
     * 
     * @param string $metaTemplateName
     * @param string $languageCode
     * @return WhatsAppTemplate|null
     */
    public function findByMetaNameAndLanguage(string $metaTemplateName, string $languageCode): ?WhatsAppTemplate;
    
    /**
     * Obtenir tous les templates approuvés
     * 
     * @param string|null $category
     * @param string|null $languageCode
     * @return WhatsAppTemplate[]
     */
    public function findApproved(?string $category = null, ?string $languageCode = null): array;
    
    /**
     * Obtenir tous les templates par catégorie
     * 
     * @param string $category
     * @return WhatsAppTemplate[]
     */
    public function findByCategory(string $category): array;
    
    /**
     * Obtenir tous les templates par langue
     * 
     * @param string $languageCode
     * @return WhatsAppTemplate[]
     */
    public function findByLanguage(string $languageCode): array;
    
    /**
     * Mettre à jour le statut d'un template
     * 
     * @param int $templateId
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $templateId, string $status): bool;
    
    /**
     * Supprimer un template par ID
     * 
     * @param mixed $templateId
     * @return bool
     */
    public function deleteById($templateId): bool;
    
    /**
     * Compter les templates par statut
     * 
     * @return array
     */
    public function countByStatus(): array;
    
    /**
     * Recherche avancée de templates avec filtrage multiple
     * 
     * @param array $criteria Critères de recherche (status, category, language, hasHeaderMedia, etc.)
     * @param array $orderBy Tri des résultats (ex: ['name' => 'ASC'])
     * @param int|null $limit Nombre maximum de résultats
     * @param int|null $offset Position de départ
     * @return array
     */
    public function findByAdvancedCriteria(array $criteria, array $orderBy = [], ?int $limit = null, ?int $offset = null): array;
    
    /**
     * Trouver tous les templates approuvés avec options de filtrage
     * 
     * @param array $filters Filtres à appliquer (name, language, category, etc.)
     * @return array Liste des templates approuvés filtrés
     */
    public function findApprovedTemplates(array $filters = []): array;
    
    /**
     * Recherche les templates par format d'en-tête
     * 
     * @param string $headerFormat Format d'en-tête (TEXT, IMAGE, VIDEO, DOCUMENT)
     * @param string|null $status Statut du template (optionnel)
     * @return array
     */
    public function findByHeaderFormat(string $headerFormat, ?string $status = null): array;
    
    /**
     * Recherche les templates avec un nombre spécifique de variables
     * 
     * @param int $minVariables Nombre minimum de variables
     * @param int|null $maxVariables Nombre maximum de variables (optionnel)
     * @return array
     */
    public function findByVariableCount(int $minVariables, ?int $maxVariables = null): array;
    
    /**
     * Recherche les templates avec boutons
     * 
     * @param int|null $buttonCount Nombre de boutons (optionnel, si null retourne tous les templates avec des boutons)
     * @return array
     */
    public function findWithButtons(?int $buttonCount = null): array;
    
    /**
     * Recherche textuelle dans le corps des templates
     * 
     * @param string $searchText Texte à rechercher
     * @return array
     */
    public function searchInBodyText(string $searchText): array;
    
    /**
     * Récupère les templates les plus utilisés
     * 
     * @param int $limit Nombre de templates à récupérer
     * @return array
     */
    public function findMostUsed(int $limit = 10): array;
}
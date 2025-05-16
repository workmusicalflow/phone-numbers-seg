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
}
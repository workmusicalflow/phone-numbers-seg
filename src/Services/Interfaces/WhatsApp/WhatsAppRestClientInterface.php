<?php

declare(strict_types=1);

namespace App\Services\Interfaces\WhatsApp;

use App\Entities\User;

/**
 * Interface pour le client REST WhatsApp
 * 
 * Définit les méthodes pour interagir avec l'API REST WhatsApp
 */
interface WhatsAppRestClientInterface
{
    /**
     * Récupère les templates WhatsApp approuvés
     * 
     * @param User $user Utilisateur authentifié
     * @param array $filters Filtres optionnels (name, language, category, status, useCache, forceRefresh)
     * @return array Templates WhatsApp
     * @throws \Exception Si une erreur se produit
     */
    public function getApprovedTemplates(User $user, array $filters = []): array;
    
    /**
     * Récupère un template spécifique par son ID
     * 
     * @param User $user Utilisateur authentifié
     * @param string $templateId ID du template
     * @return array Détails du template
     * @throws \Exception Si une erreur se produit
     */
    public function getTemplateById(User $user, string $templateId): array;
}
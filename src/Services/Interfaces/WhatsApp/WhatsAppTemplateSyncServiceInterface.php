<?php

declare(strict_types=1);

namespace App\Services\Interfaces\WhatsApp;

/**
 * Interface pour le service de synchronisation des templates WhatsApp
 * 
 * Cette interface définit les méthodes pour synchroniser les templates WhatsApp
 * entre l'API Meta Cloud et la base de données locale.
 */
interface WhatsAppTemplateSyncServiceInterface
{
    /**
     * Synchronise tous les templates depuis l'API Meta vers la base de données locale
     * 
     * @param bool $forceUpdate Si true, force la mise à jour des templates même s'ils existent déjà
     * @return array Statistiques de synchronisation [total, added, updated, failed]
     */
    public function syncTemplates(bool $forceUpdate = false): array;

    /**
     * Synchronise un template spécifique avec la base de données locale
     * 
     * @param array $metaTemplate Le template depuis l'API Meta
     * @param bool $forceUpdate Si true, force la mise à jour même si le template existe déjà
     * @return string Résultat de la synchronisation ('added', 'updated', 'unchanged')
     */
    public function syncTemplate(array $metaTemplate, bool $forceUpdate = false): string;

    /**
     * Synchronise les templates avec les utilisateurs, en particulier l'administrateur
     * 
     * @param bool $adminOnly Si true, synchronise uniquement avec l'utilisateur admin
     * @return int Nombre de relations utilisateur-template créées
     */
    public function syncTemplatesWithUsers(bool $adminOnly = true): int;

    /**
     * Effectue une synchronisation complète (templates depuis Meta puis vers utilisateurs)
     * 
     * @param bool $forceUpdate Si true, force la mise à jour des templates même s'ils existent déjà
     * @param bool $adminOnly Si true, synchronise uniquement avec l'utilisateur admin
     * @return array Statistiques complètes de synchronisation
     */
    public function fullSync(bool $forceUpdate = false, bool $adminOnly = true): array;

    /**
     * Désactive les templates qui n'existent plus dans l'API Meta
     * au lieu de les supprimer physiquement
     * 
     * @return int Nombre de templates désactivés
     */
    public function disableOrphanedTemplates(): int;

    /**
     * Génère un rapport détaillé sur l'état des templates WhatsApp
     * 
     * @return array Rapport détaillé
     */
    public function generateTemplateReport(): array;
}
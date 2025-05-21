<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Entities\WhatsApp\WhatsAppUserTemplate;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;
use App\Repositories\Interfaces\WhatsApp\WhatsAppUserTemplateRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppTemplateSyncServiceInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour synchroniser les templates WhatsApp entre l'API Meta et la base de données locale
 * 
 * Ce service permet de:
 * 1. Récupérer les templates depuis l'API Cloud de Meta
 * 2. Synchroniser ces templates avec la base de données locale
 * 3. Gérer les associations template-utilisateur
 * 4. Journaliser les opérations et gérer les erreurs
 */
class WhatsAppTemplateSyncService implements WhatsAppTemplateSyncServiceInterface
{
    private WhatsAppApiClientInterface $apiClient;
    private WhatsAppTemplateRepositoryInterface $templateRepository;
    private WhatsAppUserTemplateRepositoryInterface $userTemplateRepository;
    private UserRepositoryInterface $userRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        WhatsAppApiClientInterface $apiClient,
        WhatsAppTemplateRepositoryInterface $templateRepository,
        WhatsAppUserTemplateRepositoryInterface $userTemplateRepository,
        UserRepositoryInterface $userRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->templateRepository = $templateRepository;
        $this->userTemplateRepository = $userTemplateRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Synchronise tous les templates depuis l'API Meta vers la base de données locale
     * 
     * @param bool $forceUpdate Si true, force la mise à jour des templates même s'ils existent déjà
     * @return array Statistiques de synchronisation [total, added, updated, failed]
     */
    public function syncTemplates(bool $forceUpdate = false): array
    {
        $this->logger->info('Début de la synchronisation des templates WhatsApp');
        
        $stats = [
            'total' => 0,
            'added' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'failed' => 0
        ];
        
        try {
            // Récupérer tous les templates depuis l'API Meta
            $metaTemplates = $this->apiClient->getTemplates();
            $stats['total'] = count($metaTemplates);
            
            $this->logger->info("Récupération de {$stats['total']} templates depuis l'API Meta");
            
            // Commencer une transaction
            $this->entityManager->beginTransaction();
            
            foreach ($metaTemplates as $metaTemplate) {
                try {
                    $result = $this->syncTemplate($metaTemplate, $forceUpdate);
                    $stats[$result]++;
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors de la synchronisation du template', [
                        'template_name' => $metaTemplate['name'] ?? 'Inconnu',
                        'error' => $e->getMessage()
                    ]);
                    $stats['failed']++;
                }
            }
            
            // Valider la transaction
            $this->entityManager->commit();
            
            $this->logger->info('Fin de la synchronisation des templates WhatsApp', $stats);
            
            return $stats;
        } catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            
            $this->logger->error('Erreur globale lors de la synchronisation des templates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Synchronise un template spécifique avec la base de données locale
     * 
     * @param array $metaTemplate Le template depuis l'API Meta
     * @param bool $forceUpdate Si true, force la mise à jour même si le template existe déjà
     * @return string Résultat de la synchronisation ('added', 'updated', 'unchanged')
     */
    public function syncTemplate(array $metaTemplate, bool $forceUpdate = false): string
    {
        // Vérifier que le template a les données minimales requises
        if (!isset($metaTemplate['name']) || !isset($metaTemplate['language'])) {
            throw new \InvalidArgumentException('Template incomplet (nom ou langue manquant)');
        }
        
        // Rechercher le template existant
        $existingTemplate = $this->templateRepository->findOneBy([
            'name' => $metaTemplate['name'],
            'language' => $metaTemplate['language']
        ]);
        
        if (!$existingTemplate) {
            // Créer un nouveau template
            $template = new WhatsAppTemplate();
            $template->setName($metaTemplate['name']);
            $template->setLanguage($metaTemplate['language']);
            $template->setMetaTemplateId($metaTemplate['id'] ?? null);
            $template->setStatus($metaTemplate['status'] ?? WhatsAppTemplate::STATUS_PENDING);
            $template->setCategory($metaTemplate['category'] ?? null);
            
            // Extraire et stocker les composants
            if (isset($metaTemplate['components'])) {
                $template->setComponentsFromArray($metaTemplate['components']);
            }
            
            $this->templateRepository->save($template);
            
            $this->logger->info('Template ajouté', [
                'name' => $template->getName(),
                'language' => $template->getLanguage()
            ]);
            
            return 'added';
        } else {
            // Vérifier si une mise à jour est nécessaire
            $needsUpdate = $forceUpdate;
            
            if (!$needsUpdate) {
                // Vérifier les différences pour déterminer si une mise à jour est nécessaire
                $needsUpdate = (
                    ($existingTemplate->getStatus() !== ($metaTemplate['status'] ?? $existingTemplate->getStatus())) ||
                    ($existingTemplate->getCategory() !== ($metaTemplate['category'] ?? $existingTemplate->getCategory())) ||
                    ($existingTemplate->getMetaTemplateId() !== ($metaTemplate['id'] ?? $existingTemplate->getMetaTemplateId()))
                );
                
                // Vérifier si les composants ont changé
                if (isset($metaTemplate['components'])) {
                    $existingComponents = $existingTemplate->getComponentsAsArray();
                    $needsUpdate = $needsUpdate || ($existingComponents != $metaTemplate['components']);
                }
            }
            
            if ($needsUpdate) {
                // Mettre à jour le template existant
                $existingTemplate->setStatus($metaTemplate['status'] ?? $existingTemplate->getStatus());
                $existingTemplate->setCategory($metaTemplate['category'] ?? $existingTemplate->getCategory());
                $existingTemplate->setMetaTemplateId($metaTemplate['id'] ?? $existingTemplate->getMetaTemplateId());
                
                if (isset($metaTemplate['components'])) {
                    $existingTemplate->setComponentsFromArray($metaTemplate['components']);
                }
                
                $this->templateRepository->save($existingTemplate);
                
                $this->logger->info('Template mis à jour', [
                    'name' => $existingTemplate->getName(),
                    'language' => $existingTemplate->getLanguage()
                ]);
                
                return 'updated';
            }
            
            return 'unchanged';
        }
    }

    /**
     * Synchronise les templates avec les utilisateurs, en particulier l'administrateur
     * 
     * @param bool $adminOnly Si true, synchronise uniquement avec l'utilisateur admin
     * @return int Nombre de relations utilisateur-template créées
     */
    public function syncTemplatesWithUsers(bool $adminOnly = true): int
    {
        $this->logger->info('Début de la synchronisation des templates avec les utilisateurs');
        
        $createdCount = 0;
        
        try {
            // Récupérer les utilisateurs concernés
            $users = $adminOnly 
                ? $this->userRepository->findBy(['isAdmin' => true])
                : $this->userRepository->findAll();
            
            if (empty($users)) {
                $this->logger->warning('Aucun utilisateur trouvé pour la synchronisation des templates');
                return 0;
            }
            
            // Récupérer tous les templates approuvés
            $templates = $this->templateRepository->findBy([
                'status' => WhatsAppTemplate::STATUS_APPROVED,
                'isActive' => true
            ]);
            
            if (empty($templates)) {
                $this->logger->warning('Aucun template approuvé trouvé pour la synchronisation');
                return 0;
            }
            
            $this->logger->info('Synchronisation de ' . count($templates) . ' templates avec ' . count($users) . ' utilisateurs');
            
            // Commencer une transaction
            $this->entityManager->beginTransaction();
            
            foreach ($users as $user) {
                foreach ($templates as $template) {
                    // Vérifier si la relation existe déjà
                    $existingRelation = $this->userTemplateRepository->findOneBy([
                        'user' => $user,
                        'templateName' => $template->getName(),
                        'languageCode' => $template->getLanguage()
                    ]);
                    
                    if (!$existingRelation) {
                        // Créer une nouvelle relation utilisateur-template
                        $userTemplate = new WhatsAppUserTemplate();
                        $userTemplate->setUser($user);
                        $userTemplate->setTemplateName($template->getName());
                        $userTemplate->setLanguageCode($template->getLanguage());
                        $userTemplate->setBodyVariablesCount($template->getBodyVariablesCount());
                        $userTemplate->setHasHeaderMedia($template->hasHeaderMedia());
                        $userTemplate->setIsSpecialTemplate(false);
                        $userTemplate->setCreatedAt(new DateTime());
                        $userTemplate->setUpdatedAt(new DateTime());
                        
                        $this->userTemplateRepository->save($userTemplate);
                        $createdCount++;
                    }
                }
            }
            
            // Valider la transaction
            $this->entityManager->commit();
            
            $this->logger->info('Fin de la synchronisation des templates avec les utilisateurs', [
                'created' => $createdCount
            ]);
            
            return $createdCount;
        } catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            
            $this->logger->error('Erreur lors de la synchronisation des templates avec les utilisateurs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Effectue une synchronisation complète (templates depuis Meta puis vers utilisateurs)
     * 
     * @param bool $forceUpdate Si true, force la mise à jour des templates même s'ils existent déjà
     * @param bool $adminOnly Si true, synchronise uniquement avec l'utilisateur admin
     * @return array Statistiques complètes de synchronisation
     */
    public function fullSync(bool $forceUpdate = false, bool $adminOnly = true): array
    {
        $this->logger->info('Début de la synchronisation complète des templates WhatsApp');
        
        try {
            // 1. Synchroniser les templates depuis Meta
            $templateStats = $this->syncTemplates($forceUpdate);
            
            // 2. Synchroniser avec les utilisateurs
            $userTemplatesCreated = $this->syncTemplatesWithUsers($adminOnly);
            
            $stats = array_merge($templateStats, ['user_templates_created' => $userTemplatesCreated]);
            
            $this->logger->info('Fin de la synchronisation complète des templates WhatsApp', $stats);
            
            return $stats;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la synchronisation complète des templates', [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Désactive les templates qui n'existent plus dans l'API Meta
     * au lieu de les supprimer physiquement
     * 
     * @return int Nombre de templates désactivés
     */
    public function disableOrphanedTemplates(): int
    {
        $this->logger->info('Recherche de templates orphelins à désactiver');
        
        try {
            // Récupérer tous les templates depuis l'API Meta
            $metaTemplates = $this->apiClient->getTemplates();
            
            // Créer un index pour une recherche rapide
            $metaTemplateIndex = [];
            foreach ($metaTemplates as $template) {
                if (isset($template['name']) && isset($template['language'])) {
                    $key = $template['name'] . '_' . $template['language'];
                    $metaTemplateIndex[$key] = true;
                }
            }
            
            // Récupérer tous les templates actifs en base
            $localTemplates = $this->templateRepository->findBy(['isActive' => true]);
            
            $disabledCount = 0;
            
            // Commencer une transaction
            $this->entityManager->beginTransaction();
            
            foreach ($localTemplates as $localTemplate) {
                $key = $localTemplate->getName() . '_' . $localTemplate->getLanguage();
                
                if (!isset($metaTemplateIndex[$key])) {
                    // Ce template n'existe plus dans l'API Meta, le désactiver
                    $localTemplate->setIsActive(false);
                    $this->templateRepository->save($localTemplate);
                    $disabledCount++;
                    
                    $this->logger->info('Template désactivé car non trouvé dans l\'API Meta', [
                        'name' => $localTemplate->getName(),
                        'language' => $localTemplate->getLanguage()
                    ]);
                }
            }
            
            // Valider la transaction
            $this->entityManager->commit();
            
            $this->logger->info('Fin de la désactivation des templates orphelins', [
                'disabled' => $disabledCount
            ]);
            
            return $disabledCount;
        } catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            
            $this->logger->error('Erreur lors de la désactivation des templates orphelins', [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Génère un rapport détaillé sur l'état des templates WhatsApp
     * 
     * @return array Rapport détaillé
     */
    public function generateTemplateReport(): array
    {
        $this->logger->info('Génération du rapport sur les templates WhatsApp');
        
        try {
            // Statistiques des templates en base de données
            $dbTemplates = $this->templateRepository->findAll();
            $dbTemplateCount = count($dbTemplates);
            
            $statusCounts = [];
            $categoryCounts = [];
            $languageCounts = [];
            $activeCount = 0;
            
            foreach ($dbTemplates as $template) {
                // Compter par statut
                $status = $template->getStatus();
                if (!isset($statusCounts[$status])) {
                    $statusCounts[$status] = 0;
                }
                $statusCounts[$status]++;
                
                // Compter par catégorie
                $category = $template->getCategory() ?? 'Non spécifié';
                if (!isset($categoryCounts[$category])) {
                    $categoryCounts[$category] = 0;
                }
                $categoryCounts[$category]++;
                
                // Compter par langue
                $language = $template->getLanguage();
                if (!isset($languageCounts[$language])) {
                    $languageCounts[$language] = 0;
                }
                $languageCounts[$language]++;
                
                // Compter les actifs
                if ($template->isActive()) {
                    $activeCount++;
                }
            }
            
            // Récupérer les templates depuis Meta pour comparaison
            $metaTemplates = [];
            try {
                $metaTemplates = $this->apiClient->getTemplates();
            } catch (\Exception $e) {
                $this->logger->warning('Impossible de récupérer les templates depuis l\'API Meta pour le rapport', [
                    'error' => $e->getMessage()
                ]);
            }
            
            $metaTemplateCount = count($metaTemplates);
            
            // Générer le rapport
            $report = [
                'database' => [
                    'total' => $dbTemplateCount,
                    'active' => $activeCount,
                    'inactive' => $dbTemplateCount - $activeCount,
                    'by_status' => $statusCounts,
                    'by_category' => $categoryCounts,
                    'by_language' => $languageCounts
                ],
                'meta_api' => [
                    'total' => $metaTemplateCount,
                    'sync_status' => ($metaTemplateCount > 0) ? 'OK' : 'Erreur'
                ],
                'generated_at' => (new DateTime())->format('Y-m-d H:i:s')
            ];
            
            $this->logger->info('Rapport sur les templates WhatsApp généré');
            
            return $report;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération du rapport sur les templates', [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
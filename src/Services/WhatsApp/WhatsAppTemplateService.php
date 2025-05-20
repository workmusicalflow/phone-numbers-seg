<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Entities\User;
use App\Entities\WhatsApp\WhatsAppTemplate;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppApiClientInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour gérer les templates de messages WhatsApp
 * 
 * Ce service utilise l'approche de "chargement direct" qui consiste à récupérer 
 * les templates directement depuis l'API Meta (Cloud API) sans synchronisation
 * avec une base de données locale. Cela garantit que les templates sont toujours
 * à jour avec les dernières modifications ou approbations de Meta.
 */
class WhatsAppTemplateService implements WhatsAppTemplateServiceInterface
{
    private WhatsAppApiClientInterface $apiClient;
    private LoggerInterface $logger;
    private ?WhatsAppTemplateRepositoryInterface $templateRepository;  // Optionnel et non utilisé dans l'approche directe

    public function __construct(
        WhatsAppApiClientInterface $apiClient,
        ?WhatsAppTemplateRepositoryInterface $templateRepository,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->templateRepository = $templateRepository; // Gardé pour compatibilité mais non utilisé
        $this->logger = $logger;
    }

    /**
     * Récupère les templates approuvés directement depuis l'API Meta
     * Sans stockage local (approche chargement direct)
     *
     * Cette méthode est au cœur de l'approche de chargement direct. Elle:
     * 1. Fait un appel à l'API Cloud de Meta pour obtenir tous les templates
     * 2. Filtre pour ne conserver que les templates approuvés
     * 3. Applique des filtres supplémentaires si demandé (nom, langue, catégorie)
     * 
     * @param array $filters Filtres optionnels (name, language, category)
     * @return array Templates approuvés
     */
    public function fetchApprovedTemplatesFromMeta(array $filters = []): array
    {
        try {
            // Récupérer tous les templates depuis l'API Meta
            $allTemplates = $this->apiClient->getTemplates();
            
            // Filtrer pour ne garder que les templates avec statut "APPROVED"
            $approvedTemplates = array_filter($allTemplates, function($template) {
                return isset($template['status']) && $template['status'] === 'APPROVED';
            });
            
            // Appliquer des filtres supplémentaires si fournis
            if (!empty($filters)) {
                $approvedTemplates = array_filter($approvedTemplates, function($template) use ($filters) {
                    $match = true;
                    
                    // Filtre par nom
                    if (isset($filters['name']) && !empty($filters['name'])) {
                        $match = $match && (stripos($template['name'] ?? '', $filters['name']) !== false);
                    }
                    
                    // Filtre par langue
                    if (isset($filters['language']) && !empty($filters['language'])) {
                        $match = $match && (($template['language'] ?? '') === $filters['language']);
                    }
                    
                    // Filtre par catégorie
                    if (isset($filters['category']) && !empty($filters['category'])) {
                        $match = $match && (($template['category'] ?? '') === $filters['category']);
                    }
                    
                    return $match;
                });
            }
            
            // Transformer les templates pour s'assurer que tous les champs requis sont présents
            $formattedTemplates = [];
            foreach ($approvedTemplates as $template) {
                // S'assurer que template_id existe et est une string
                if (!isset($template['id'])) {
                    $template['id'] = isset($template['name']) ? md5($template['name'] . ($template['language'] ?? '')) : uniqid();
                }
                
                // Convertir id en template_id (qui est requis comme non-nullable dans le schéma)
                $template['template_id'] = (string)$template['id'];
                
                $formattedTemplates[] = $template;
            }
            
            // Retourner les templates approuvés, filtrés et correctement formatés
            return array_values($formattedTemplates);
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération templates WhatsApp depuis Meta', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            
            return [];
        }
    }

    /**
     * Récupère les templates pour un utilisateur spécifique
     * En utilisant l'approche de chargement direct depuis Meta
     * 
     * Note: Dans l'approche de chargement direct, tous les utilisateurs ont accès
     * aux mêmes templates car ils sont associés au compte WhatsApp Business (WABA)
     * et non aux utilisateurs individuels.
     *
     * @param User $user L'utilisateur est ignoré dans cette implémentation
     * @param array $filters Filtres optionnels (name, language, category)
     * @return array Liste des templates disponibles
     */
    public function getUserTemplates(User $user, array $filters = []): array
    {
        // En mode chargement direct, l'utilisateur n'affecte pas les templates disponibles
        // Les templates sont associés au compte WhatsApp Business (WABA)
        return $this->fetchApprovedTemplatesFromMeta($filters);
    }

    /**
     * Récupère les catégories de templates disponibles
     *
     * @return array Liste des catégories
     */
    public function getTemplateCategories(): array
    {
        try {
            $templates = $this->apiClient->getTemplates();
            
            // Extraire les catégories uniques
            $categories = [];
            foreach ($templates as $template) {
                if (isset($template['category']) && !empty($template['category'])) {
                    $categories[$template['category']] = true;
                }
            }
            
            return array_keys($categories);
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération catégories templates WhatsApp', [
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Récupère les langues disponibles pour les templates
     *
     * @return array Liste des langues
     */
    public function getTemplateLanguages(): array
    {
        try {
            $templates = $this->apiClient->getTemplates();
            
            // Extraire les langues uniques
            $languages = [];
            foreach ($templates as $template) {
                if (isset($template['language']) && !empty($template['language'])) {
                    $languages[$template['language']] = true;
                }
            }
            
            return array_keys($languages);
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération langues templates WhatsApp', [
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Récupère un template spécifique par son nom et sa langue
     *
     * @param string $templateName
     * @param string $languageCode
     * @return array|null
     */
    public function getTemplate(string $templateName, string $languageCode): ?array
    {
        try {
            $templates = $this->apiClient->getTemplates();
            
            // Rechercher le template par nom et langue
            foreach ($templates as $template) {
                if (
                    ($template['name'] ?? '') === $templateName && 
                    ($template['language'] ?? '') === $languageCode
                ) {
                    return $template;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération template WhatsApp spécifique', [
                'template_name' => $templateName,
                'language' => $languageCode,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Construit le payload de composants pour un template à partir des données dynamiques
     *
     * @param array $templateComponentsFromMeta Structure des composants du template depuis Meta
     * @param array $templateDynamicData Données dynamiques pour personnaliser le template
     * @return array Structure de composants pour l'API
     */
    public function buildTemplateComponents(array $templateComponentsFromMeta, array $templateDynamicData): array
    {
        $components = [];
        
        // Parcourir les composants du template
        foreach ($templateComponentsFromMeta as $component) {
            $componentType = $component['type'] ?? '';
            
            switch ($componentType) {
                case 'HEADER':
                    // Si un header est défini dans le template
                    $format = $component['format'] ?? '';
                    
                    if (isset($templateDynamicData['header'])) {
                        $headerData = $templateDynamicData['header'];
                        
                        $headerComponent = [
                            'type' => 'header',
                            'parameters' => []
                        ];
                        
                        // Gérer différents types de header
                        if ($format === 'TEXT' && isset($headerData['text'])) {
                            $headerComponent['parameters'][] = [
                                'type' => 'text',
                                'text' => $headerData['text']
                            ];
                        } elseif ($format === 'IMAGE' && (isset($headerData['link']) || isset($headerData['id']))) {
                            $imageParam = [
                                'type' => 'image',
                                'image' => []
                            ];
                            
                            if (isset($headerData['link'])) {
                                $imageParam['image']['link'] = $headerData['link'];
                            } elseif (isset($headerData['id'])) {
                                $imageParam['image']['id'] = $headerData['id'];
                            }
                            
                            $headerComponent['parameters'][] = $imageParam;
                        } elseif ($format === 'VIDEO' && (isset($headerData['link']) || isset($headerData['id']))) {
                            $videoParam = [
                                'type' => 'video',
                                'video' => []
                            ];
                            
                            if (isset($headerData['link'])) {
                                $videoParam['video']['link'] = $headerData['link'];
                            } elseif (isset($headerData['id'])) {
                                $videoParam['video']['id'] = $headerData['id'];
                            }
                            
                            $headerComponent['parameters'][] = $videoParam;
                        } elseif ($format === 'DOCUMENT' && (isset($headerData['link']) || isset($headerData['id']))) {
                            $docParam = [
                                'type' => 'document',
                                'document' => []
                            ];
                            
                            if (isset($headerData['link'])) {
                                $docParam['document']['link'] = $headerData['link'];
                                
                                if (isset($headerData['filename'])) {
                                    $docParam['document']['filename'] = $headerData['filename'];
                                }
                            } elseif (isset($headerData['id'])) {
                                $docParam['document']['id'] = $headerData['id'];
                                
                                if (isset($headerData['filename'])) {
                                    $docParam['document']['filename'] = $headerData['filename'];
                                }
                            }
                            
                            $headerComponent['parameters'][] = $docParam;
                        }
                        
                        if (!empty($headerComponent['parameters'])) {
                            $components[] = $headerComponent;
                        }
                    }
                    break;
                    
                case 'BODY':
                    // Si des variables sont définies dans le body
                    if (isset($component['text']) && isset($templateDynamicData['body'])) {
                        $bodyText = $component['text'];
                        $bodyParams = $templateDynamicData['body'];
                        
                        // Compter le nombre de variables {{1}}, {{2}}, etc.
                        preg_match_all('/{{(\d+)}}/', $bodyText, $matches);
                        
                        if (!empty($matches[1])) {
                            $bodyComponent = [
                                'type' => 'body',
                                'parameters' => []
                            ];
                            
                            foreach ($matches[1] as $index) {
                                $paramIdx = intval($index) - 1;
                                if (isset($bodyParams[$paramIdx])) {
                                    $param = $bodyParams[$paramIdx];
                                    
                                    // Si c'est un tableau avec type spécifié
                                    if (is_array($param) && isset($param['type'])) {
                                        $bodyComponent['parameters'][] = $param;
                                    } 
                                    // Sinon, traiter comme texte simple
                                    else {
                                        $bodyComponent['parameters'][] = [
                                            'type' => 'text',
                                            'text' => (string)$param
                                        ];
                                    }
                                } else {
                                    // Paramètre manquant, utiliser une chaîne vide
                                    $bodyComponent['parameters'][] = [
                                        'type' => 'text',
                                        'text' => ''
                                    ];
                                }
                            }
                            
                            if (!empty($bodyComponent['parameters'])) {
                                $components[] = $bodyComponent;
                            }
                        }
                    }
                    break;
                    
                case 'BUTTONS':
                    // Si des boutons sont définis dans le template
                    if (isset($component['buttons']) && isset($templateDynamicData['buttons'])) {
                        $templateButtons = $component['buttons'];
                        $buttonParams = $templateDynamicData['buttons'];
                        
                        foreach ($templateButtons as $buttonIndex => $button) {
                            $buttonType = $button['type'] ?? '';
                            
                            if ($buttonType === 'QUICK_REPLY' && isset($buttonParams[$buttonIndex])) {
                                $components[] = [
                                    'type' => 'button',
                                    'sub_type' => 'quick_reply',
                                    'index' => (string)$buttonIndex,
                                    'parameters' => [
                                        [
                                            'type' => 'payload',
                                            'payload' => $buttonParams[$buttonIndex]
                                        ]
                                    ]
                                ];
                            } elseif ($buttonType === 'URL' && isset($buttonParams[$buttonIndex])) {
                                $components[] = [
                                    'type' => 'button',
                                    'sub_type' => 'url',
                                    'index' => (string)$buttonIndex,
                                    'parameters' => [
                                        [
                                            'type' => 'text',
                                            'text' => $buttonParams[$buttonIndex]
                                        ]
                                    ]
                                ];
                            }
                        }
                    }
                    break;
            }
        }
        
        return $components;
    }
}
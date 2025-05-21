<?php

declare(strict_types=1);

namespace App\GraphQL\Resolvers\WhatsApp;

use App\Entities\User;
use App\GraphQL\Types\WhatsApp\SendTemplateInput;
use App\GraphQL\Types\WhatsApp\TemplateFilterInput;
use App\GraphQL\Types\WhatsApp\WhatsAppTemplateSafeType;
use App\Services\Interfaces\WhatsApp\WhatsAppRestClientInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppServiceInterface;
use App\Services\Interfaces\WhatsApp\WhatsAppTemplateServiceInterface;
use Psr\Log\LoggerInterface;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

/**
 * Resolver GraphQL pour les templates WhatsApp
 */
class WhatsAppTemplateResolver
{
    private WhatsAppTemplateServiceInterface $templateService;
    private WhatsAppServiceInterface $whatsAppService;
    private LoggerInterface $logger;
    private \App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface $templateRepository;
    private WhatsAppRestClientInterface $restClient;

    public function __construct(
        WhatsAppTemplateServiceInterface $templateService,
        WhatsAppServiceInterface $whatsAppService,
        \App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface $templateRepository,
        WhatsAppRestClientInterface $restClient,
        LoggerInterface $logger
    ) {
        $this->templateService = $templateService;
        $this->whatsAppService = $whatsAppService;
        $this->templateRepository = $templateRepository;
        $this->restClient = $restClient;
        $this->logger = $logger;
    }

    /**
     * Récupère les templates WhatsApp approuvés via l'API REST robuste
     *
     * Cette implémentation utilise le client REST qui implémente plusieurs niveaux
     * de fallback pour garantir qu'une réponse valide est toujours fournie.
     *
     * @param TemplateFilterInput|null $filter Filtres optionnels
     * @return WhatsAppTemplateType[]
     */
    #[Query(name: "fetchApprovedWhatsAppTemplates")]
    #[Logged]
    public function fetchApprovedWhatsAppTemplates(?TemplateFilterInput $filter = null, #[InjectUser] ?User $user = null): array
    {
        if (!$user) {
            throw new GraphQLException("Authentification requise", 401);
        }

        try {
            // Convertir les filtres GraphQL en filtres REST
            $filterArray = [];
            if ($filter) {
                if ($filter->name !== null) {
                    $filterArray['name'] = $filter->name;
                }
                if ($filter->language !== null) {
                    $filterArray['language'] = $filter->language;
                }
                if ($filter->category !== null) {
                    $filterArray['category'] = $filter->category;
                }
                if ($filter->status !== null) {
                    $filterArray['status'] = $filter->status;
                }
                
                // Si des filtres avancés sont demandés, forcer le chargement depuis l'API
                // car les fallbacks ne supportent pas tous les types de filtres
                if ($filter->hasHeaderMedia !== null || 
                    $filter->headerFormat !== null || 
                    $filter->minVariables !== null ||
                    $filter->maxVariables !== null ||
                    $filter->hasButtons !== null ||
                    $filter->hasFooter !== null) {
                    $filterArray['forceRefresh'] = true;
                }
            }

            // Utiliser le client REST qui implémente déjà les fallbacks
            $templates = $this->restClient->getApprovedTemplates($user, $filterArray);
            
            // Convertir les templates en types GraphQL
            $templateTypes = [];
            foreach ($templates as $template) {
                try {
                    $templateTypes[] = new WhatsAppTemplateSafeType($template);
                } catch (\Throwable $typeException) {
                    $this->logger->error('Erreur lors de la création du type template', [
                        'template' => $template,
                        'error' => $typeException->getMessage()
                    ]);
                    // Continuer avec le prochain template en cas d'erreur
                    continue;
                }
            }
            
            // Appliquer les filtres avancés côté serveur si nécessaire
            if ($filter) {
                if ($filter->hasHeaderMedia !== null) {
                    $templateTypes = array_filter($templateTypes, function($template) use ($filter) {
                        return $template->hasMediaHeader() === $filter->hasHeaderMedia;
                    });
                }
                
                if ($filter->headerFormat !== null) {
                    $templateTypes = array_filter($templateTypes, function($template) use ($filter) {
                        return $template->getHeaderFormat() === $filter->headerFormat;
                    });
                }
                
                if ($filter->minVariables !== null) {
                    $templateTypes = array_filter($templateTypes, function($template) use ($filter) {
                        return $template->getBodyVariablesCount() >= $filter->minVariables;
                    });
                }
                
                if ($filter->maxVariables !== null) {
                    $templateTypes = array_filter($templateTypes, function($template) use ($filter) {
                        return $template->getBodyVariablesCount() <= $filter->maxVariables;
                    });
                }
                
                if ($filter->hasButtons !== null) {
                    $templateTypes = array_filter($templateTypes, function($template) use ($filter) {
                        return $template->hasButtons() === $filter->hasButtons;
                    });
                }
                
                if ($filter->hasFooter !== null) {
                    $templateTypes = array_filter($templateTypes, function($template) use ($filter) {
                        return $template->hasFooter() === $filter->hasFooter;
                    });
                }
                
                if ($filter->bodyText !== null) {
                    $templateTypes = array_filter($templateTypes, function($template) use ($filter) {
                        $fullBodyText = $template->getFullBodyText();
                        return $fullBodyText && stripos($fullBodyText, $filter->bodyText) !== false;
                    });
                }
                
                // Réindexer le tableau après filtrage
                $templateTypes = array_values($templateTypes);
            }
            
            // Garantir que nous retournons TOUJOURS un tableau (même vide)
            // pour respecter la non-nullabilité du schéma GraphQL
            return $templateTypes;
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération templates WhatsApp', [
                'error' => $e->getMessage(),
                'user' => $user->getId() ?? 'unknown'
            ]);
            
            // En cas d'erreur, retournez un tableau vide plutôt que de lancer une exception
            // pour éviter l'erreur "Cannot return null for non-nullable field"
            return [];
        }
    }
    
    /**
     * Recherche avancée de templates WhatsApp avec filtrage multiple
     * 
     * Utilise le client REST qui implémente déjà tous les mécanismes de fallback,
     * puis applique les filtres côté serveur pour garantir la cohérence des résultats.
     *
     * @param TemplateFilterInput|null $filter Filtres avancés
     * @param int|null $limit Nombre maximum de résultats
     * @param int|null $offset Position de départ
     * @return WhatsAppTemplateType[]
     */
    #[Query(name: "searchWhatsAppTemplates")]
    #[Logged]
    public function searchWhatsAppTemplates(
        ?TemplateFilterInput $filter = null, 
        ?int $limit = 20,
        ?int $offset = 0,
        #[InjectUser] ?User $user = null
    ): array {
        if (!$user) {
            throw new GraphQLException("Authentification requise", 401);
        }

        try {
            // Convertir les filtres GraphQL en filtres REST
            $restFilters = [];
            
            // Ajouter les filtres de base supportés par l'API REST
            if ($filter) {
                if ($filter->name !== null) {
                    $restFilters['name'] = $filter->name;
                }
                if ($filter->language !== null) {
                    $restFilters['language'] = $filter->language;
                }
                if ($filter->category !== null) {
                    $restFilters['category'] = $filter->category;
                }
                if ($filter->status !== null) {
                    $restFilters['status'] = $filter->status;
                }
                
                // Pour les recherches avancées, forcer le rafraîchissement depuis l'API
                // afin de garantir les résultats les plus à jour
                $restFilters['forceRefresh'] = true;
            }
            
            // Utiliser le client REST pour obtenir la base de templates
            $templates = $this->restClient->getApprovedTemplates($user, $restFilters);
            
            // Convertir tous les templates en types GraphQL
            $templateTypes = [];
            foreach ($templates as $template) {
                try {
                    $templateTypes[] = new WhatsAppTemplateSafeType($template);
                } catch (\Throwable $typeException) {
                    $this->logger->error('Erreur lors de la création du type template', [
                        'template' => $template,
                        'error' => $typeException->getMessage()
                    ]);
                    continue;
                }
            }
            
            // Appliquer les filtres avancés côté serveur
            if ($filter) {
                if ($filter->headerFormat !== null) {
                    $templateTypes = array_filter($templateTypes, function($template) use ($filter) {
                        return $template->getHeaderFormat() === $filter->headerFormat;
                    });
                }
                
                if ($filter->hasHeaderMedia !== null) {
                    $templateTypes = array_filter($templateTypes, function($template) use ($filter) {
                        return $template->hasMediaHeader() === $filter->hasHeaderMedia;
                    });
                }
                
                if ($filter->minVariables !== null) {
                    $templateTypes = array_filter($templateTypes, function($template) use ($filter) {
                        return $template->getBodyVariablesCount() >= $filter->minVariables;
                    });
                }
                
                if ($filter->maxVariables !== null) {
                    $templateTypes = array_filter($templateTypes, function($template) use ($filter) {
                        return $template->getBodyVariablesCount() <= $filter->maxVariables;
                    });
                }
                
                if ($filter->hasButtons !== null) {
                    $templateTypes = array_filter($templateTypes, function($template) use ($filter) {
                        return $template->hasButtons() === $filter->hasButtons;
                    });
                }
                
                if ($filter->buttonCount !== null) {
                    $templateTypes = array_filter($templateTypes, function($template) use ($filter) {
                        return $template->getButtonsCount() === $filter->buttonCount;
                    });
                }
                
                if ($filter->hasFooter !== null) {
                    $templateTypes = array_filter($templateTypes, function($template) use ($filter) {
                        return $template->hasFooter() === $filter->hasFooter;
                    });
                }
                
                if ($filter->bodyText !== null) {
                    $templateTypes = array_filter($templateTypes, function($template) use ($filter) {
                        $fullBodyText = $template->getFullBodyText();
                        return $fullBodyText && stripos($fullBodyText, $filter->bodyText) !== false;
                    });
                }
                
                if ($filter->minUsageCount !== null) {
                    $templateTypes = array_filter($templateTypes, function($template) use ($filter) {
                        return $template->getUsageCount() >= $filter->minUsageCount;
                    });
                }
                
                // Appliquer le tri
                if ($filter->orderBy !== null) {
                    $direction = ($filter->orderDirection === 'DESC') ? -1 : 1;
                    usort($templateTypes, function($a, $b) use ($filter, $direction) {
                        $field = $filter->orderBy;
                        
                        // Récupérer dynamiquement les valeurs à comparer
                        $valueA = null;
                        $valueB = null;
                        
                        // Pour chaque field, utiliser la méthode getter appropriée
                        switch ($field) {
                            case 'name':
                                $valueA = $a->getName(); 
                                $valueB = $b->getName();
                                break;
                            case 'category':
                                $valueA = $a->getCategory(); 
                                $valueB = $b->getCategory();
                                break;
                            case 'language':
                                $valueA = $a->getLanguage(); 
                                $valueB = $b->getLanguage();
                                break;
                            case 'status':
                                $valueA = $a->getStatus(); 
                                $valueB = $b->getStatus();
                                break;
                            case 'bodyVariablesCount':
                                $valueA = $a->getBodyVariablesCount(); 
                                $valueB = $b->getBodyVariablesCount();
                                break;
                            case 'buttonsCount':
                                $valueA = $a->getButtonsCount(); 
                                $valueB = $b->getButtonsCount();
                                break;
                            case 'usageCount':
                                $valueA = $a->getUsageCount(); 
                                $valueB = $b->getUsageCount();
                                break;
                            case 'lastUsedAt':
                                $valueA = $a->getLastUsedAt() ? strtotime($a->getLastUsedAt()) : 0; 
                                $valueB = $b->getLastUsedAt() ? strtotime($b->getLastUsedAt()) : 0;
                                break;
                            default:
                                // Par défaut, tri par nom
                                $valueA = $a->getName(); 
                                $valueB = $b->getName();
                                break;
                        }
                        
                        // Comparaison en fonction du type de valeur
                        if (is_numeric($valueA) && is_numeric($valueB)) {
                            return ($valueA - $valueB) * $direction;
                        } else {
                            return strcasecmp($valueA ?? '', $valueB ?? '') * $direction;
                        }
                    });
                } else {
                    // Tri par défaut (nom)
                    usort($templateTypes, function($a, $b) {
                        return strcasecmp($a->getName(), $b->getName());
                    });
                }
            }
            
            // Appliquer la pagination en mémoire
            $totalTemplates = count($templateTypes);
            $templateTypes = array_slice($templateTypes, $offset, $limit);
            
            // Log des résultats pour monitoring
            $this->logger->info('Recherche de templates effectuée', [
                'total_found' => $totalTemplates,
                'returned' => count($templateTypes),
                'offset' => $offset,
                'limit' => $limit
            ]);
            
            // Retourner un tableau vide si aucun template trouvé
            return $templateTypes;
        } catch (\Exception $e) {
            $this->logger->error('Erreur recherche templates WhatsApp', [
                'error' => $e->getMessage(),
                'user' => $user->getId() ?? 'unknown',
                'filters' => $filter ?? []
            ]);
            
            // En cas d'erreur, retournez un tableau vide plutôt que de lancer une exception
            return [];
        }
    }

    /**
     * Récupère les catégories de templates disponibles
     *
     * @return string[]
     */
    #[Query(name: "whatsAppTemplateCategories")]
    #[Logged]
    public function getTemplateCategories(#[InjectUser] ?User $user = null): array
    {
        if (!$user) {
            throw new GraphQLException("Authentification requise", 401);
        }

        try {
            $categories = $this->templateService->getTemplateCategories();
            return $categories ?: [];
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération catégories templates WhatsApp', [
                'error' => $e->getMessage(),
                'user' => $user->getId() ?? 'unknown'
            ]);
            
            // En cas d'erreur, retournez un tableau vide
            return [];
        }
    }

    /**
     * Récupère les langues disponibles pour les templates
     *
     * @return string[]
     */
    #[Query(name: "whatsAppTemplateLanguages")]
    #[Logged]
    public function getTemplateLanguages(#[InjectUser] ?User $user = null): array
    {
        if (!$user) {
            throw new GraphQLException("Authentification requise", 401);
        }

        try {
            $languages = $this->templateService->getTemplateLanguages();
            return $languages ?: [];
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération langues templates WhatsApp', [
                'error' => $e->getMessage(),
                'user' => $user->getId() ?? 'unknown'
            ]);
            
            // En cas d'erreur, retournez un tableau vide
            return [];
        }
    }

    /**
     * Récupère les templates par format d'en-tête
     *
     * Utilise le client REST pour obtenir tous les templates, puis filtre par format d'en-tête.
     *
     * @param string $headerFormat Format d'en-tête (TEXT, IMAGE, VIDEO, DOCUMENT)
     * @return WhatsAppTemplateType[]
     */
    #[Query(name: "whatsAppTemplatesByHeaderFormat")]
    #[Logged]
    public function getTemplatesByHeaderFormat(
        string $headerFormat,
        ?string $status = 'APPROVED',
        #[InjectUser] ?User $user = null
    ): array {
        if (!$user) {
            throw new GraphQLException("Authentification requise", 401);
        }

        try {
            // Obtenir tous les templates ayant le statut demandé via le client REST
            $restFilters = [
                'status' => $status
            ];
            
            $templates = $this->restClient->getApprovedTemplates($user, $restFilters);
            
            // Convertir et filtrer les templates par format d'en-tête
            $templateTypes = [];
            foreach ($templates as $template) {
                try {
                    $templateType = new WhatsAppTemplateSafeType($template);
                    
                    // Ne garder que les templates avec le format d'en-tête correspondant
                    if ($templateType->getHeaderFormat() === $headerFormat) {
                        $templateTypes[] = $templateType;
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('Erreur lors de la création du type template', [
                        'template' => $template,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }
            
            // Log du résultat pour monitoring
            $this->logger->info('Récupération des templates par format d\'en-tête', [
                'header_format' => $headerFormat,
                'status' => $status,
                'count' => count($templateTypes)
            ]);
            
            return $templateTypes;
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération templates par format d\'en-tête', [
                'error' => $e->getMessage(),
                'user' => $user->getId() ?? 'unknown',
                'headerFormat' => $headerFormat
            ]);
            
            // En cas d'erreur, retournez un tableau vide
            return [];
        }
    }

    /**
     * Récupère les templates les plus utilisés
     *
     * Utilise le client REST pour obtenir les templates, puis trie par usage.
     * 
     * @param int $limit Nombre maximum de templates à récupérer
     * @return WhatsAppTemplateType[]
     */
    #[Query(name: "mostUsedWhatsAppTemplates")]
    #[Logged]
    public function getMostUsedTemplates(
        int $limit = 10,
        #[InjectUser] ?User $user = null
    ): array {
        if (!$user) {
            throw new GraphQLException("Authentification requise", 401);
        }

        try {
            // Obtenir les templates via le client REST
            $templates = $this->restClient->getApprovedTemplates($user, [
                'status' => 'APPROVED',
                'useCache' => true // Utiliser le cache pour optimiser les performances
            ]);
            
            // Convertir les templates en types GraphQL
            $templateTypes = [];
            foreach ($templates as $template) {
                try {
                    $templateType = new WhatsAppTemplateSafeType($template);
                    // Ne garder que les templates qui ont été utilisés au moins une fois
                    if ($templateType->getUsageCount() > 0) {
                        $templateTypes[] = $templateType;
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('Erreur lors de la création du type template', [
                        'template' => $template,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }
            
            // Trier par nombre d'utilisations décroissant
            usort($templateTypes, function($a, $b) {
                return $b->getUsageCount() - $a->getUsageCount();
            });
            
            // Limiter le nombre de résultats
            $templateTypes = array_slice($templateTypes, 0, $limit);
            
            // Log du résultat pour monitoring
            $this->logger->info('Récupération des templates les plus utilisés', [
                'limit' => $limit,
                'count' => count($templateTypes)
            ]);
            
            return $templateTypes;
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération templates les plus utilisés', [
                'error' => $e->getMessage(),
                'user' => $user->getId() ?? 'unknown'
            ]);
            
            // En cas d'erreur, retournez un tableau vide
            return [];
        }
    }

    /**
     * Envoie un message WhatsApp basé sur un template
     */
    #[Mutation(name: "sendWhatsAppTemplate")]
    #[Logged]
    public function sendWhatsAppTemplate(
        SendTemplateInput $input,
        #[InjectUser] ?User $user = null
    ): array {
        if (!$user) {
            throw new GraphQLException("Authentification requise", 401);
        }

        try {
            // Décoder les composants du template
            $templateComponents = json_decode($input->templateComponentsJsonString, true);
            if (!$templateComponents && $input->templateComponentsJsonString) {
                throw new GraphQLException("Format de composants de template invalide");
            }

            // Préparer les données dynamiques
            $templateDynamicData = [];

            // Ajouter le média d'en-tête si présent
            if ($input->headerMediaUrl) {
                $templateDynamicData['header'] = ['link' => $input->headerMediaUrl];
            }

            // Ajouter les variables du corps
            if (!empty($input->bodyVariables)) {
                $templateDynamicData['body'] = $input->bodyVariables;
            }

            // Ajouter les variables de boutons
            if (!empty($input->buttonVariables)) {
                $templateDynamicData['buttons'] = $input->buttonVariables;
            }

            // Construire les composants pour l'API
            $components = $this->templateService->buildTemplateComponents(
                $templateComponents,
                $templateDynamicData
            );

            // Envoyer le message template
            $result = $this->whatsAppService->sendTemplateMessageWithComponents(
                $user,
                $input->recipientPhoneNumber,
                $input->templateName,
                $input->templateLanguage,
                $components
            );

            return [
                'success' => true,
                'messageId' => $result['messages'][0]['id'] ?? null,
                'error' => null
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi template WhatsApp', [
                'error' => $e->getMessage(),
                'user' => $user->getId(),
                'template' => $input->templateName,
                'recipient' => $input->recipientPhoneNumber
            ]);
            
            return [
                'success' => false,
                'messageId' => null,
                'error' => $e->getMessage()
            ];
        }
    }
}
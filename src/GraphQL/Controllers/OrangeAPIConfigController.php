<?php

namespace App\GraphQL\Controllers;

use App\Models\OrangeAPIConfig;
use App\Repositories\OrangeAPIConfigRepository;
use App\Repositories\UserRepository;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Contrôleur GraphQL pour la gestion des configurations de l'API Orange
 * 
 * @Type
 */
class OrangeAPIConfigController
{
    private $orangeAPIConfigRepository;
    private $userRepository;

    public function __construct(
        OrangeAPIConfigRepository $orangeAPIConfigRepository,
        UserRepository $userRepository
    ) {
        $this->orangeAPIConfigRepository = $orangeAPIConfigRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Récupère toutes les configurations de l'API Orange
     * 
     * @Query
     * @return OrangeAPIConfig[]
     */
    public function orangeAPIConfigs(): array
    {
        // TODO: Ajouter une vérification d'authentification (admin uniquement)
        return $this->orangeAPIConfigRepository->findAll();
    }

    /**
     * Récupère une configuration de l'API Orange par son ID
     * 
     * @Query
     * @param int $id
     * @return OrangeAPIConfig|null
     */
    public function orangeAPIConfig(int $id): ?OrangeAPIConfig
    {
        // TODO: Ajouter une vérification d'authentification
        return $this->orangeAPIConfigRepository->findById($id);
    }

    /**
     * Récupère la configuration de l'API Orange d'un utilisateur
     * 
     * @Query
     * @param int $userId
     * @return OrangeAPIConfig|null
     */
    public function orangeAPIConfigByUser(int $userId): ?OrangeAPIConfig
    {
        // TODO: Ajouter une vérification d'authentification
        return $this->orangeAPIConfigRepository->findByUserId($userId);
    }

    /**
     * Récupère la configuration de l'API Orange de l'administrateur
     * 
     * @Query
     * @return OrangeAPIConfig|null
     */
    public function adminOrangeAPIConfig(): ?OrangeAPIConfig
    {
        // TODO: Ajouter une vérification d'authentification (admin uniquement)
        return $this->orangeAPIConfigRepository->findAdminConfig();
    }

    /**
     * Crée une nouvelle configuration de l'API Orange
     * 
     * @Mutation
     * @param int|null $userId
     * @param string $clientId
     * @param string $clientSecret
     * @param bool $isAdmin
     * @return OrangeAPIConfig
     */
    public function createOrangeAPIConfig(
        ?int $userId,
        string $clientId,
        string $clientSecret,
        bool $isAdmin = false
    ): OrangeAPIConfig {
        // TODO: Ajouter une vérification d'authentification (admin uniquement)

        // Vérifier si l'utilisateur existe (si userId est fourni)
        if ($userId !== null) {
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                throw new \Exception("Utilisateur non trouvé");
            }

            // Vérifier si l'utilisateur a déjà une configuration
            $existingConfig = $this->orangeAPIConfigRepository->findByUserId($userId);
            if ($existingConfig) {
                throw new \Exception("L'utilisateur a déjà une configuration de l'API Orange");
            }
        }

        // Vérifier si une configuration admin existe déjà (si isAdmin est true)
        if ($isAdmin) {
            $existingAdminConfig = $this->orangeAPIConfigRepository->findAdminConfig();
            if ($existingAdminConfig) {
                throw new \Exception("Une configuration admin existe déjà");
            }
        }

        // Créer la configuration
        $orangeAPIConfig = new OrangeAPIConfig(null, $userId, $clientId, $clientSecret, $isAdmin);

        // Sauvegarder la configuration
        return $this->orangeAPIConfigRepository->save($orangeAPIConfig);
    }

    /**
     * Met à jour une configuration de l'API Orange
     * 
     * @Mutation
     * @param int $id
     * @param string $clientId
     * @param string $clientSecret
     * @return OrangeAPIConfig
     */
    public function updateOrangeAPIConfig(
        int $id,
        string $clientId,
        string $clientSecret
    ): OrangeAPIConfig {
        // TODO: Ajouter une vérification d'authentification (admin uniquement)

        // Récupérer la configuration
        $orangeAPIConfig = $this->orangeAPIConfigRepository->findById($id);
        if (!$orangeAPIConfig) {
            throw new \Exception("Configuration de l'API Orange non trouvée");
        }

        // Mettre à jour les champs
        $orangeAPIConfig->setClientId($clientId);
        $orangeAPIConfig->setClientSecret($clientSecret);

        // Sauvegarder la configuration
        return $this->orangeAPIConfigRepository->save($orangeAPIConfig);
    }

    /**
     * Supprime une configuration de l'API Orange
     * 
     * @Mutation
     * @param int $id
     * @return bool
     */
    public function deleteOrangeAPIConfig(int $id): bool
    {
        // TODO: Ajouter une vérification d'authentification (admin uniquement)

        // Récupérer la configuration
        $orangeAPIConfig = $this->orangeAPIConfigRepository->findById($id);
        if (!$orangeAPIConfig) {
            throw new \Exception("Configuration de l'API Orange non trouvée");
        }

        // Supprimer la configuration
        return $this->orangeAPIConfigRepository->delete($id);
    }
}

<?php

namespace App\GraphQL\Controllers;

use App\Exceptions\ValidationException;
use App\Models\SMSTemplate;
use App\Repositories\Interfaces\SMSTemplateRepositoryInterface;
use App\Services\Validators\SMSTemplateValidator;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Right;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Contrôleur GraphQL pour les modèles de SMS
 */
class SMSTemplateController
{
    /**
     * Repository pour les modèles de SMS
     * 
     * @var SMSTemplateRepositoryInterface
     */
    private $repository;

    /**
     * Validateur pour les modèles de SMS
     * 
     * @var SMSTemplateValidator
     */
    private $validator;

    /**
     * Constructeur
     * 
     * @param SMSTemplateRepositoryInterface $repository Repository pour les modèles de SMS
     * @param SMSTemplateValidator $validator Validateur pour les modèles de SMS
     */
    public function __construct(
        SMSTemplateRepositoryInterface $repository,
        SMSTemplateValidator $validator
    ) {
        $this->repository = $repository;
        $this->validator = $validator;
    }

    /**
     * Récupérer tous les modèles de SMS d'un utilisateur
     * 
     * @Query
     * @Logged
     * @param int $userId ID de l'utilisateur
     * @param int $limit Limite de résultats (pagination)
     * @param int $offset Offset de résultats (pagination)
     * @return SMSTemplate[]
     */
    public function getSMSTemplatesByUserId(int $userId, int $limit = 10, int $offset = 0): array
    {
        return $this->repository->findByUserId($userId, $limit, $offset);
    }

    /**
     * Récupérer un modèle de SMS par son ID
     * 
     * @Query
     * @Logged
     * @param ID $id ID du modèle
     * @return SMSTemplate|null
     */
    public function getSMSTemplate(ID $id): ?SMSTemplate
    {
        return $this->repository->findById((int) $id);
    }

    /**
     * Rechercher des modèles de SMS par titre ou contenu
     * 
     * @Query
     * @Logged
     * @param int $userId ID de l'utilisateur
     * @param string $search Terme de recherche
     * @param int $limit Limite de résultats (pagination)
     * @param int $offset Offset de résultats (pagination)
     * @return SMSTemplate[]
     */
    public function searchSMSTemplates(int $userId, string $search, int $limit = 10, int $offset = 0): array
    {
        return $this->repository->searchByUser($userId, $search, $limit, $offset);
    }

    /**
     * Compter le nombre de modèles de SMS d'un utilisateur
     * 
     * @Query
     * @Logged
     * @param int $userId ID de l'utilisateur
     * @return int
     */
    public function countSMSTemplatesByUserId(int $userId): int
    {
        return $this->repository->countByUserId($userId);
    }

    /**
     * Créer un nouveau modèle de SMS
     * 
     * @Mutation
     * @Logged
     * @param string $title Titre du modèle
     * @param string $content Contenu du modèle
     * @param int $userId ID de l'utilisateur propriétaire
     * @param string|null $description Description du modèle (optionnel)
     * @return SMSTemplate
     * @throws ValidationException Si les données sont invalides
     */
    public function createSMSTemplate(
        string $title,
        string $content,
        int $userId,
        ?string $description = null
    ): SMSTemplate {
        $data = [
            'title' => $title,
            'content' => $content,
            'userId' => $userId,
            'description' => $description
        ];

        $validatedData = $this->validator->validateCreate($data);
        $template = $this->validator->createFromValidatedData($validatedData);

        return $this->repository->create($template);
    }

    /**
     * Mettre à jour un modèle de SMS existant
     * 
     * @Mutation
     * @Logged
     * @param ID $id ID du modèle à mettre à jour
     * @param string $title Nouveau titre
     * @param string $content Nouveau contenu
     * @param int $userId ID de l'utilisateur propriétaire
     * @param string|null $description Nouvelle description (optionnel)
     * @return SMSTemplate
     * @throws ValidationException Si les données sont invalides
     */
    public function updateSMSTemplate(
        ID $id,
        string $title,
        string $content,
        int $userId,
        ?string $description = null
    ): SMSTemplate {
        $data = [
            'title' => $title,
            'content' => $content,
            'userId' => $userId,
            'description' => $description
        ];

        $validatedData = $this->validator->validateUpdate((int) $id, $data);
        $template = $this->validator->createFromValidatedData($validatedData);

        $this->repository->update($template);

        return $template;
    }

    /**
     * Supprimer un modèle de SMS
     * 
     * @Mutation
     * @Logged
     * @param ID $id ID du modèle à supprimer
     * @return bool
     * @throws ValidationException Si l'ID est invalide
     */
    public function deleteSMSTemplate(ID $id): bool
    {
        $validatedData = $this->validator->validateDelete((int) $id);

        return $this->repository->deleteById((int) $id);
    }
}

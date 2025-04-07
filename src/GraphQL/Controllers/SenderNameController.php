<?php

namespace App\GraphQL\Controllers;

use App\Models\SenderName;
use App\Repositories\SenderNameRepository;
use App\Repositories\UserRepository;
use App\Services\Validators\SenderNameValidator;
use App\Exceptions\ValidationException;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Right;

/**
 * Contrôleur GraphQL pour les noms d'expéditeur
 */
class SenderNameController
{
    /**
     * @var SenderNameRepository
     */
    private $senderNameRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var SenderNameValidator
     */
    private $senderNameValidator;

    /**
     * Constructeur
     * 
     * @param SenderNameRepository $senderNameRepository
     * @param UserRepository $userRepository
     * @param SenderNameValidator $senderNameValidator
     */
    public function __construct(
        SenderNameRepository $senderNameRepository,
        UserRepository $userRepository,
        SenderNameValidator $senderNameValidator
    ) {
        $this->senderNameRepository = $senderNameRepository;
        $this->userRepository = $userRepository;
        $this->senderNameValidator = $senderNameValidator;
    }

    /**
     * Récupérer un nom d'expéditeur par son ID
     * 
     * @Query
     * @param int $id
     * @return SenderName
     */
    public function senderName(int $id): ?SenderName
    {
        return $this->senderNameRepository->findById($id);
    }

    /**
     * Récupérer tous les noms d'expéditeur
     * 
     * @Query
     * @return SenderName[]
     */
    public function senderNames(): array
    {
        return $this->senderNameRepository->findAll();
    }

    /**
     * Récupérer les noms d'expéditeur d'un utilisateur
     * 
     * @Query
     * @param int $userId
     * @return SenderName[]
     */
    public function userSenderNames(int $userId): array
    {
        try {
            // Vérifier que l'utilisateur existe
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                throw new \Exception("Utilisateur non trouvé");
            }

            return $this->senderNameRepository->findByUserId($userId);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Créer un nouveau nom d'expéditeur
     * 
     * @Mutation
     * @param int $userId
     * @param string $name
     * @return SenderName
     */
    public function createSenderName(int $userId, string $name): SenderName
    {
        try {
            // Valider les données
            $validatedData = $this->senderNameValidator->validateRequest($userId, $name);

            // Créer le nom d'expéditeur
            $senderName = new SenderName(
                $validatedData['userId'],
                $validatedData['name'],
                'pending'
            );

            // Sauvegarder le nom d'expéditeur
            return $this->senderNameRepository->save($senderName);
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }
    }

    /**
     * Approuver un nom d'expéditeur
     * 
     * @Mutation
     * @param int $id
     * @return SenderName
     */
    public function approveSenderName(int $id): SenderName
    {
        try {
            // Valider les données
            $validatedData = $this->senderNameValidator->validateApproval($id);

            // Récupérer le nom d'expéditeur
            $senderName = $this->senderNameRepository->findById($validatedData['id']);
            if (!$senderName) {
                throw new \Exception("Nom d'expéditeur non trouvé");
            }

            // Mettre à jour le statut
            $senderName->setStatus('approved');

            // Sauvegarder le nom d'expéditeur
            return $this->senderNameRepository->save($senderName);
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }
    }

    /**
     * Rejeter un nom d'expéditeur
     * 
     * @Mutation
     * @param int $id
     * @return SenderName
     */
    public function rejectSenderName(int $id): SenderName
    {
        try {
            // Valider les données
            $validatedData = $this->senderNameValidator->validateRejection($id);

            // Récupérer le nom d'expéditeur
            $senderName = $this->senderNameRepository->findById($validatedData['id']);
            if (!$senderName) {
                throw new \Exception("Nom d'expéditeur non trouvé");
            }

            // Mettre à jour le statut
            $senderName->setStatus('rejected');

            // Sauvegarder le nom d'expéditeur
            return $this->senderNameRepository->save($senderName);
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }
    }

    /**
     * Supprimer un nom d'expéditeur
     * 
     * @Mutation
     * @param int $id
     * @return bool
     */
    public function deleteSenderName(int $id): bool
    {
        try {
            // Valider les données
            $validatedData = $this->senderNameValidator->validateDelete($id);

            // Supprimer le nom d'expéditeur
            return $this->senderNameRepository->delete($validatedData['id']);
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }
    }

    /**
     * Récupérer les noms d'expéditeur en attente
     * 
     * @Query
     * @return SenderName[]
     */
    public function pendingSenderNames(): array
    {
        return $this->senderNameRepository->findByStatus('pending');
    }

    /**
     * Récupérer les noms d'expéditeur approuvés
     * 
     * @Query
     * @return SenderName[]
     */
    public function approvedSenderNames(): array
    {
        return $this->senderNameRepository->findByStatus('approved');
    }

    /**
     * Récupérer les noms d'expéditeur rejetés
     * 
     * @Query
     * @return SenderName[]
     */
    public function rejectedSenderNames(): array
    {
        return $this->senderNameRepository->findByStatus('rejected');
    }
}

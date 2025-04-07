<?php

namespace App\GraphQL\Controllers;

use App\Models\SMSOrder;
use App\Models\User;
use App\Repositories\SMSOrderRepository;
use App\Repositories\UserRepository;
use App\Services\Validators\SMSOrderValidator;
use App\Exceptions\ValidationException;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Right;

/**
 * Contrôleur GraphQL pour les commandes de crédits SMS
 */
class SMSOrderController
{
    /**
     * @var SMSOrderRepository
     */
    private $smsOrderRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var SMSOrderValidator
     */
    private $smsOrderValidator;

    /**
     * Constructeur
     * 
     * @param SMSOrderRepository $smsOrderRepository
     * @param UserRepository $userRepository
     * @param SMSOrderValidator $smsOrderValidator
     */
    public function __construct(
        SMSOrderRepository $smsOrderRepository,
        UserRepository $userRepository,
        SMSOrderValidator $smsOrderValidator
    ) {
        $this->smsOrderRepository = $smsOrderRepository;
        $this->userRepository = $userRepository;
        $this->smsOrderValidator = $smsOrderValidator;
    }

    /**
     * Récupérer une commande de crédits SMS par son ID
     * 
     * @Query
     * @param int $id
     * @return SMSOrder
     */
    public function smsOrder(int $id): ?SMSOrder
    {
        return $this->smsOrderRepository->findById($id);
    }

    /**
     * Récupérer toutes les commandes de crédits SMS
     * 
     * @Query
     * @return SMSOrder[]
     */
    public function smsOrders(): array
    {
        return $this->smsOrderRepository->findAll();
    }

    /**
     * Récupérer les commandes de crédits SMS d'un utilisateur
     * 
     * @Query
     * @param int $userId
     * @return SMSOrder[]
     */
    public function userSMSOrders(int $userId): array
    {
        try {
            // Vérifier que l'utilisateur existe
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                throw new \Exception("Utilisateur non trouvé");
            }

            return $this->smsOrderRepository->findByUserId($userId);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Créer une nouvelle commande de crédits SMS
     * 
     * @Mutation
     * @param int $userId
     * @param int $quantity
     * @return SMSOrder
     */
    public function createSMSOrder(int $userId, int $quantity): SMSOrder
    {
        try {
            // Valider les données
            $validatedData = $this->smsOrderValidator->validateOrderCreation($userId, $quantity);

            // Créer la commande
            $smsOrder = new SMSOrder(
                $validatedData['userId'],
                $validatedData['quantity'],
                'pending'
            );

            // Sauvegarder la commande
            return $this->smsOrderRepository->save($smsOrder);
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }
    }

    /**
     * Compléter une commande de crédits SMS
     * 
     * @Mutation
     * @param int $id
     * @return SMSOrder
     */
    public function completeSMSOrder(int $id): SMSOrder
    {
        try {
            // Valider les données
            $validatedData = $this->smsOrderValidator->validateCompletion($id);

            // Récupérer la commande
            $smsOrder = $this->smsOrderRepository->findById($validatedData['id']);
            if (!$smsOrder) {
                throw new \Exception("Commande de crédits SMS non trouvée");
            }

            // Récupérer l'utilisateur
            $user = $this->userRepository->findById($smsOrder->getUserId());
            if (!$user) {
                throw new \Exception("Utilisateur non trouvé");
            }

            // Mettre à jour le statut de la commande
            $smsOrder->setStatus('completed');

            // Ajouter les crédits à l'utilisateur
            $user->setSmsCredit($user->getSmsCredit() + $smsOrder->getQuantity());

            // Sauvegarder l'utilisateur
            $this->userRepository->save($user);

            // Sauvegarder la commande
            return $this->smsOrderRepository->save($smsOrder);
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }
    }

    /**
     * Supprimer une commande de crédits SMS
     * 
     * @Mutation
     * @param int $id
     * @return bool
     */
    public function deleteSMSOrder(int $id): bool
    {
        try {
            // Valider les données
            $validatedData = $this->smsOrderValidator->validateDelete($id);

            // Supprimer la commande
            return $this->smsOrderRepository->delete($validatedData['id']);
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }
    }

    /**
     * Récupérer les commandes de crédits SMS en attente
     * 
     * @Query
     * @return SMSOrder[]
     */
    public function pendingSMSOrders(): array
    {
        return $this->smsOrderRepository->findByStatus('pending');
    }

    /**
     * Récupérer les commandes de crédits SMS complétées
     * 
     * @Query
     * @return SMSOrder[]
     */
    public function completedSMSOrders(): array
    {
        return $this->smsOrderRepository->findByStatus('completed');
    }

    /**
     * Mettre à jour la quantité d'une commande de crédits SMS
     * 
     * @Mutation
     * @param int $id
     * @param int $quantity
     * @return SMSOrder
     */
    public function updateSMSOrderQuantity(int $id, int $quantity): SMSOrder
    {
        try {
            // Valider les données
            $validatedData = $this->smsOrderValidator->validateUpdate($id, ['quantity' => $quantity]);

            // Récupérer la commande
            $smsOrder = $this->smsOrderRepository->findById($validatedData['id']);
            if (!$smsOrder) {
                throw new \Exception("Commande de crédits SMS non trouvée");
            }

            // Vérifier que la commande n'est pas déjà complétée
            if ($smsOrder->getStatus() === 'completed') {
                throw new \Exception("Impossible de modifier une commande complétée");
            }

            // Mettre à jour la quantité
            $smsOrder->setQuantity($validatedData['quantity']);

            // Sauvegarder la commande
            return $this->smsOrderRepository->save($smsOrder);
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }
    }
}

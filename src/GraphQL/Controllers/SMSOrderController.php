<?php

namespace App\GraphQL\Controllers;

use App\Entities\SMSOrder; // Use Doctrine Entity
use App\Entities\User; // Use Doctrine Entity
use App\Repositories\Interfaces\SMSOrderRepositoryInterface; // Use Interface
use App\Repositories\Interfaces\UserRepositoryInterface; // Use Interface
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
     * @var SMSOrderRepositoryInterface
     */
    private $smsOrderRepository; // Use Interface

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository; // Use Interface

    /**
     * @var SMSOrderValidator
     */
    private $smsOrderValidator;

    /**
     * Constructeur
     * 
     * @param SMSOrderRepositoryInterface $smsOrderRepository // Use Interface
     * @param UserRepositoryInterface $userRepository // Use Interface
     * @param SMSOrderValidator $smsOrderValidator
     */
    public function __construct(
        SMSOrderRepositoryInterface $smsOrderRepository, // Use Interface
        UserRepositoryInterface $userRepository, // Use Interface
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
     * @return ?SMSOrder // Return Doctrine Entity
     */
    public function smsOrder(int $id): ?SMSOrder // Return Doctrine Entity
    {
        return $this->smsOrderRepository->findById($id);
    }

    /**
     * Récupérer toutes les commandes de crédits SMS
     * 
     * @Query
     * @return SMSOrder[] // Return array of Doctrine Entities
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
     * @return SMSOrder[] // Return array of Doctrine Entities
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
     * @return SMSOrder // Return Doctrine Entity
     */
    public function createSMSOrder(int $userId, int $quantity): SMSOrder // Return Doctrine Entity
    {
        try {
            // Valider les données
            $validatedData = $this->smsOrderValidator->validateOrderCreation($userId, $quantity);

            // Créer la commande (Instantiate Entity and use setters)
            $smsOrder = new SMSOrder();
            $smsOrder->setUserId($validatedData['userId']);
            $smsOrder->setQuantity($validatedData['quantity']);
            $smsOrder->setStatus('pending');
            $smsOrder->setCreatedAt(new \DateTime()); // Assuming createdAt is set here or in save

            // Sauvegarder la commande (Save Entity)
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
     * @return SMSOrder // Return Doctrine Entity
     */
    public function completeSMSOrder(int $id): SMSOrder // Return Doctrine Entity
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
     * @return SMSOrder[] // Return array of Doctrine Entities
     */
    public function pendingSMSOrders(): array
    {
        return $this->smsOrderRepository->findByStatus('pending');
    }

    /**
     * Récupérer les commandes de crédits SMS complétées
     * 
     * @Query
     * @return SMSOrder[] // Return array of Doctrine Entities
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
     * @return SMSOrder // Return Doctrine Entity
     */
    public function updateSMSOrderQuantity(int $id, int $quantity): SMSOrder // Return Doctrine Entity
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

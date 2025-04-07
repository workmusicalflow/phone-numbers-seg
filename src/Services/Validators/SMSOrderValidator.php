<?php

namespace App\Services\Validators;

use App\Repositories\SMSOrderRepository;
use App\Repositories\UserRepository;
use App\Exceptions\ValidationException;

/**
 * Validateur pour les commandes de crédits SMS
 * 
 * Cette classe est responsable de la validation des commandes de crédits SMS
 * et de leurs métadonnées associées.
 */
class SMSOrderValidator extends AbstractValidator
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
     * Constructeur
     * 
     * @param SMSOrderRepository $smsOrderRepository
     * @param UserRepository $userRepository
     */
    public function __construct(
        SMSOrderRepository $smsOrderRepository,
        UserRepository $userRepository
    ) {
        $this->smsOrderRepository = $smsOrderRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Valide les données pour la création d'une commande de crédits SMS
     * 
     * @param array $data Données à valider
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateCreate(array $data): array
    {
        $errors = $this->validateCreateData($data);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la commande de crédits SMS échouée", $errors);
        }

        return $data;
    }

    /**
     * Valide les données pour la mise à jour d'une commande de crédits SMS
     * 
     * @param int $id ID de la commande à mettre à jour
     * @param array $data Données à valider
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateUpdate(int $id, array $data): array
    {
        $errors = $this->validateUpdateData($id, $data);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la mise à jour de la commande de crédits SMS échouée", $errors);
        }

        return array_merge(['id' => $id], $data);
    }

    /**
     * Valide les données pour la suppression d'une commande de crédits SMS
     * 
     * @param int $id ID de la commande à supprimer
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateDelete(int $id): array
    {
        $errors = $this->validateDeleteData($id);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la suppression de la commande de crédits SMS échouée", $errors);
        }

        return ['id' => $id];
    }

    /**
     * Implémentation de la méthode abstraite pour valider les données de création
     * 
     * @param array $data
     * @return array Tableau d'erreurs (vide si aucune erreur)
     */
    protected function validateCreateData(array $data): array
    {
        $errors = [];

        // Validation de l'utilisateur
        if (empty($data['userId'])) {
            $errors['userId'] = "L'ID de l'utilisateur est requis";
        } elseif (!$this->userRepository->findById($data['userId'])) {
            $errors['userId'] = "L'utilisateur n'existe pas";
        }

        // Validation de la quantité
        if (empty($data['quantity'])) {
            $errors['quantity'] = "La quantité est requise";
        } elseif (!is_numeric($data['quantity']) || $data['quantity'] <= 0) {
            $errors['quantity'] = "La quantité doit être un nombre positif";
        } elseif ($data['quantity'] > 10000) {
            $errors['quantity'] = "La quantité ne peut pas dépasser 10000";
        }

        // Validation du statut (optionnel)
        if (isset($data['status']) && !in_array($data['status'], ['pending', 'completed'])) {
            $errors['status'] = "Le statut doit être 'pending' ou 'completed'";
        }

        return $errors;
    }

    /**
     * Implémentation de la méthode abstraite pour valider les données de mise à jour
     * 
     * @param int $id
     * @param array $data
     * @return array Tableau d'erreurs (vide si aucune erreur)
     */
    protected function validateUpdateData(int $id, array $data): array
    {
        $errors = [];

        // Vérifier que la commande existe
        $smsOrder = $this->smsOrderRepository->findById($id);
        if (!$smsOrder) {
            $errors['id'] = "La commande de crédits SMS n'existe pas";
            return $errors;
        }

        // Validation du statut
        if (isset($data['status']) && !in_array($data['status'], ['pending', 'completed'])) {
            $errors['status'] = "Le statut doit être 'pending' ou 'completed'";
        }

        // Validation de la quantité (si présente)
        if (isset($data['quantity'])) {
            if (!is_numeric($data['quantity']) || $data['quantity'] <= 0) {
                $errors['quantity'] = "La quantité doit être un nombre positif";
            } elseif ($data['quantity'] > 10000) {
                $errors['quantity'] = "La quantité ne peut pas dépasser 10000";
            }
        }

        return $errors;
    }

    /**
     * Implémentation de la méthode abstraite pour valider les données de suppression
     * 
     * @param int $id
     * @return array Tableau d'erreurs (vide si aucune erreur)
     */
    protected function validateDeleteData(int $id): array
    {
        $errors = [];

        // Vérifier que la commande existe
        $smsOrder = $this->smsOrderRepository->findById($id);
        if (!$smsOrder) {
            $errors['id'] = "La commande de crédits SMS n'existe pas";
        }

        // Vérifier que la commande n'est pas déjà complétée
        if ($smsOrder && $smsOrder->getStatus() === 'completed') {
            $errors['id'] = "Une commande complétée ne peut pas être supprimée";
        }

        return $errors;
    }

    /**
     * Valide les données pour la complétion d'une commande de crédits SMS
     * 
     * @param int $id
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateCompletion(int $id): array
    {
        $errors = [];

        // Vérifier que la commande existe
        $smsOrder = $this->smsOrderRepository->findById($id);
        if (!$smsOrder) {
            $errors['id'] = "La commande de crédits SMS n'existe pas";
            throw new ValidationException("Validation de la complétion de la commande de crédits SMS échouée", $errors);
        }

        // Vérifier que la commande n'est pas déjà complétée
        if ($smsOrder->getStatus() === 'completed') {
            $errors['id'] = "La commande est déjà complétée";
            throw new ValidationException("Validation de la complétion de la commande de crédits SMS échouée", $errors);
        }

        return ['id' => $id];
    }

    /**
     * Valide les données pour la création d'une commande de crédits SMS par un utilisateur
     * 
     * @param int $userId
     * @param int $quantity
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateOrderCreation(int $userId, int $quantity): array
    {
        $data = [
            'userId' => $userId,
            'quantity' => $quantity
        ];

        $errors = $this->validateCreateData($data);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la création de la commande de crédits SMS échouée", $errors);
        }

        return $data;
    }
}

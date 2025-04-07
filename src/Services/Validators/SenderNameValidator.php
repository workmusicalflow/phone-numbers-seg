<?php

namespace App\Services\Validators;

use App\Repositories\SenderNameRepository;
use App\Repositories\UserRepository;
use App\Exceptions\ValidationException;

/**
 * Validateur pour les noms d'expéditeur
 * 
 * Cette classe est responsable de la validation des noms d'expéditeur
 * et de leurs métadonnées associées.
 */
class SenderNameValidator extends AbstractValidator
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
     * Constructeur
     * 
     * @param SenderNameRepository $senderNameRepository
     * @param UserRepository $userRepository
     */
    public function __construct(
        SenderNameRepository $senderNameRepository,
        UserRepository $userRepository
    ) {
        $this->senderNameRepository = $senderNameRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Valide les données pour la création d'un nom d'expéditeur
     * 
     * @param array $data Données à valider
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateCreate(array $data): array
    {
        $errors = $this->validateCreateData($data);

        if (!empty($errors)) {
            throw new ValidationException("Validation du nom d'expéditeur échouée", $errors);
        }

        return $data;
    }

    /**
     * Valide les données pour la mise à jour d'un nom d'expéditeur
     * 
     * @param int $id ID du nom d'expéditeur à mettre à jour
     * @param array $data Données à valider
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateUpdate(int $id, array $data): array
    {
        $errors = $this->validateUpdateData($id, $data);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la mise à jour du nom d'expéditeur échouée", $errors);
        }

        return array_merge(['id' => $id], $data);
    }

    /**
     * Valide les données pour la suppression d'un nom d'expéditeur
     * 
     * @param int $id ID du nom d'expéditeur à supprimer
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateDelete(int $id): array
    {
        $errors = $this->validateDeleteData($id);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la suppression du nom d'expéditeur échouée", $errors);
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

        // Validation du nom d'expéditeur
        if (empty($data['name'])) {
            $errors['name'] = "Le nom d'expéditeur est requis";
        } elseif (strlen($data['name']) < 3) {
            $errors['name'] = "Le nom d'expéditeur doit contenir au moins 3 caractères";
        } elseif (strlen($data['name']) > 11) {
            $errors['name'] = "Le nom d'expéditeur ne doit pas dépasser 11 caractères";
        } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $data['name'])) {
            $errors['name'] = "Le nom d'expéditeur ne doit contenir que des lettres et des chiffres";
        } elseif ($this->senderNameRepository->findByName($data['name'])) {
            $errors['name'] = "Ce nom d'expéditeur existe déjà";
        }

        // Validation de l'utilisateur
        if (empty($data['userId'])) {
            $errors['userId'] = "L'ID de l'utilisateur est requis";
        } elseif (!$this->userRepository->findById($data['userId'])) {
            $errors['userId'] = "L'utilisateur n'existe pas";
        } else {
            // Vérifier que l'utilisateur n'a pas déjà 2 noms d'expéditeur
            $existingSenderNames = $this->senderNameRepository->findByUserId($data['userId']);
            if (count($existingSenderNames) >= 2) {
                $errors['userId'] = "L'utilisateur a déjà atteint le nombre maximum de noms d'expéditeur (2)";
            }
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

        // Vérifier que le nom d'expéditeur existe
        $senderName = $this->senderNameRepository->findById($id);
        if (!$senderName) {
            $errors['id'] = "Le nom d'expéditeur n'existe pas";
            return $errors;
        }

        // Validation du statut
        if (isset($data['status']) && !in_array($data['status'], ['pending', 'approved', 'rejected'])) {
            $errors['status'] = "Le statut doit être 'pending', 'approved' ou 'rejected'";
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

        // Vérifier que le nom d'expéditeur existe
        $senderName = $this->senderNameRepository->findById($id);
        if (!$senderName) {
            $errors['id'] = "Le nom d'expéditeur n'existe pas";
        }

        return $errors;
    }

    /**
     * Valide les données pour la demande d'un nom d'expéditeur
     * 
     * @param int $userId
     * @param string $name
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateRequest(int $userId, string $name): array
    {
        $data = [
            'userId' => $userId,
            'name' => $name
        ];

        $errors = $this->validateCreateData($data);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la demande de nom d'expéditeur échouée", $errors);
        }

        return $data;
    }

    /**
     * Valide les données pour l'approbation d'un nom d'expéditeur
     * 
     * @param int $id
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateApproval(int $id): array
    {
        $errors = [];

        // Vérifier que le nom d'expéditeur existe
        $senderName = $this->senderNameRepository->findById($id);
        if (!$senderName) {
            $errors['id'] = "Le nom d'expéditeur n'existe pas";
            throw new ValidationException("Validation de l'approbation du nom d'expéditeur échouée", $errors);
        }

        // Vérifier que le nom d'expéditeur est en attente
        if ($senderName->getStatus() !== 'pending') {
            $errors['id'] = "Le nom d'expéditeur n'est pas en attente d'approbation";
            throw new ValidationException("Validation de l'approbation du nom d'expéditeur échouée", $errors);
        }

        return ['id' => $id];
    }

    /**
     * Valide les données pour le rejet d'un nom d'expéditeur
     * 
     * @param int $id
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateRejection(int $id): array
    {
        $errors = [];

        // Vérifier que le nom d'expéditeur existe
        $senderName = $this->senderNameRepository->findById($id);
        if (!$senderName) {
            $errors['id'] = "Le nom d'expéditeur n'existe pas";
            throw new ValidationException("Validation du rejet du nom d'expéditeur échouée", $errors);
        }

        // Vérifier que le nom d'expéditeur est en attente
        if ($senderName->getStatus() !== 'pending') {
            $errors['id'] = "Le nom d'expéditeur n'est pas en attente d'approbation";
            throw new ValidationException("Validation du rejet du nom d'expéditeur échouée", $errors);
        }

        return ['id' => $id];
    }
}

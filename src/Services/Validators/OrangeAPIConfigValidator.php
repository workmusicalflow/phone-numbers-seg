<?php

namespace App\Services\Validators;

use App\Repositories\OrangeAPIConfigRepository;
use App\Repositories\UserRepository;
use App\Exceptions\ValidationException;

/**
 * Validateur pour les configurations de l'API Orange
 * 
 * Cette classe est responsable de la validation des configurations de l'API Orange
 * et de leurs métadonnées associées.
 */
class OrangeAPIConfigValidator extends AbstractValidator
{
    /**
     * @var OrangeAPIConfigRepository
     */
    private $orangeAPIConfigRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * Constructeur
     * 
     * @param OrangeAPIConfigRepository $orangeAPIConfigRepository
     * @param UserRepository $userRepository
     */
    public function __construct(
        OrangeAPIConfigRepository $orangeAPIConfigRepository,
        UserRepository $userRepository
    ) {
        $this->orangeAPIConfigRepository = $orangeAPIConfigRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Valide les données pour la création d'une configuration de l'API Orange
     * 
     * @param array $data Données à valider
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateCreate(array $data): array
    {
        $errors = $this->validateCreateData($data);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la configuration de l'API Orange échouée", $errors);
        }

        return $data;
    }

    /**
     * Valide les données pour la mise à jour d'une configuration de l'API Orange
     * 
     * @param int $id ID de la configuration à mettre à jour
     * @param array $data Données à valider
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateUpdate(int $id, array $data): array
    {
        $errors = $this->validateUpdateData($id, $data);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la mise à jour de la configuration de l'API Orange échouée", $errors);
        }

        return array_merge(['id' => $id], $data);
    }

    /**
     * Valide les données pour la suppression d'une configuration de l'API Orange
     * 
     * @param int $id ID de la configuration à supprimer
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateDelete(int $id): array
    {
        $errors = $this->validateDeleteData($id);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la suppression de la configuration de l'API Orange échouée", $errors);
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

        // Validation du client ID
        if (empty($data['clientId'])) {
            $errors['clientId'] = "L'ID client est requis";
        } elseif (strlen($data['clientId']) < 10) {
            $errors['clientId'] = "L'ID client doit contenir au moins 10 caractères";
        } elseif (strlen($data['clientId']) > 255) {
            $errors['clientId'] = "L'ID client ne doit pas dépasser 255 caractères";
        }

        // Validation du client secret
        if (empty($data['clientSecret'])) {
            $errors['clientSecret'] = "Le secret client est requis";
        } elseif (strlen($data['clientSecret']) < 10) {
            $errors['clientSecret'] = "Le secret client doit contenir au moins 10 caractères";
        } elseif (strlen($data['clientSecret']) > 255) {
            $errors['clientSecret'] = "Le secret client ne doit pas dépasser 255 caractères";
        }

        // Validation de l'utilisateur (si présent)
        if (isset($data['userId']) && $data['userId'] !== null) {
            if (!is_numeric($data['userId'])) {
                $errors['userId'] = "L'ID de l'utilisateur doit être un nombre";
            } elseif (!$this->userRepository->findById($data['userId'])) {
                $errors['userId'] = "L'utilisateur n'existe pas";
            } elseif ($this->orangeAPIConfigRepository->findByUserId($data['userId'])) {
                $errors['userId'] = "Cet utilisateur a déjà une configuration de l'API Orange";
            }
        }

        // Validation du flag admin (optionnel)
        if (isset($data['isAdmin']) && !is_bool($data['isAdmin'])) {
            $errors['isAdmin'] = "Le flag admin doit être un booléen";
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

        // Vérifier que la configuration existe
        $orangeAPIConfig = $this->orangeAPIConfigRepository->findById($id);
        if (!$orangeAPIConfig) {
            $errors['id'] = "La configuration de l'API Orange n'existe pas";
            return $errors;
        }

        // Validation du client ID (si présent)
        if (isset($data['clientId'])) {
            if (empty($data['clientId'])) {
                $errors['clientId'] = "L'ID client est requis";
            } elseif (strlen($data['clientId']) < 10) {
                $errors['clientId'] = "L'ID client doit contenir au moins 10 caractères";
            } elseif (strlen($data['clientId']) > 255) {
                $errors['clientId'] = "L'ID client ne doit pas dépasser 255 caractères";
            }
        }

        // Validation du client secret (si présent)
        if (isset($data['clientSecret'])) {
            if (empty($data['clientSecret'])) {
                $errors['clientSecret'] = "Le secret client est requis";
            } elseif (strlen($data['clientSecret']) < 10) {
                $errors['clientSecret'] = "Le secret client doit contenir au moins 10 caractères";
            } elseif (strlen($data['clientSecret']) > 255) {
                $errors['clientSecret'] = "Le secret client ne doit pas dépasser 255 caractères";
            }
        }

        // Validation du flag admin (si présent)
        if (isset($data['isAdmin']) && !is_bool($data['isAdmin'])) {
            $errors['isAdmin'] = "Le flag admin doit être un booléen";
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

        // Vérifier que la configuration existe
        $orangeAPIConfig = $this->orangeAPIConfigRepository->findById($id);
        if (!$orangeAPIConfig) {
            $errors['id'] = "La configuration de l'API Orange n'existe pas";
        }

        return $errors;
    }

    /**
     * Valide les données pour la création d'une configuration de l'API Orange pour un utilisateur
     * 
     * @param int $userId
     * @param string $clientId
     * @param string $clientSecret
     * @param bool $isAdmin
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateCreateForUser(
        int $userId,
        string $clientId,
        string $clientSecret,
        bool $isAdmin = false
    ): array {
        $data = [
            'userId' => $userId,
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'isAdmin' => $isAdmin
        ];

        $errors = $this->validateCreateData($data);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la création de la configuration de l'API Orange échouée", $errors);
        }

        return $data;
    }

    /**
     * Valide les données pour la création d'une configuration de l'API Orange pour l'administrateur
     * 
     * @param string $clientId
     * @param string $clientSecret
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateCreateForAdmin(string $clientId, string $clientSecret): array
    {
        $data = [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'isAdmin' => true
        ];

        $errors = $this->validateCreateData($data);

        // Vérifier qu'il n'existe pas déjà une configuration admin
        if ($this->orangeAPIConfigRepository->findAdminConfig()) {
            $errors['isAdmin'] = "Une configuration admin existe déjà";
        }

        if (!empty($errors)) {
            throw new ValidationException("Validation de la création de la configuration admin de l'API Orange échouée", $errors);
        }

        return $data;
    }

    /**
     * Valide les données pour tester une configuration de l'API Orange
     * 
     * @param string $clientId
     * @param string $clientSecret
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateTestConfig(string $clientId, string $clientSecret): array
    {
        $errors = [];

        // Validation du client ID
        if (empty($clientId)) {
            $errors['clientId'] = "L'ID client est requis";
        } elseif (strlen($clientId) < 10) {
            $errors['clientId'] = "L'ID client doit contenir au moins 10 caractères";
        } elseif (strlen($clientId) > 255) {
            $errors['clientId'] = "L'ID client ne doit pas dépasser 255 caractères";
        }

        // Validation du client secret
        if (empty($clientSecret)) {
            $errors['clientSecret'] = "Le secret client est requis";
        } elseif (strlen($clientSecret) < 10) {
            $errors['clientSecret'] = "Le secret client doit contenir au moins 10 caractères";
        } elseif (strlen($clientSecret) > 255) {
            $errors['clientSecret'] = "Le secret client ne doit pas dépasser 255 caractères";
        }

        if (!empty($errors)) {
            throw new ValidationException("Validation du test de la configuration de l'API Orange échouée", $errors);
        }

        return [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret
        ];
    }
}

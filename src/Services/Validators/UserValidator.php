<?php

namespace App\Services\Validators;

use App\Repositories\UserRepository;
use App\Exceptions\ValidationException;

/**
 * Validateur pour les utilisateurs
 * 
 * Cette classe est responsable de la validation des données utilisateur
 * comme le nom d'utilisateur, l'email, etc.
 */
class UserValidator extends AbstractValidator
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * Constructeur
     * 
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Prépare les données pour la création d'un utilisateur
     * 
     * @param string $username
     * @param string $password
     * @param string $email
     * @param int $smsCredit
     * @param int $smsLimit
     * @param bool $isAdmin
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function prepareUserCreateData(
        string $username,
        string $password,
        string $email = '',
        int $smsCredit = 0,
        int $smsLimit = 0,
        bool $isAdmin = false
    ): array {
        $data = [
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'smsCredit' => $smsCredit,
            'smsLimit' => $smsLimit,
            'isAdmin' => $isAdmin
        ];

        return $this->validateCreate($data);
    }

    /**
     * Prépare les données pour la mise à jour d'un utilisateur
     * 
     * @param int $id
     * @param string $email
     * @param int $smsLimit
     * @param bool|null $isAdmin
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function prepareUserUpdateData(
        int $id,
        string $email = '',
        int $smsLimit = 0,
        ?bool $isAdmin = null
    ): array {
        $data = [
            'email' => $email,
            'smsLimit' => $smsLimit
        ];

        if ($isAdmin !== null) {
            $data['isAdmin'] = $isAdmin;
        }

        return $this->validateUpdate($id, $data);
    }

    /**
     * Valide les données pour la suppression d'un utilisateur
     * 
     * @param int $id
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateDelete(int $id): array
    {
        $errors = $this->validateDeleteData($id);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la suppression de l'utilisateur échouée", $errors);
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

        // Validation du nom d'utilisateur
        if (empty($data['username'])) {
            $errors['username'] = "Le nom d'utilisateur est requis";
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = "Le nom d'utilisateur doit contenir au moins 3 caractères";
        } elseif (strlen($data['username']) > 50) {
            $errors['username'] = "Le nom d'utilisateur ne doit pas dépasser 50 caractères";
        } elseif ($this->userRepository->findByUsername($data['username'])) {
            $errors['username'] = "Ce nom d'utilisateur existe déjà";
        }

        // Validation du mot de passe
        if (empty($data['password'])) {
            $errors['password'] = "Le mot de passe est requis";
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = "Le mot de passe doit contenir au moins 8 caractères";
        } elseif (!preg_match('/[A-Z]/', $data['password'])) {
            $errors['password'] = "Le mot de passe doit contenir au moins une lettre majuscule";
        } elseif (!preg_match('/[0-9]/', $data['password'])) {
            $errors['password'] = "Le mot de passe doit contenir au moins un chiffre";
        }

        // Validation de l'email
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "L'adresse email n'est pas valide";
        }

        // Validation des crédits SMS
        if ($data['smsCredit'] < 0) {
            $errors['smsCredit'] = "Le crédit SMS ne peut pas être négatif";
        }

        // Validation de la limite SMS
        if ($data['smsLimit'] < 0) {
            $errors['smsLimit'] = "La limite SMS ne peut pas être négative";
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

        // Vérifier que l'utilisateur existe
        $user = $this->userRepository->findById($id);
        if (!$user) {
            $errors['id'] = "L'utilisateur n'existe pas";
            return $errors;
        }

        // Validation de l'email
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "L'adresse email n'est pas valide";
        }

        // Validation des crédits SMS
        if ($data['smsCredit'] < 0) {
            $errors['smsCredit'] = "Le crédit SMS ne peut pas être négatif";
        }

        // Validation de la limite SMS
        if ($data['smsLimit'] < 0) {
            $errors['smsLimit'] = "La limite SMS ne peut pas être négative";
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

        // Vérifier que l'utilisateur existe
        $user = $this->userRepository->findById($id);
        if (!$user) {
            $errors['id'] = "L'utilisateur n'existe pas";
        }

        // Vérifier que l'utilisateur n'est pas l'administrateur
        if ($user && $user->isAdmin()) {
            $errors['id'] = "L'administrateur ne peut pas être supprimé";
        }

        return $errors;
    }

    /**
     * Valide les données pour la mise à jour du mot de passe d'un utilisateur
     * 
     * @param int $id
     * @param string $currentPassword
     * @param string $newPassword
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validatePasswordUpdate(int $id, string $currentPassword, string $newPassword): array
    {
        $errors = [];

        // Vérifier que l'utilisateur existe
        $user = $this->userRepository->findById($id);
        if (!$user) {
            $errors['id'] = "L'utilisateur n'existe pas";
            throw new ValidationException("Validation de la mise à jour du mot de passe échouée", $errors);
        }

        // Vérifier que le mot de passe actuel est correct
        if (!password_verify($currentPassword, $user->getPassword())) {
            $errors['currentPassword'] = "Le mot de passe actuel est incorrect";
        }

        // Validation du nouveau mot de passe
        if (empty($newPassword)) {
            $errors['newPassword'] = "Le nouveau mot de passe est requis";
        } elseif (strlen($newPassword) < 8) {
            $errors['newPassword'] = "Le nouveau mot de passe doit contenir au moins 8 caractères";
        } elseif (!preg_match('/[A-Z]/', $newPassword)) {
            $errors['newPassword'] = "Le nouveau mot de passe doit contenir au moins une lettre majuscule";
        } elseif (!preg_match('/[0-9]/', $newPassword)) {
            $errors['newPassword'] = "Le nouveau mot de passe doit contenir au moins un chiffre";
        }

        if (!empty($errors)) {
            throw new ValidationException("Validation de la mise à jour du mot de passe échouée", $errors);
        }

        return [
            'id' => $id,
            'newPassword' => $newPassword
        ];
    }
}

<?php

namespace App\GraphQL\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Validators\UserValidator;
use App\Exceptions\ValidationException;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Right;

/**
 * Contrôleur GraphQL pour les utilisateurs
 */
class UserController
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var UserValidator
     */
    private $userValidator;

    /**
     * Constructeur
     * 
     * @param UserRepository $userRepository
     * @param UserValidator $userValidator
     */
    public function __construct(
        UserRepository $userRepository,
        UserValidator $userValidator
    ) {
        $this->userRepository = $userRepository;
        $this->userValidator = $userValidator;
    }

    /**
     * Récupérer un utilisateur par son ID
     * 
     * @Query
     * @param int $id
     * @return User
     */
    public function user(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Récupérer tous les utilisateurs
     * 
     * @Query
     * @return User[]
     */
    public function users(): array
    {
        return $this->userRepository->findAll();
    }

    /**
     * Créer un nouvel utilisateur
     * 
     * @Mutation
     * @param string $username
     * @param string $password
     * @param string $email
     * @param int $smsCredit
     * @param int $smsLimit
     * @return User
     */
    public function createUser(
        string $username,
        string $password,
        string $email = '',
        int $smsCredit = 0,
        int $smsLimit = 0
    ): User {
        try {
            // Valider les données
            $validatedData = $this->userValidator->validateCreate(
                $username,
                $password,
                $email,
                $smsCredit,
                $smsLimit
            );

            // Créer l'utilisateur
            $user = new User(
                $validatedData['username'],
                password_hash($validatedData['password'], PASSWORD_DEFAULT),
                $validatedData['email'],
                $validatedData['smsCredit'],
                $validatedData['smsLimit']
            );

            // Sauvegarder l'utilisateur
            return $this->userRepository->save($user);
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }
    }

    /**
     * Mettre à jour un utilisateur
     * 
     * @Mutation
     * @param int $id
     * @param string $email
     * @param int $smsCredit
     * @param int $smsLimit
     * @return User
     */
    public function updateUser(
        int $id,
        string $email = '',
        int $smsCredit = 0,
        int $smsLimit = 0
    ): User {
        try {
            // Valider les données
            $validatedData = $this->userValidator->validateUpdate(
                $id,
                $email,
                $smsCredit,
                $smsLimit
            );

            // Récupérer l'utilisateur
            $user = $this->userRepository->findById($id);
            if (!$user) {
                throw new \Exception("Utilisateur non trouvé");
            }

            // Mettre à jour l'utilisateur
            $user->setEmail($validatedData['email']);
            $user->setSmsCredit($validatedData['smsCredit']);
            $user->setSmsLimit($validatedData['smsLimit']);

            // Sauvegarder l'utilisateur
            return $this->userRepository->save($user);
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }
    }

    /**
     * Supprimer un utilisateur
     * 
     * @Mutation
     * @param int $id
     * @return bool
     */
    public function deleteUser(int $id): bool
    {
        try {
            // Valider les données
            $validatedData = $this->userValidator->validateDelete($id);

            // Supprimer l'utilisateur
            return $this->userRepository->delete($validatedData['id']);
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }
    }

    /**
     * Mettre à jour le mot de passe d'un utilisateur
     * 
     * @Mutation
     * @param int $id
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     */
    public function updateUserPassword(
        int $id,
        string $currentPassword,
        string $newPassword
    ): bool {
        try {
            // Valider les données
            $validatedData = $this->userValidator->validatePasswordUpdate(
                $id,
                $currentPassword,
                $newPassword
            );

            // Récupérer l'utilisateur
            $user = $this->userRepository->findById($validatedData['id']);
            if (!$user) {
                throw new \Exception("Utilisateur non trouvé");
            }

            // Mettre à jour le mot de passe
            $user->setPassword(password_hash($validatedData['newPassword'], PASSWORD_DEFAULT));

            // Sauvegarder l'utilisateur
            $this->userRepository->save($user);

            return true;
        } catch (ValidationException $e) {
            throw new \Exception($e->getMessage() . ': ' . json_encode($e->getErrors()));
        }
    }

    /**
     * Ajouter des crédits SMS à un utilisateur
     * 
     * @Mutation
     * @param int $id
     * @param int $credits
     * @return User
     */
    public function addSMSCredits(int $id, int $credits): User
    {
        // Récupérer l'utilisateur
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new \Exception("Utilisateur non trouvé");
        }

        // Valider les crédits
        if ($credits <= 0) {
            throw new \Exception("Le nombre de crédits doit être positif");
        }

        // Ajouter les crédits
        $user->setSmsCredit($user->getSmsCredit() + $credits);

        // Sauvegarder l'utilisateur
        return $this->userRepository->save($user);
    }
}

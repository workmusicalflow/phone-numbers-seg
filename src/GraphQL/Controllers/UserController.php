<?php

namespace App\GraphQL\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Validators\UserValidator;
use App\Exceptions\ValidationException;
use App\Services\Interfaces\AdminActionLoggerInterface;
use App\Services\Interfaces\AuthServiceInterface;
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
     * @var AdminActionLoggerInterface
     */
    private $adminActionLogger;

    /**
     * @var AuthServiceInterface
     */
    private $authService;

    /**
     * Constructeur
     * 
     * @param UserRepository $userRepository
     * @param UserValidator $userValidator
     * @param AdminActionLoggerInterface $adminActionLogger
     * @param AuthServiceInterface $authService
     */
    public function __construct(
        UserRepository $userRepository,
        UserValidator $userValidator,
        AdminActionLoggerInterface $adminActionLogger,
        AuthServiceInterface $authService
    ) {
        $this->userRepository = $userRepository;
        $this->userValidator = $userValidator;
        $this->adminActionLogger = $adminActionLogger;
        $this->authService = $authService;
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
     * @Right("ROLE_ADMIN")
     * @param string $username
     * @param string $password
     * @param string $email
     * @param int $smsCredit
     * @param int $smsLimit
     * @param bool $isAdmin
     * @return User
     */
    public function createUser(
        string $username,
        string $password,
        string $email = '',
        int $smsCredit = 0,
        int $smsLimit = 0,
        bool $isAdmin = false
    ): User {
        try {
            // Valider les données
            $validatedData = $this->userValidator->prepareUserCreateData(
                $username,
                $password,
                $email,
                $smsCredit,
                $smsLimit,
                $isAdmin
            );

            // Créer l'utilisateur
            $user = new User(
                $validatedData['username'],
                password_hash($validatedData['password'], PASSWORD_DEFAULT),
                null,
                $validatedData['email'] ?? null,
                $validatedData['smsCredit'],
                $validatedData['smsLimit'],
                $validatedData['isAdmin']
            );

            // Sauvegarder l'utilisateur
            $newUser = $this->userRepository->save($user);

            // Journaliser l'action
            $currentUser = $this->authService->getCurrentUser();
            if ($currentUser) {
                $this->adminActionLogger->log(
                    $currentUser->getId(),
                    'user_creation',
                    $newUser->getId(),
                    'user',
                    [
                        'username' => $validatedData['username'],
                        'email' => $validatedData['email'] ?? null,
                        'smsCredit' => $validatedData['smsCredit'],
                        'smsLimit' => $validatedData['smsLimit'],
                        'isAdmin' => $validatedData['isAdmin']
                    ]
                );
            }

            return $newUser;
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
     * @param int $smsLimit
     * @param bool $isAdmin
     * @return User
     */
    public function updateUser(
        int $id,
        string $email = '',
        int $smsLimit = 0,
        bool $isAdmin = null
    ): User {
        try {
            // Valider les données
            $validatedData = $this->userValidator->prepareUserUpdateData(
                $id,
                $email,
                $smsLimit,
                $isAdmin
            );

            // Récupérer l'utilisateur
            $user = $this->userRepository->findById($id);
            if (!$user) {
                throw new \Exception("Utilisateur non trouvé");
            }

            // Mettre à jour l'utilisateur
            $user->setEmail($validatedData['email']);
            $user->setSmsLimit($validatedData['smsLimit']);

            // Mettre à jour le statut admin si fourni
            if ($validatedData['isAdmin'] !== null) {
                $user->setIsAdmin($validatedData['isAdmin']);
            }

            // Sauvegarder l'utilisateur
            $updatedUser = $this->userRepository->save($user);

            // Journaliser l'action
            $currentUser = $this->authService->getCurrentUser();
            if ($currentUser) {
                $this->adminActionLogger->log(
                    $currentUser->getId(),
                    'user_update',
                    $updatedUser->getId(),
                    'user',
                    [
                        'email' => $validatedData['email'],
                        'smsLimit' => $validatedData['smsLimit'],
                        'isAdmin' => $validatedData['isAdmin']
                    ]
                );
            }

            return $updatedUser;
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

            // Récupérer l'utilisateur avant de le supprimer pour le journaliser
            $user = $this->userRepository->findById($validatedData['id']);
            if (!$user) {
                throw new \Exception("Utilisateur non trouvé");
            }

            // Supprimer l'utilisateur
            $result = $this->userRepository->delete($validatedData['id']);

            // Journaliser l'action
            if ($result) {
                $currentUser = $this->authService->getCurrentUser();
                if ($currentUser) {
                    $this->adminActionLogger->log(
                        $currentUser->getId(),
                        'user_deletion',
                        $validatedData['id'],
                        'user',
                        [
                            'username' => $user->getUsername()
                        ]
                    );
                }
            }

            return $result;
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

            // Journaliser l'action
            $currentUser = $this->authService->getCurrentUser();
            if ($currentUser) {
                $this->adminActionLogger->log(
                    $currentUser->getId(),
                    'password_change',
                    $validatedData['id'],
                    'user',
                    [
                        'username' => $user->getUsername()
                    ]
                );
            }

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
     * @param int $amount
     * @return User
     */
    public function addCredits(int $id, int $amount): User
    {
        // Récupérer l'utilisateur
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new \Exception("Utilisateur non trouvé");
        }

        // Valider les crédits
        if ($amount <= 0) {
            throw new \Exception("Le nombre de crédits doit être positif");
        }

        // Ajouter les crédits
        $user->setSmsCredit($user->getSmsCredit() + $amount);

        // Sauvegarder l'utilisateur
        $updatedUser = $this->userRepository->save($user);

        // Journaliser l'action
        $currentUser = $this->authService->getCurrentUser();
        if ($currentUser) {
            $this->adminActionLogger->log(
                $currentUser->getId(),
                'credit_added',
                $id,
                'user',
                [
                    'username' => $user->getUsername(),
                    'credits_added' => $amount,
                    'new_balance' => $user->getSmsCredit()
                ]
            );
        }

        return $updatedUser;
    }
}

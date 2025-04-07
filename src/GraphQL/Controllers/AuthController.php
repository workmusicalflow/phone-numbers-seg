<?php

namespace App\GraphQL\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Validators\UserValidator;
use App\Services\Interfaces\AuthServiceInterface;
use App\Exceptions\ValidationException;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Right;

/**
 * Contrôleur GraphQL pour l'authentification
 */
class AuthController
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
     * @var AuthServiceInterface
     */
    private $authService;

    /**
     * Constructeur
     * 
     * @param UserRepository $userRepository
     * @param UserValidator $userValidator
     * @param AuthServiceInterface $authService
     */
    public function __construct(
        UserRepository $userRepository,
        UserValidator $userValidator,
        AuthServiceInterface $authService
    ) {
        $this->userRepository = $userRepository;
        $this->userValidator = $userValidator;
        $this->authService = $authService;
    }

    /**
     * Authentifier un utilisateur
     * 
     * @Mutation
     * @param string $username
     * @param string $password
     * @return array
     */
    public function login(string $username, string $password): array
    {
        // Authentifier l'utilisateur
        $user = $this->authService->authenticate($username, $password);

        if (!$user) {
            throw new \Exception("Nom d'utilisateur ou mot de passe incorrect");
        }

        // Générer un token JWT (ou tout autre type de token)
        $token = bin2hex(random_bytes(32)); // Génération simple pour l'exemple

        // Retourner les informations de l'utilisateur
        return [
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'smsCredit' => $user->getSmsCredit(),
                'smsLimit' => $user->getSmsLimit(),
                'isAdmin' => $user->isAdmin(),
            ]
        ];
    }

    /**
     * Vérifier si l'utilisateur est authentifié
     * 
     * @Query
     * @return array
     */
    public function checkAuth(): array
    {
        // Vérifier si l'utilisateur est authentifié
        if (!$this->authService->isAuthenticated()) {
            return [
                'authenticated' => false
            ];
        }

        // Récupérer l'utilisateur
        $user = $this->authService->getCurrentUser();

        if (!$user) {
            return [
                'authenticated' => false
            ];
        }

        // Retourner les informations de l'utilisateur
        return [
            'authenticated' => true,
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'smsCredit' => $user->getSmsCredit(),
                'smsLimit' => $user->getSmsLimit(),
                'isAdmin' => $user->isAdmin(),
            ]
        ];
    }

    /**
     * Déconnecter l'utilisateur
     * 
     * @Mutation
     * @return array
     */
    public function logout(): array
    {
        // Détruire la session
        $this->authService->destroyUserSession();

        return [
            'success' => true
        ];
    }

    /**
     * Demander la réinitialisation du mot de passe
     * 
     * @Mutation
     * @param string $email
     * @return array
     */
    public function requestPasswordReset(string $email): array
    {
        // Générer un token de réinitialisation
        $token = $this->authService->generatePasswordResetToken($email);

        // Même si le token est null (utilisateur non trouvé), on retourne un succès
        // pour éviter de divulguer des informations sur les utilisateurs existants
        return [
            'success' => true
        ];
    }

    /**
     * Réinitialiser le mot de passe
     * 
     * @Mutation
     * @param string $token
     * @param string $newPassword
     * @return array
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        // Réinitialiser le mot de passe
        $success = $this->authService->resetPassword($token, $newPassword);

        if (!$success) {
            throw new \Exception("La réinitialisation du mot de passe a échoué");
        }

        return [
            'success' => true
        ];
    }
}

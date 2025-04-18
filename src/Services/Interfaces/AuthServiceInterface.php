<?php

namespace App\Services\Interfaces;

use App\Entities\User;

/**
 * Interface pour le service d'authentification
 */
interface AuthServiceInterface
{
    /**
     * Authentifier un utilisateur
     * 
     * @param string $username
     * @param string $password
     * @return User|null
     */
    public function authenticate(string $username, string $password): ?User;

    /**
     * Détruire la session utilisateur
     * 
     * @return void
     */
    public function destroyUserSession(): void;

    /**
     * Vérifier si l'utilisateur est authentifié
     * 
     * @return bool
     */
    public function isAuthenticated(): bool;

    /**
     * Obtenir l'utilisateur actuellement authentifié
     * 
     * @return User|null
     */
    public function getCurrentUser(): ?User;

    /**
     * Vérifier si un mot de passe est suffisamment complexe
     * 
     * @param string $password
     * @return bool
     */
    public function isPasswordComplex(string $password): bool;

    /**
     * Vérifier si un compte est verrouillé
     * 
     * @param string $username
     * @return bool
     */
    public function isAccountLocked(string $username): bool;

    /**
     * Incrémenter le compteur de tentatives échouées
     * 
     * @param string $username
     * @return void
     */
    public function incrementFailedLoginAttempts(string $username): void;

    /**
     * Réinitialiser le compteur de tentatives échouées
     * 
     * @param string $username
     * @return void
     */
    public function resetFailedLoginAttempts(string $username): void;

    /**
     * Générer un token pour la réinitialisation de mot de passe
     * 
     * @param string $email
     * @return string|null
     */
    public function generatePasswordResetToken(string $email): ?string;

    /**
     * Vérifier un token de réinitialisation de mot de passe
     * 
     * @param string $token
     * @return User|null
     */
    public function verifyPasswordResetToken(string $token): ?User;

    /**
     * Réinitialiser le mot de passe d'un utilisateur
     * 
     * @param string $token
     * @param string $newPassword
     * @return bool
     */
    public function resetPassword(string $token, string $newPassword): bool;
}

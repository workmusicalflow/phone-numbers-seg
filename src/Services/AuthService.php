<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Interfaces\AuthServiceInterface;
use App\Services\Interfaces\EmailServiceInterface;

/**
 * Service d'authentification
 */
class AuthService implements AuthServiceInterface
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var EmailServiceInterface
     */
    private $emailService;

    /**
     * @var array
     */
    private $failedLoginAttempts = [];

    /**
     * @var array
     */
    private $accountLockTime = [];

    /**
     * @var array
     */
    private $passwordResetTokens = [];

    /**
     * Nombre maximum de tentatives de connexion échouées avant verrouillage
     * 
     * @var int
     */
    private $maxFailedAttempts = 5;

    /**
     * Durée de verrouillage du compte en secondes (15 minutes)
     * 
     * @var int
     */
    private $lockDuration = 900;

    /**
     * Constructeur
     * 
     * @param UserRepository $userRepository
     * @param EmailServiceInterface $emailService
     */
    public function __construct(
        UserRepository $userRepository,
        EmailServiceInterface $emailService
    ) {
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(string $username, string $password): ?User
    {
        // Vérifier si le compte est verrouillé
        if ($this->isAccountLocked($username)) {
            return null;
        }

        // Rechercher l'utilisateur
        $user = $this->userRepository->findByUsername($username);

        // Si l'utilisateur n'existe pas ou le mot de passe est incorrect
        if (!$user || !$user->verifyPassword($password)) {
            $this->incrementFailedLoginAttempts($username);
            return null;
        }

        // Réinitialiser le compteur de tentatives échouées
        $this->resetFailedLoginAttempts($username);

        // Créer la session utilisateur
        $this->createUserSession($user);

        return $user;
    }

    /**
     * Créer une session pour l'utilisateur authentifié
     * 
     * @param User $user
     * @return void
     */
    private function createUserSession(User $user): void
    {
        // Démarrer la session si elle n'est pas déjà démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Stocker les informations de l'utilisateur en session
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['is_admin'] = $user->isAdmin();
        $_SESSION['auth_time'] = time();

        // Régénérer l'ID de session pour éviter les attaques de fixation de session
        session_regenerate_id(true);
    }

    /**
     * Détruire la session utilisateur
     * 
     * @return void
     */
    public function destroyUserSession(): void
    {
        // Démarrer la session si elle n'est pas déjà démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Détruire toutes les données de session
        $_SESSION = [];

        // Détruire le cookie de session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Détruire la session
        session_destroy();
    }

    /**
     * Vérifier si l'utilisateur est authentifié
     * 
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        // Démarrer la session si elle n'est pas déjà démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['user_id']);
    }

    /**
     * Obtenir l'utilisateur actuellement authentifié
     * 
     * @return User|null
     */
    public function getCurrentUser(): ?User
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return $this->userRepository->findById($_SESSION['user_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordComplex(string $password): bool
    {
        // Vérifier la longueur minimale (8 caractères)
        if (strlen($password) < 8) {
            return false;
        }

        // Vérifier la présence d'au moins une lettre majuscule
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Vérifier la présence d'au moins une lettre minuscule
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Vérifier la présence d'au moins un chiffre
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Vérifier la présence d'au moins un caractère spécial
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountLocked(string $username): bool
    {
        // Si le compte n'a pas de tentatives échouées, il n'est pas verrouillé
        if (!isset($this->failedLoginAttempts[$username])) {
            return false;
        }

        // Si le nombre de tentatives échouées est inférieur au maximum, le compte n'est pas verrouillé
        if ($this->failedLoginAttempts[$username] < $this->maxFailedAttempts) {
            return false;
        }

        // Si le compte est verrouillé mais que le temps de verrouillage est écoulé, déverrouiller le compte
        if (isset($this->accountLockTime[$username]) && time() - $this->accountLockTime[$username] > $this->lockDuration) {
            $this->resetFailedLoginAttempts($username);
            return false;
        }

        // Le compte est verrouillé
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementFailedLoginAttempts(string $username): void
    {
        // Initialiser le compteur si nécessaire
        if (!isset($this->failedLoginAttempts[$username])) {
            $this->failedLoginAttempts[$username] = 0;
        }

        // Incrémenter le compteur
        $this->failedLoginAttempts[$username]++;

        // Si le nombre de tentatives échouées atteint le maximum, verrouiller le compte
        if ($this->failedLoginAttempts[$username] >= $this->maxFailedAttempts) {
            $this->accountLockTime[$username] = time();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resetFailedLoginAttempts(string $username): void
    {
        // Réinitialiser le compteur et le temps de verrouillage
        $this->failedLoginAttempts[$username] = 0;
        unset($this->accountLockTime[$username]);
    }

    /**
     * {@inheritdoc}
     */
    public function generatePasswordResetToken(string $email): ?string
    {
        // Rechercher l'utilisateur par email
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            return null;
        }

        // Générer un token aléatoire
        $token = bin2hex(random_bytes(32));

        // Stocker le token avec l'ID de l'utilisateur et une date d'expiration (24 heures)
        $this->passwordResetTokens[$token] = [
            'userId' => $user->getId(),
            'expiration' => time() + 86400,
        ];

        // Envoyer un email avec le lien de réinitialisation
        $resetLink = $_ENV['APP_URL'] . '/reset-password?token=' . $token;
        $subject = 'Réinitialisation de votre mot de passe';
        $body = "Bonjour,\n\nVous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le lien suivant pour réinitialiser votre mot de passe :\n\n$resetLink\n\nCe lien est valable pendant 24 heures.\n\nSi vous n'avez pas demandé cette réinitialisation, ignorez cet email.\n\nCordialement,\nL'équipe Oracle SMS";

        $this->emailService->sendEmail($email, $subject, $body);

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function verifyPasswordResetToken(string $token): ?User
    {
        // Vérifier si le token existe
        if (!isset($this->passwordResetTokens[$token])) {
            return null;
        }

        // Vérifier si le token n'est pas expiré
        if ($this->passwordResetTokens[$token]['expiration'] < time()) {
            // Supprimer le token expiré
            unset($this->passwordResetTokens[$token]);
            return null;
        }

        // Récupérer l'utilisateur
        $userId = $this->passwordResetTokens[$token]['userId'];
        return $this->userRepository->findById($userId);
    }

    /**
     * {@inheritdoc}
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        // Vérifier le token et récupérer l'utilisateur
        $user = $this->verifyPasswordResetToken($token);

        if (!$user) {
            return false;
        }

        // Vérifier la complexité du mot de passe
        if (!$this->isPasswordComplex($newPassword)) {
            return false;
        }

        // Mettre à jour le mot de passe
        $user->setPassword(password_hash($newPassword, PASSWORD_DEFAULT));
        $this->userRepository->save($user);

        // Supprimer le token
        unset($this->passwordResetTokens[$token]);

        return true;
    }
}

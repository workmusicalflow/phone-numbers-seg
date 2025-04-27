<?php

namespace App\Services;

use App\Entities\User;
// Removed duplicate User import
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\AuthServiceInterface;
use App\Services\Interfaces\EmailServiceInterface;
use Psr\Log\LoggerInterface; // Import LoggerInterface

/**
 * Service d'authentification
 */
class AuthService implements AuthServiceInterface
{
    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var EmailServiceInterface
     */
    private $emailService;

    /**
     * @var LoggerInterface
     */
    private $logger; // Add logger property

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
     * @param UserRepositoryInterface $userRepository
     * @param EmailServiceInterface $emailService
     * @param LoggerInterface $logger // Inject Logger
     */
    public function __construct(
        UserRepositoryInterface $userRepository,
        EmailServiceInterface $emailService,
        LoggerInterface $logger // Add logger parameter
    ) {
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
        $this->logger = $logger; // Store logger instance
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(string $username, string $password): ?User
    {
        $this->logger->info("Tentative de connexion pour l'utilisateur: {$username}");
        // Vérifier si le compte est verrouillé
        if ($this->isAccountLocked($username)) {
            $this->logger->warning("Échec de la connexion: Compte verrouillé pour {$username}");
            return null;
        }

        // Rechercher l'utilisateur
        $user = $this->userRepository->findByUsername($username);

        // Si l'utilisateur n'existe pas ou le mot de passe est incorrect
        if (!$user || !$user->verifyPassword($password)) {
            $this->incrementFailedLoginAttempts($username);
            $this->logger->warning("Échec de la connexion: Identifiants invalides pour {$username}");
            return null;
        }

        // Réinitialiser le compteur de tentatives échouées
        $this->resetFailedLoginAttempts($username);

        // Créer la session utilisateur
        $this->createUserSession($user);
        $this->logger->info("Connexion réussie pour l'utilisateur: {$username} (ID: {$user->getId()})");

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
        // Démarrer la session si elle n'est pas déjà démarrée et si les en-têtes n'ont pas été envoyés
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $userId = $user->getId();
        $username = $user->getUsername();
        $isAdmin = $user->isAdmin();

        // Stocker les informations de l'utilisateur en session
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = $isAdmin;
        $_SESSION['auth_time'] = time();

        $this->logger->info("Session créée pour l'utilisateur: {$username} (ID: {$userId})");

        // Régénérer l'ID de session pour éviter les attaques de fixation de session
        // Seulement si la session est active
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
        }
    }

    /**
     * Détruire la session utilisateur
     * 
     * @return void
     */
    public function destroyUserSession(): void
    {
        // Démarrer la session si elle n'est pas déjà démarrée et si les en-têtes n'ont pas été envoyés
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // Si la session est active, la détruire
        if (session_status() === PHP_SESSION_ACTIVE) {
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
            $userId = $_SESSION['user_id'] ?? 'inconnu';
            $username = $_SESSION['username'] ?? 'inconnu';
            session_destroy();
            $this->logger->info("Session détruite pour l'utilisateur: {$username} (ID: {$userId})");
        }
    }

    /**
     * Vérifier si l'utilisateur est authentifié
     * 
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        // Démarrer la session si elle n'est pas déjà démarrée et si les en-têtes n'ont pas été envoyés
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
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
            $this->logger->info("Déverrouillage automatique du compte pour {$username} après expiration du délai.");
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
            $this->logger->warning("Compte verrouillé pour {$username} après {$this->maxFailedAttempts} tentatives échouées.");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resetFailedLoginAttempts(string $username): void
    {
        // Réinitialiser le compteur et le temps de verrouillage
        if (isset($this->failedLoginAttempts[$username]) && $this->failedLoginAttempts[$username] > 0) {
            $this->logger->info("Réinitialisation des tentatives de connexion échouées pour {$username}.");
        }
        $this->failedLoginAttempts[$username] = 0;
        unset($this->accountLockTime[$username]);
    }

    /**
     * {@inheritdoc}
     */
    public function generatePasswordResetToken(string $email): ?string
    {
        $this->logger->info("Demande de réinitialisation de mot de passe pour l'email: {$email}");
        // Rechercher l'utilisateur par email
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            $this->logger->warning("Demande de réinitialisation échouée: Aucun utilisateur trouvé pour l'email {$email}");
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

        try {
            $this->emailService->sendEmail($email, $subject, $body);
            $this->logger->info("Email de réinitialisation envoyé à {$email} pour l'utilisateur ID: {$user->getId()}");
        } catch (\Exception $e) {
            $this->logger->error("Échec de l'envoi de l'email de réinitialisation à {$email}. Erreur: " . $e->getMessage());
            // Ne pas retourner le token si l'email échoue ? Ou le retourner quand même ?
            // Pour l'instant, on le retourne mais on logue l'erreur.
        }


        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function verifyPasswordResetToken(string $token): ?User
    {
        $this->logger->info("Vérification du token de réinitialisation: " . substr($token, 0, 8) . "...");
        // Vérifier si le token existe
        if (!isset($this->passwordResetTokens[$token])) {
            $this->logger->warning("Échec de la vérification du token: Token invalide ou inexistant.");
            return null;
        }

        // Vérifier si le token n'est pas expiré
        if ($this->passwordResetTokens[$token]['expiration'] < time()) {
            $this->logger->warning("Échec de la vérification du token: Token expiré.");
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
        $this->logger->info("Tentative de réinitialisation de mot de passe avec token: " . substr($token, 0, 8) . "...");
        // Vérifier le token et récupérer l'utilisateur
        $user = $this->verifyPasswordResetToken($token);

        if (!$user) {
            $this->logger->error("Échec de la réinitialisation: Token invalide ou expiré.");
            return false;
        }

        // Vérifier la complexité du mot de passe
        if (!$this->isPasswordComplex($newPassword)) {
            $this->logger->error("Échec de la réinitialisation pour l'utilisateur ID {$user->getId()}: Nouveau mot de passe trop simple.");
            return false;
        }

        try {
            // Mettre à jour le mot de passe
            $user->setPassword(password_hash($newPassword, PASSWORD_DEFAULT));
            $this->userRepository->save($user);

            // Supprimer le token
            unset($this->passwordResetTokens[$token]);
            $this->logger->info("Mot de passe réinitialisé avec succès pour l'utilisateur ID {$user->getId()}.");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la sauvegarde du nouveau mot de passe pour l'utilisateur ID {$user->getId()}: " . $e->getMessage());
            return false;
        }
    }
}

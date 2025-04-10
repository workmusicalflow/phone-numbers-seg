<?php

namespace App\GraphQL\Resolvers;

use App\Services\Interfaces\AuthServiceInterface;
use App\Models\User;
// GraphQLFormatterInterface removed again
use Exception;
use Psr\Log\LoggerInterface;

class AuthResolver
{
    private AuthServiceInterface $authService;
    // Formatter property removed again
    private LoggerInterface $logger;

    public function __construct(
        AuthServiceInterface $authService,
        // Formatter injection removed again
        LoggerInterface $logger
    ) {
        $this->authService = $authService;
        $this->logger = $logger;
    }

    /**
     * Resolver for the 'login' mutation.
     *
     * @param array<string, mixed> $args Contains 'username', 'password'
     * @param mixed $context
     * @return bool True on success, false on failure.
     * @throws Exception For unexpected errors.
     */
    public function mutateLogin(array $args, $context): bool // Return type changed to bool for testing
    {
        $username = $args['username'] ?? '';
        $password = $args['password'] ?? '';
        $this->logger->info('Executing AuthResolver::mutateLogin for username: ' . $username);

        if (empty($username) || empty($password)) {
            $this->logger->warning('Login attempt with empty username or password.');
            throw new Exception("Nom d'utilisateur et mot de passe requis.");
        }

        try {
            // Authenticate user via AuthService
            $user = $this->authService->authenticate($username, $password);

            if (!$user) {
                $this->logger->warning('Failed login attempt for username: ' . $username);
                // Return false on authentication failure
                return false;
            }

            $this->logger->info('User authenticated successfully: ' . $username . ' (ID: ' . $user->getId() . ')');

            // Session is handled by AuthService::authenticate via createUserSession

            // Return true on success
            return true;
        } catch (Exception $e) {
            // Don't log password in case of error
            $this->logger->error('Error during login for username ' . $username . ': ' . $e->getMessage(), ['exception' => $e]);
            throw $e; // Re-throw to let GraphQL handle the error response
        }
    }

    /**
     * Resolver for the 'logout' mutation.
     *
     * @param array<string, mixed> $args
     * @param mixed $context
     * @return bool
     */
    public function mutateLogout(array $args, $context): bool
    {
        $this->logger->info('Executing AuthResolver::mutateLogout');
        try {
            // --- Session Handling ---
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $userId = $_SESSION['user_id'] ?? null;
            if ($userId) {
                $this->logger->info('Logging out user ID: ' . $userId);
            } else {
                $this->logger->info('Logout called but no user was logged in.');
            }

            // Unset all session variables
            $_SESSION = [];

            // Destroy the session cookie if it exists
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
                $this->logger->debug('Session cookie destroyed.');
            }

            // Destroy the session
            session_destroy();
            $this->logger->info('Session destroyed.');
            // --- End Session Handling ---

            return true;
        } catch (Exception $e) {
            $this->logger->error('Error during logout: ' . $e->getMessage(), ['exception' => $e]);
            return false; // Indicate failure on error
        }
    }

    // TODO: Implement resolvers for refreshToken, verifyToken, requestPasswordReset, resetPassword
    // These will require corresponding methods in the AuthService and potentially other services (e.g., for token generation/validation, email sending).

    /**
     * Resolver for the 'refreshToken' mutation (Placeholder).
     */
    public function mutateRefreshToken(array $args, $context): array
    {
        $this->logger->warning('mutateRefreshToken resolver not implemented.');
        throw new Exception("Fonctionnalité de rafraîchissement de token non implémentée.");
        // Implementation would involve validating the old token, potentially checking a refresh token store,
        // and issuing a new token if valid.
    }

    /**
     * Resolver for the 'verifyToken' query (Placeholder).
     */
    public function resolveVerifyToken(array $args, $context): array
    {
        $this->logger->warning('resolveVerifyToken resolver not implemented.');
        throw new Exception("Fonctionnalité de vérification de token non implémentée.");
        // Implementation would involve validating the token structure and signature,
        // checking expiration, and potentially querying user data.
        // Returning TokenVerificationResult structure.
    }

    /**
     * Resolver for the 'requestPasswordReset' mutation (Placeholder).
     */
    public function mutateRequestPasswordReset(array $args, $context): bool
    {
        $this->logger->warning('mutateRequestPasswordReset resolver not implemented.');
        throw new Exception("Fonctionnalité de demande de réinitialisation de mot de passe non implémentée.");
        // Implementation involves finding user by email, generating a reset token,
        // storing it (with expiration), and sending an email.
    }

    /**
     * Resolver for the 'resetPassword' mutation (Placeholder).
     */
    public function mutateResetPassword(array $args, $context): bool
    {
        $this->logger->warning('mutateResetPassword resolver not implemented.');
        throw new Exception("Fonctionnalité de réinitialisation de mot de passe non implémentée.");
        // Implementation involves validating the reset token, finding the user,
        // checking token expiration, hashing the new password, updating the user record,
        // and invalidating the reset token.
    }


    // --- Helper Methods ---
    // No helper methods needed here now
}

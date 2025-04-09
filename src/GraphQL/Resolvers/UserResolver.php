<?php

namespace App\GraphQL\Resolvers;

use App\Repositories\UserRepository;
use Exception;
use Psr\Log\LoggerInterface; // Assuming you might want logging

class UserResolver
{
    private UserRepository $userRepository;
    private LoggerInterface $logger;

    public function __construct(UserRepository $userRepository, LoggerInterface $logger)
    {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    /**
     * Resolver for the 'users' query.
     * Fetches all users.
     *
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    public function resolveUsers(): array
    {
        $this->logger->info('Executing UserResolver::resolveUsers');
        try {
            $users = $this->userRepository->findAll();
            $this->logger->info('Found ' . count($users) . ' users');

            // Convert User objects to arrays (temporary, will be improved in Phase 3)
            $result = [];
            foreach ($users as $user) {
                $result[] = $this->formatUser($user);
            }
            $this->logger->info('Formatted users for GraphQL response.');
            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error in UserResolver::resolveUsers: ' . $e->getMessage(), ['exception' => $e]);
            throw $e; // Re-throw the exception to be handled by GraphQL error handling
        }
    }

    /**
     * Resolver for the 'user' query.
     * Fetches a user by ID.
     *
     * @param array<string, mixed> $args
     * @return array<string, mixed>|null
     * @throws Exception
     */
    public function resolveUser(array $args): ?array
    {
        $userId = (int)($args['id'] ?? 0);
        $this->logger->info('Executing UserResolver::resolveUser for ID: ' . $userId);

        if ($userId <= 0) {
            $this->logger->warning('Invalid user ID provided for resolveUser.', ['args' => $args]);
            return null; // Or throw an argument exception
        }

        try {
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                $this->logger->info('User not found for ID: ' . $userId);
                return null;
            }

            $this->logger->info('User found for ID: ' . $userId);
            return $this->formatUser($user);
        } catch (Exception $e) {
            $this->logger->error('Error in UserResolver::resolveUser: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'userByUsername' query.
     * Fetches a user by username.
     *
     * @param array<string, mixed> $args
     * @return array<string, mixed>|null
     * @throws Exception
     */
    public function resolveUserByUsername(array $args): ?array
    {
        $username = $args['username'] ?? '';
        $this->logger->info('Executing UserResolver::resolveUserByUsername for username: ' . $username);

        if (empty($username)) {
            $this->logger->warning('Empty username provided for resolveUserByUsername.', ['args' => $args]);
            return null; // Or throw an argument exception
        }

        try {
            $user = $this->userRepository->findByUsername($username);
            if (!$user) {
                $this->logger->info('User not found for username: ' . $username);
                return null;
            }

            $this->logger->info('User found for username: ' . $username);
            return $this->formatUser($user);
        } catch (Exception $e) {
            $this->logger->error('Error in UserResolver::resolveUserByUsername: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'createUser' mutation.
     *
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     * @throws Exception
     */
    public function mutateCreateUser(array $args): array
    {
        $username = $args['username'] ?? '';
        $password = $args['password'] ?? '';
        $this->logger->info('Executing UserResolver::mutateCreateUser for username: ' . $username);

        if (empty($username) || empty($password)) {
            $this->logger->error('Username or password missing for createUser mutation.', ['args' => $args]);
            throw new Exception("Nom d'utilisateur et mot de passe requis.");
        }

        try {
            // Check if user already exists
            $existingUser = $this->userRepository->findByUsername($username);
            if ($existingUser) {
                $this->logger->warning('Attempted to create user with existing username: ' . $username);
                throw new Exception("Un utilisateur avec ce nom d'utilisateur existe déjà");
            }

            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            if ($hashedPassword === false) {
                $this->logger->error('Password hashing failed for user: ' . $username);
                throw new Exception("Erreur lors du hachage du mot de passe.");
            }

            // Create the user model
            $smsCredit = isset($args['smsCredit']) ? (int)$args['smsCredit'] : 10; // Default credits
            $smsLimit = isset($args['smsLimit']) ? (int)$args['smsLimit'] : null;
            $isAdmin = isset($args['isAdmin']) ? (bool)$args['isAdmin'] : false;
            $email = $args['email'] ?? null;

            // Note: The User model constructor might need adjustment if it expects ID=0 or null
            $user = new \App\Models\User(
                0, // Assuming ID is auto-generated
                $username,
                $hashedPassword,
                $email,
                $smsCredit,
                $smsLimit,
                $isAdmin
                // createdAt and updatedAt are likely handled by the model/repository
            );

            // Save the user
            $savedUser = $this->userRepository->save($user); // Assuming save handles create/update
            $this->logger->info('User created successfully with ID: ' . $savedUser->getId());

            return $this->formatUser($savedUser);
        } catch (Exception $e) {
            $this->logger->error('Error in UserResolver::mutateCreateUser: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'updateUser' mutation.
     *
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     * @throws Exception
     */
    public function mutateUpdateUser(array $args): array
    {
        $userId = (int)($args['id'] ?? 0);
        $this->logger->info('Executing UserResolver::mutateUpdateUser for ID: ' . $userId);

        if ($userId <= 0) {
            $this->logger->error('Invalid user ID provided for updateUser mutation.', ['args' => $args]);
            throw new Exception("ID utilisateur invalide.");
        }

        try {
            // Fetch the user
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                $this->logger->error('User not found for update mutation with ID: ' . $userId);
                throw new Exception("Utilisateur non trouvé");
            }

            // Update fields if provided
            if (array_key_exists('email', $args)) {
                $user->setEmail($args['email']);
                $this->logger->debug('Updating email for user ID: ' . $userId);
            }
            if (array_key_exists('smsLimit', $args)) {
                $user->setSmsLimit(isset($args['smsLimit']) ? (int)$args['smsLimit'] : null);
                $this->logger->debug('Updating smsLimit for user ID: ' . $userId);
            }
            if (array_key_exists('isAdmin', $args)) {
                $user->setIsAdmin((bool)$args['isAdmin']);
                $this->logger->debug('Updating isAdmin status for user ID: ' . $userId);
            }
            // Note: Username and password changes are typically handled by separate mutations

            // Save the updated user
            $updatedUser = $this->userRepository->save($user);
            $this->logger->info('User updated successfully for ID: ' . $userId);

            return $this->formatUser($updatedUser);
        } catch (Exception $e) {
            $this->logger->error('Error in UserResolver::mutateUpdateUser: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'changePassword' mutation.
     *
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     * @throws Exception
     */
    public function mutateChangePassword(array $args): array
    {
        $userId = (int)($args['id'] ?? 0);
        $newPassword = $args['newPassword'] ?? '';
        $this->logger->info('Executing UserResolver::mutateChangePassword for ID: ' . $userId);

        if ($userId <= 0 || empty($newPassword)) {
            $this->logger->error('Invalid user ID or empty password for changePassword mutation.', ['args' => $args]);
            throw new Exception("ID utilisateur ou nouveau mot de passe invalide.");
        }

        try {
            // Fetch the user
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                $this->logger->error('User not found for changePassword mutation with ID: ' . $userId);
                throw new Exception("Utilisateur non trouvé");
            }

            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            if ($hashedPassword === false) {
                $this->logger->error('Password hashing failed during changePassword for user ID: ' . $userId);
                throw new Exception("Erreur lors du hachage du nouveau mot de passe.");
            }
            $user->setPassword($hashedPassword);

            // Save the user
            $updatedUser = $this->userRepository->save($user);
            $this->logger->info('Password changed successfully for user ID: ' . $userId);

            return $this->formatUser($updatedUser);
        } catch (Exception $e) {
            $this->logger->error('Error in UserResolver::mutateChangePassword: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'addCredits' mutation.
     *
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     * @throws Exception
     */
    public function mutateAddCredits(array $args): array
    {
        $userId = (int)($args['id'] ?? 0);
        $amount = (int)($args['amount'] ?? 0);
        $this->logger->info('Executing UserResolver::mutateAddCredits for ID: ' . $userId . ', amount: ' . $amount);

        if ($userId <= 0 || $amount <= 0) {
            $this->logger->error('Invalid user ID or amount for addCredits mutation.', ['args' => $args]);
            throw new Exception("ID utilisateur ou montant invalide.");
        }

        try {
            // Fetch the user
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                $this->logger->error('User not found for addCredits mutation with ID: ' . $userId);
                throw new Exception("Utilisateur non trouvé");
            }

            // Add credits
            $currentCredits = $user->getSmsCredit();
            $user->setSmsCredit($currentCredits + $amount);

            // Save the user
            $updatedUser = $this->userRepository->save($user);
            $this->logger->info('Credits added successfully for user ID: ' . $userId . '. New balance: ' . $updatedUser->getSmsCredit());

            return $this->formatUser($updatedUser);
        } catch (Exception $e) {
            $this->logger->error('Error in UserResolver::mutateAddCredits: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resolver for the 'deleteUser' mutation.
     *
     * @param array<string, mixed> $args
     * @return bool
     * @throws Exception
     */
    public function mutateDeleteUser(array $args): bool
    {
        $userId = (int)($args['id'] ?? 0);
        $this->logger->info('Executing UserResolver::mutateDeleteUser for ID: ' . $userId);

        if ($userId <= 0) {
            $this->logger->error('Invalid user ID provided for deleteUser mutation.', ['args' => $args]);
            throw new Exception("ID utilisateur invalide.");
        }

        try {
            // Check if user exists before attempting delete
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                $this->logger->warning('Attempted to delete non-existent user with ID: ' . $userId);
                // Depending on desired behavior, could return false or throw not found exception
                return false;
                // throw new Exception("Utilisateur non trouvé");
            }

            $deleted = $this->userRepository->delete($userId);
            if ($deleted) {
                $this->logger->info('User deleted successfully with ID: ' . $userId);
            } else {
                $this->logger->error('Failed to delete user with ID: ' . $userId);
            }
            return $deleted;
        } catch (Exception $e) {
            // Log repository exceptions specifically if possible
            $this->logger->error('Error in UserResolver::mutateDeleteUser: ' . $e->getMessage(), ['exception' => $e]);
            throw $e; // Or return false depending on how you want to handle errors
        }
    }

    // --- Helper Methods ---

    /**
     * Formats a User object into an array suitable for GraphQL response.
     * This will be improved in Phase 3 (Centralized Conversion).
     *
     * @param \App\Models\User $user
     * @return array<string, mixed>
     */
    private function formatUser(\App\Models\User $user): array
    {
        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'smsCredit' => $user->getSmsCredit(),
            'smsLimit' => $user->getSmsLimit(),
            'isAdmin' => $user->isAdmin(),
            'createdAt' => $user->getCreatedAt(), // Ensure format is correct (e.g., ISO 8601)
            'updatedAt' => $user->getUpdatedAt(), // Ensure format is correct
        ];
    }
}

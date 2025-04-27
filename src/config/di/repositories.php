<?php

use DI\Container;
use function DI\factory;

/**
 * Repository definitions for Dependency Injection Container
 */
return [
    // Legacy Repositories (PDO-based) - Keep for transition or remove if fully migrated
    \App\Repositories\PhoneNumberRepository::class => factory(function (Container $container) {
        return new \App\Repositories\PhoneNumberRepository($container->get(PDO::class));
    }),
    \App\Repositories\SegmentRepository::class => factory(function (Container $container) {
        return new \App\Repositories\SegmentRepository($container->get(PDO::class));
    }),
    \App\Repositories\CustomSegmentRepository::class => factory(function (Container $container) {
        return new \App\Repositories\CustomSegmentRepository($container->get(PDO::class));
    }),
    \App\Repositories\UserRepository::class => factory(function (Container $container) {
        return new \App\Repositories\UserRepository($container->get(PDO::class));
    }),
    \App\Repositories\SenderNameRepository::class => factory(function (Container $container) {
        return new \App\Repositories\SenderNameRepository($container->get(PDO::class));
    }),
    \App\Repositories\SMSOrderRepository::class => factory(function (Container $container) {
        return new \App\Repositories\SMSOrderRepository($container->get(PDO::class));
    }),
    \App\Repositories\OrangeAPIConfigRepository::class => factory(function (Container $container) {
        return new \App\Repositories\OrangeAPIConfigRepository($container->get(PDO::class));
    }),
    \App\Repositories\TechnicalSegmentRepository::class => factory(function (Container $container) {
        return new \App\Repositories\TechnicalSegmentRepository($container->get(PDO::class));
    }),
    \App\Repositories\ContactRepository::class => factory(function (Container $container) {
        return new \App\Repositories\ContactRepository($container->get(PDO::class));
    }),
    \App\Repositories\ContactGroupRepository::class => factory(function (Container $container) {
        return new \App\Repositories\ContactGroupRepository($container->get(PDO::class));
    }),
    \App\Repositories\ContactGroupMembershipRepository::class => factory(function (Container $container) {
        return new \App\Repositories\ContactGroupMembershipRepository($container->get(PDO::class));
    }),
    \App\Repositories\SMSHistoryRepository::class => factory(function (Container $container) {
        return new \App\Repositories\SMSHistoryRepository($container->get(PDO::class));
    }),
    // Note: Legacy AdminContactRepository removed as it's fully migrated

    // Doctrine Repositories (Concrete Implementations)
    App\Repositories\Doctrine\SenderNameRepository::class => factory(function (Container $container) {
        return new App\Repositories\Doctrine\SenderNameRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
        );
    }),
    App\Repositories\Doctrine\SegmentRepository::class => factory(function (Container $container) {
        return new App\Repositories\Doctrine\SegmentRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
        );
    }),
    App\Repositories\Doctrine\CustomSegmentRepository::class => factory(function (Container $container) {
        return new App\Repositories\Doctrine\CustomSegmentRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
        );
    }),
    App\Repositories\Doctrine\PhoneNumberSegmentRepository::class => factory(function (Container $container) {
        return new App\Repositories\Doctrine\PhoneNumberSegmentRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
        );
    }),
    App\Repositories\Doctrine\OrangeAPIConfigRepository::class => factory(function (Container $container) {
        return new App\Repositories\Doctrine\OrangeAPIConfigRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
        );
    }),
    App\Repositories\Doctrine\PhoneNumberRepository::class => factory(function (Container $container) {
        return new App\Repositories\Doctrine\PhoneNumberRepository(
            $container->get(\Doctrine\ORM\EntityManager::class),
            $container->get(App\Repositories\Interfaces\SegmentRepositoryInterface::class),
            $container->get(App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class)
        );
    }),
    App\Repositories\Doctrine\TechnicalSegmentRepository::class => factory(function (Container $container) {
        return new App\Repositories\Doctrine\TechnicalSegmentRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
        );
    }),
    App\Repositories\Doctrine\UserRepository::class => factory(function (Container $container) {
        // Note: Using direct instantiation for repositories with custom dependencies
        // or extending BaseRepository to ensure correct injection.
        return new App\Repositories\Doctrine\UserRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
            // Add other dependencies if UserRepository constructor requires them
        );
    }),
    App\Repositories\Doctrine\ContactRepository::class => factory(function (Container $container) {
        // Explicitly instantiate to ensure correct dependencies if BaseRepository is used or constructor changes
        return new App\Repositories\Doctrine\ContactRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
            // Add other dependencies if ContactRepository constructor requires them
        );
    }),
    App\Repositories\Doctrine\ContactGroupRepository::class => factory(function (Container $container) {
        // Explicitly instantiate to inject all required dependencies
        return new App\Repositories\Doctrine\ContactGroupRepository(
            $container->get(\Doctrine\ORM\EntityManager::class),
            $container->get(App\Repositories\Interfaces\ContactRepositoryInterface::class),
            $container->get(App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface::class)
        );
    }),
    App\Repositories\Doctrine\ContactGroupMembershipRepository::class => factory(function (Container $container) {
        // Explicitly instantiate to ensure correct dependencies if BaseRepository is used or constructor changes
        return new App\Repositories\Doctrine\ContactGroupMembershipRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
            // Add other dependencies if ContactGroupMembershipRepository constructor requires them
        );
    }),
    App\Repositories\Doctrine\SMSHistoryRepository::class => factory(function (Container $container) {
        // Explicitly instantiate to ensure correct dependencies if BaseRepository is used or constructor changes
        return new App\Repositories\Doctrine\SMSHistoryRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
            // Add other dependencies if SMSHistoryRepository constructor requires them
        );
    }),
    App\Repositories\Doctrine\SMSOrderRepository::class => factory(function (Container $container) {
        // Explicitly instantiate to ensure correct dependencies if BaseRepository is used or constructor changes
        return new App\Repositories\Doctrine\SMSOrderRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
            // Add other dependencies if SMSOrderRepository constructor requires them
        );
    }),
    App\Repositories\Doctrine\AdminActionLogRepository::class => factory(function (Container $container) {
        // Explicitly instantiate to ensure correct dependencies if BaseRepository is used or constructor changes
        return new App\Repositories\Doctrine\AdminActionLogRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
            // Add other dependencies if AdminActionLogRepository constructor requires them
        );
    }),
    App\Repositories\Doctrine\AdminContactRepository::class => factory(function (Container $container) {
        // Explicitly instantiate to ensure correct dependencies if BaseRepository is used or constructor changes
        return new App\Repositories\Doctrine\AdminContactRepository(
            $container->get(\Doctrine\ORM\EntityManager::class)
            // Add other dependencies if AdminContactRepository constructor requires them
        );
    }),

    // Repository Interface to Doctrine Implementation Mapping
    App\Repositories\Interfaces\SegmentRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\SegmentRepository::class);
    }),
    App\Repositories\Interfaces\CustomSegmentRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\CustomSegmentRepository::class);
    }),
    App\Repositories\Interfaces\PhoneNumberSegmentRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\PhoneNumberSegmentRepository::class);
    }),
    App\Repositories\Interfaces\OrangeAPIConfigRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\OrangeAPIConfigRepository::class);
    }),
    App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\PhoneNumberRepository::class);
    }),
    App\Repositories\Interfaces\TechnicalSegmentRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\TechnicalSegmentRepository::class);
    }),
    App\Repositories\Interfaces\UserRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\UserRepository::class);
    }),
    App\Repositories\Interfaces\ContactRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\ContactRepository::class);
    }),
    App\Repositories\Interfaces\ContactGroupRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\ContactGroupRepository::class);
    }),
    App\Repositories\Interfaces\ContactGroupMembershipRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\ContactGroupMembershipRepository::class);
    }),
    App\Repositories\Interfaces\SMSHistoryRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\SMSHistoryRepository::class);
    }),
    App\Repositories\Interfaces\SMSOrderRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\SMSOrderRepository::class);
    }),
    App\Repositories\Interfaces\AdminActionLogRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\AdminActionLogRepository::class);
    }),
    App\Repositories\Interfaces\AdminContactRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\AdminContactRepository::class);
    }),
    // Add mapping for SenderNameRepositoryInterface
    App\Repositories\Interfaces\SenderNameRepositoryInterface::class => factory(function (Container $container) {
        return $container->get(App\Repositories\Doctrine\SenderNameRepository::class);
    }),
];

<?php

use DI\Container;
use function DI\factory;

/**
 * Validator definitions for Dependency Injection Container
 */
return [
    \App\Services\Interfaces\PhoneNumberValidatorInterface::class => factory(function () {
        return new \App\Services\PhoneNumberValidator();
    }),

    \App\Services\Interfaces\RegexValidatorInterface::class => factory(function () {
        return new \App\Services\RegexValidator();
    }),

    \App\Services\Interfaces\SMSValidationServiceInterface::class => factory(function () {
        return new \App\Services\SMSValidationService();
    }),

    // Add other validator interfaces or concrete classes if needed
    // e.g., \App\Services\Validators\AdminContactValidator::class => factory(...) if autowiring is not sufficient
];

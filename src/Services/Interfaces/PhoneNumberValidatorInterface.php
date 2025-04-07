<?php

namespace App\Services\Interfaces;

use App\Services\Interfaces\ValidatorInterface;

/**
 * Interface pour les validateurs de numéros de téléphone
 */
interface PhoneNumberValidatorInterface extends ValidatorInterface
{
    /**
     * Valide un numéro de téléphone
     * 
     * @param string $number
     * @return bool
     */
    public function isValid(string $number): bool;

    /**
     * Valide un lot de numéros de téléphone
     * 
     * @param array $numbers
     * @return array Tableau associatif avec les numéros valides et invalides
     */
    public function validateBatch(array $numbers): array;

    /**
     * Valide un numéro de téléphone pour l'envoi de SMS
     * 
     * @param string $number
     * @return bool
     */
    public function isValidForSMS(string $number): bool;
}

<?php

namespace App\Services\Validators;

use App\Repositories\PhoneNumberRepository;
use App\Exceptions\ValidationException;
use App\Services\Interfaces\PhoneNumberValidatorInterface;

/**
 * Validateur pour les numéros de téléphone
 * 
 * Cette classe est responsable de la validation des numéros de téléphone
 * et de leurs métadonnées associées.
 */
class PhoneNumberValidator extends AbstractValidator implements PhoneNumberValidatorInterface
{
    /**
     * @var PhoneNumberRepository
     */
    private $phoneNumberRepository;

    /**
     * Constructeur
     * 
     * @param PhoneNumberRepository $phoneNumberRepository
     */
    public function __construct(PhoneNumberRepository $phoneNumberRepository)
    {
        $this->phoneNumberRepository = $phoneNumberRepository;
    }

    /**
     * Valide les données pour la création d'un numéro de téléphone
     * 
     * @param array $data Données à valider
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateCreate(array $data): array
    {
        $errors = $this->validateCreateData($data);

        if (!empty($errors)) {
            throw new ValidationException("Validation du numéro de téléphone échouée", $errors);
        }

        return $data;
    }

    /**
     * Valide les données pour la mise à jour d'un numéro de téléphone
     * 
     * @param int $id ID du numéro à mettre à jour
     * @param array $data Données à valider
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateUpdate(int $id, array $data): array
    {
        $errors = $this->validateUpdateData($id, $data);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la mise à jour du numéro de téléphone échouée", $errors);
        }

        return array_merge(['id' => $id], $data);
    }

    /**
     * Valide les données pour la suppression d'un numéro de téléphone
     * 
     * @param int $id ID du numéro à supprimer
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateDelete(int $id): array
    {
        $errors = $this->validateDeleteData($id);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la suppression du numéro de téléphone échouée", $errors);
        }

        return ['id' => $id];
    }

    /**
     * Implémentation de la méthode abstraite pour valider les données de création
     * 
     * @param array $data
     * @return array Tableau d'erreurs (vide si aucune erreur)
     */
    protected function validateCreateData(array $data): array
    {
        $errors = [];

        // Validation du numéro de téléphone
        if (empty($data['number'])) {
            $errors['number'] = "Le numéro de téléphone est requis";
        } elseif (strlen($data['number']) < 8) {
            $errors['number'] = "Le numéro de téléphone doit contenir au moins 8 caractères";
        } elseif (strlen($data['number']) > 20) {
            $errors['number'] = "Le numéro de téléphone ne doit pas dépasser 20 caractères";
        } elseif ($this->phoneNumberRepository->findByNumber($data['number'])) {
            $errors['number'] = "Ce numéro de téléphone existe déjà";
        }

        // Validation de la civilité (optionnel)
        if (isset($data['civility']) && strlen($data['civility']) > 10) {
            $errors['civility'] = "La civilité ne doit pas dépasser 10 caractères";
        }

        // Validation du prénom (optionnel)
        if (isset($data['firstName']) && strlen($data['firstName']) > 100) {
            $errors['firstName'] = "Le prénom ne doit pas dépasser 100 caractères";
        }

        // Validation du nom (optionnel)
        if (isset($data['name']) && strlen($data['name']) > 100) {
            $errors['name'] = "Le nom ne doit pas dépasser 100 caractères";
        }

        // Validation de l'entreprise (optionnel)
        if (isset($data['company']) && strlen($data['company']) > 100) {
            $errors['company'] = "L'entreprise ne doit pas dépasser 100 caractères";
        }

        // Validation du secteur (optionnel)
        if (isset($data['sector']) && strlen($data['sector']) > 100) {
            $errors['sector'] = "Le secteur ne doit pas dépasser 100 caractères";
        }

        // Validation des notes (optionnel)
        if (isset($data['notes']) && strlen($data['notes']) > 1000) {
            $errors['notes'] = "Les notes ne doivent pas dépasser 1000 caractères";
        }

        return $errors;
    }

    /**
     * Implémentation de la méthode abstraite pour valider les données de mise à jour
     * 
     * @param int $id
     * @param array $data
     * @return array Tableau d'erreurs (vide si aucune erreur)
     */
    protected function validateUpdateData(int $id, array $data): array
    {
        $errors = [];

        // Vérifier que le numéro existe
        $phoneNumber = $this->phoneNumberRepository->findById($id);
        if (!$phoneNumber) {
            $errors['id'] = "Le numéro de téléphone n'existe pas";
            return $errors;
        }

        // Validation du numéro de téléphone (si présent)
        if (isset($data['number'])) {
            if (strlen($data['number']) < 8) {
                $errors['number'] = "Le numéro de téléphone doit contenir au moins 8 caractères";
            } elseif (strlen($data['number']) > 20) {
                $errors['number'] = "Le numéro de téléphone ne doit pas dépasser 20 caractères";
            } else {
                // Vérifier que le numéro n'est pas déjà utilisé par un autre contact
                $existingPhoneNumber = $this->phoneNumberRepository->findByNumber($data['number']);
                if ($existingPhoneNumber && $existingPhoneNumber->getId() !== $id) {
                    $errors['number'] = "Ce numéro de téléphone existe déjà";
                }
            }
        }

        // Validation de la civilité (si présente)
        if (isset($data['civility']) && strlen($data['civility']) > 10) {
            $errors['civility'] = "La civilité ne doit pas dépasser 10 caractères";
        }

        // Validation du prénom (si présent)
        if (isset($data['firstName']) && strlen($data['firstName']) > 100) {
            $errors['firstName'] = "Le prénom ne doit pas dépasser 100 caractères";
        }

        // Validation du nom (si présent)
        if (isset($data['name']) && strlen($data['name']) > 100) {
            $errors['name'] = "Le nom ne doit pas dépasser 100 caractères";
        }

        // Validation de l'entreprise (si présente)
        if (isset($data['company']) && strlen($data['company']) > 100) {
            $errors['company'] = "L'entreprise ne doit pas dépasser 100 caractères";
        }

        // Validation du secteur (si présent)
        if (isset($data['sector']) && strlen($data['sector']) > 100) {
            $errors['sector'] = "Le secteur ne doit pas dépasser 100 caractères";
        }

        // Validation des notes (si présentes)
        if (isset($data['notes']) && strlen($data['notes']) > 1000) {
            $errors['notes'] = "Les notes ne doivent pas dépasser 1000 caractères";
        }

        return $errors;
    }

    /**
     * Implémentation de la méthode abstraite pour valider les données de suppression
     * 
     * @param int $id
     * @return array Tableau d'erreurs (vide si aucune erreur)
     */
    protected function validateDeleteData(int $id): array
    {
        $errors = [];

        // Vérifier que le numéro existe
        $phoneNumber = $this->phoneNumberRepository->findById($id);
        if (!$phoneNumber) {
            $errors['id'] = "Le numéro de téléphone n'existe pas";
        }

        return $errors;
    }

    /**
     * Valide un numéro de téléphone
     * 
     * @param string $number
     * @return bool
     */
    public function isValid(string $number): bool
    {
        // Vérifier que le numéro n'est pas vide
        if (empty($number)) {
            return false;
        }

        // Vérifier la longueur du numéro
        if (strlen($number) < 8 || strlen($number) > 20) {
            return false;
        }

        // Vérifier que le numéro ne contient que des chiffres, +, - et espaces
        if (!preg_match('/^[0-9+\- ]+$/', $number)) {
            return false;
        }

        return true;
    }

    /**
     * Valide un lot de numéros de téléphone
     * 
     * @param array $numbers
     * @return array Tableau associatif avec les numéros valides et invalides
     */
    public function validateBatch(array $numbers): array
    {
        $result = [
            'valid' => [],
            'invalid' => []
        ];

        foreach ($numbers as $index => $number) {
            if ($this->isValid($number)) {
                $result['valid'][] = $number;
            } else {
                $result['invalid'][] = [
                    'index' => $index,
                    'number' => $number,
                    'reason' => 'Format invalide'
                ];
            }
        }

        return $result;
    }

    /**
     * Valide un numéro de téléphone pour l'envoi de SMS
     * 
     * @param string $number
     * @return bool
     */
    public function isValidForSMS(string $number): bool
    {
        // Vérifier que le numéro est valide
        if (!$this->isValid($number)) {
            return false;
        }

        // Vérifier que le numéro commence par un code pays
        if (!preg_match('/^\+[0-9]{1,3}/', $number)) {
            return false;
        }

        return true;
    }
}

<?php

namespace App\Services\Validators;

use App\Repositories\SMSHistoryRepository;
use App\Repositories\PhoneNumberRepository;
use App\Exceptions\ValidationException;

/**
 * Validateur pour l'historique des SMS
 * 
 * Cette classe est responsable de la validation des enregistrements d'historique SMS
 * et de leurs métadonnées associées.
 */
class SMSHistoryValidator extends AbstractValidator
{
    /**
     * @var SMSHistoryRepository
     */
    private $smsHistoryRepository;

    /**
     * @var PhoneNumberRepository
     */
    private $phoneNumberRepository;

    /**
     * Constructeur
     * 
     * @param SMSHistoryRepository $smsHistoryRepository
     * @param PhoneNumberRepository $phoneNumberRepository
     */
    public function __construct(
        SMSHistoryRepository $smsHistoryRepository,
        PhoneNumberRepository $phoneNumberRepository
    ) {
        $this->smsHistoryRepository = $smsHistoryRepository;
        $this->phoneNumberRepository = $phoneNumberRepository;
    }

    /**
     * Valide les données pour la création d'un enregistrement d'historique SMS
     * 
     * @param array $data Données à valider
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateCreate(array $data): array
    {
        $errors = $this->validateCreateData($data);

        if (!empty($errors)) {
            throw new ValidationException("Validation de l'enregistrement d'historique SMS échouée", $errors);
        }

        return $data;
    }

    /**
     * Valide les données pour la mise à jour d'un enregistrement d'historique SMS
     * 
     * @param int $id ID de l'enregistrement à mettre à jour
     * @param array $data Données à valider
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateUpdate(int $id, array $data): array
    {
        $errors = $this->validateUpdateData($id, $data);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la mise à jour de l'enregistrement d'historique SMS échouée", $errors);
        }

        return array_merge(['id' => $id], $data);
    }

    /**
     * Valide les données pour la suppression d'un enregistrement d'historique SMS
     * 
     * @param int $id ID de l'enregistrement à supprimer
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateDelete(int $id): array
    {
        $errors = $this->validateDeleteData($id);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la suppression de l'enregistrement d'historique SMS échouée", $errors);
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
        if (empty($data['phoneNumber'])) {
            $errors['phoneNumber'] = "Le numéro de téléphone est requis";
        } elseif (strlen($data['phoneNumber']) < 8) {
            $errors['phoneNumber'] = "Le numéro de téléphone doit contenir au moins 8 caractères";
        } elseif (strlen($data['phoneNumber']) > 20) {
            $errors['phoneNumber'] = "Le numéro de téléphone ne doit pas dépasser 20 caractères";
        }

        // Validation du message
        if (empty($data['message'])) {
            $errors['message'] = "Le message est requis";
        } elseif (strlen($data['message']) > 1600) {
            $errors['message'] = "Le message ne doit pas dépasser 1600 caractères";
        }

        // Validation du statut
        if (empty($data['status'])) {
            $errors['status'] = "Le statut est requis";
        } elseif (!in_array($data['status'], ['sent', 'failed', 'pending'])) {
            $errors['status'] = "Le statut doit être 'sent', 'failed' ou 'pending'";
        }

        // Validation du message d'erreur (optionnel)
        if (isset($data['errorMessage']) && strlen($data['errorMessage']) > 1000) {
            $errors['errorMessage'] = "Le message d'erreur ne doit pas dépasser 1000 caractères";
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

        // Vérifier que l'enregistrement existe
        $smsHistory = $this->smsHistoryRepository->findById($id);
        if (!$smsHistory) {
            $errors['id'] = "L'enregistrement d'historique SMS n'existe pas";
            return $errors;
        }

        // Validation du statut (si présent)
        if (isset($data['status']) && !in_array($data['status'], ['sent', 'failed', 'pending'])) {
            $errors['status'] = "Le statut doit être 'sent', 'failed' ou 'pending'";
        }

        // Validation du message d'erreur (si présent)
        if (isset($data['errorMessage']) && strlen($data['errorMessage']) > 1000) {
            $errors['errorMessage'] = "Le message d'erreur ne doit pas dépasser 1000 caractères";
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

        // Vérifier que l'enregistrement existe
        $smsHistory = $this->smsHistoryRepository->findById($id);
        if (!$smsHistory) {
            $errors['id'] = "L'enregistrement d'historique SMS n'existe pas";
        }

        return $errors;
    }

    /**
     * Valide les données pour la recherche d'enregistrements d'historique SMS
     * 
     * @param array $criteria Critères de recherche
     * @return array Critères validés
     * @throws ValidationException Si les critères sont invalides
     */
    public function validateSearchCriteria(array $criteria): array
    {
        $errors = [];

        // Validation du statut (si présent)
        if (isset($criteria['status']) && !in_array($criteria['status'], ['sent', 'failed', 'pending', 'all'])) {
            $errors['status'] = "Le statut doit être 'sent', 'failed', 'pending' ou 'all'";
        }

        // Validation de la date de début (si présente)
        if (isset($criteria['startDate']) && !strtotime($criteria['startDate'])) {
            $errors['startDate'] = "La date de début n'est pas valide";
        }

        // Validation de la date de fin (si présente)
        if (isset($criteria['endDate']) && !strtotime($criteria['endDate'])) {
            $errors['endDate'] = "La date de fin n'est pas valide";
        }

        // Validation de la limite (si présente)
        if (isset($criteria['limit']) && (!is_numeric($criteria['limit']) || $criteria['limit'] < 1)) {
            $errors['limit'] = "La limite doit être un nombre positif";
        }

        // Validation de l'offset (si présent)
        if (isset($criteria['offset']) && (!is_numeric($criteria['offset']) || $criteria['offset'] < 0)) {
            $errors['offset'] = "L'offset doit être un nombre positif ou zéro";
        }

        if (!empty($errors)) {
            throw new ValidationException("Validation des critères de recherche échouée", $errors);
        }

        return $criteria;
    }

    /**
     * Valide les données pour le réessai d'envoi d'un SMS
     * 
     * @param int $id ID de l'enregistrement d'historique SMS
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateRetry(int $id): array
    {
        $errors = [];

        // Vérifier que l'enregistrement existe
        $smsHistory = $this->smsHistoryRepository->findById($id);
        if (!$smsHistory) {
            $errors['id'] = "L'enregistrement d'historique SMS n'existe pas";
            throw new ValidationException("Validation du réessai d'envoi de SMS échouée", $errors);
        }

        // Vérifier que le statut est 'failed'
        if ($smsHistory->getStatus() !== 'failed') {
            $errors['id'] = "Seuls les SMS échoués peuvent être réessayés";
            throw new ValidationException("Validation du réessai d'envoi de SMS échouée", $errors);
        }

        return ['id' => $id];
    }
}

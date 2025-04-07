<?php

namespace App\Services\Validators;

use App\Repositories\AdminContactRepository;
use App\Repositories\CustomSegmentRepository;
use App\Exceptions\ValidationException;

/**
 * Validateur pour les contacts administrateur
 * 
 * Cette classe est responsable de la validation des contacts administrateur
 * et de leurs métadonnées associées.
 */
class AdminContactValidator extends AbstractValidator
{
    /**
     * @var AdminContactRepository
     */
    private $adminContactRepository;

    /**
     * @var CustomSegmentRepository
     */
    private $customSegmentRepository;

    /**
     * Constructeur
     * 
     * @param AdminContactRepository $adminContactRepository
     * @param CustomSegmentRepository $customSegmentRepository
     */
    public function __construct(
        AdminContactRepository $adminContactRepository,
        CustomSegmentRepository $customSegmentRepository
    ) {
        $this->adminContactRepository = $adminContactRepository;
        $this->customSegmentRepository = $customSegmentRepository;
    }

    /**
     * Valide les données pour la création d'un contact administrateur
     * 
     * @param array $data Données à valider
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateCreate(array $data): array
    {
        $errors = $this->validateCreateData($data);

        if (!empty($errors)) {
            throw new ValidationException("Validation du contact administrateur échouée", $errors);
        }

        return $data;
    }

    /**
     * Valide les données pour la mise à jour d'un contact administrateur
     * 
     * @param int $id ID du contact à mettre à jour
     * @param array $data Données à valider
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateUpdate(int $id, array $data): array
    {
        $errors = $this->validateUpdateData($id, $data);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la mise à jour du contact administrateur échouée", $errors);
        }

        return array_merge(['id' => $id], $data);
    }

    /**
     * Valide les données pour la suppression d'un contact administrateur
     * 
     * @param int $id ID du contact à supprimer
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateDelete(int $id): array
    {
        $errors = $this->validateDeleteData($id);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la suppression du contact administrateur échouée", $errors);
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
        } elseif ($this->adminContactRepository->findByPhoneNumber($data['phoneNumber'])) {
            $errors['phoneNumber'] = "Ce numéro de téléphone existe déjà";
        }

        // Validation du nom (optionnel)
        if (isset($data['name']) && strlen($data['name']) > 100) {
            $errors['name'] = "Le nom ne doit pas dépasser 100 caractères";
        }

        // Validation du segment (optionnel)
        if (isset($data['segmentId']) && $data['segmentId'] !== null) {
            if (!is_numeric($data['segmentId'])) {
                $errors['segmentId'] = "L'ID du segment doit être un nombre";
            } elseif (!$this->customSegmentRepository->findById($data['segmentId'])) {
                $errors['segmentId'] = "Le segment n'existe pas";
            }
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

        // Vérifier que le contact existe
        $adminContact = $this->adminContactRepository->findById($id);
        if (!$adminContact) {
            $errors['id'] = "Le contact administrateur n'existe pas";
            return $errors;
        }

        // Validation du numéro de téléphone (si présent)
        if (isset($data['phoneNumber'])) {
            if (strlen($data['phoneNumber']) < 8) {
                $errors['phoneNumber'] = "Le numéro de téléphone doit contenir au moins 8 caractères";
            } elseif (strlen($data['phoneNumber']) > 20) {
                $errors['phoneNumber'] = "Le numéro de téléphone ne doit pas dépasser 20 caractères";
            } else {
                // Vérifier que le numéro n'est pas déjà utilisé par un autre contact
                $existingContact = $this->adminContactRepository->findByPhoneNumber($data['phoneNumber']);
                if ($existingContact && $existingContact->getId() !== $id) {
                    $errors['phoneNumber'] = "Ce numéro de téléphone existe déjà";
                }
            }
        }

        // Validation du nom (si présent)
        if (isset($data['name']) && strlen($data['name']) > 100) {
            $errors['name'] = "Le nom ne doit pas dépasser 100 caractères";
        }

        // Validation du segment (si présent)
        if (isset($data['segmentId']) && $data['segmentId'] !== null) {
            if (!is_numeric($data['segmentId'])) {
                $errors['segmentId'] = "L'ID du segment doit être un nombre";
            } elseif (!$this->customSegmentRepository->findById($data['segmentId'])) {
                $errors['segmentId'] = "Le segment n'existe pas";
            }
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

        // Vérifier que le contact existe
        $adminContact = $this->adminContactRepository->findById($id);
        if (!$adminContact) {
            $errors['id'] = "Le contact administrateur n'existe pas";
        }

        return $errors;
    }

    /**
     * Valide les données pour l'ajout d'un contact administrateur à un segment
     * 
     * @param int $contactId
     * @param int $segmentId
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateAddToSegment(int $contactId, int $segmentId): array
    {
        $errors = [];

        // Vérifier que le contact existe
        $adminContact = $this->adminContactRepository->findById($contactId);
        if (!$adminContact) {
            $errors['contactId'] = "Le contact administrateur n'existe pas";
        }

        // Vérifier que le segment existe
        $customSegment = $this->customSegmentRepository->findById($segmentId);
        if (!$customSegment) {
            $errors['segmentId'] = "Le segment n'existe pas";
        }

        if (!empty($errors)) {
            throw new ValidationException("Validation de l'ajout du contact au segment échouée", $errors);
        }

        return [
            'contactId' => $contactId,
            'segmentId' => $segmentId
        ];
    }

    /**
     * Valide les données pour la suppression d'un contact administrateur d'un segment
     * 
     * @param int $contactId
     * @param int $segmentId
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateRemoveFromSegment(int $contactId, int $segmentId): array
    {
        $errors = [];

        // Vérifier que le contact existe
        $adminContact = $this->adminContactRepository->findById($contactId);
        if (!$adminContact) {
            $errors['contactId'] = "Le contact administrateur n'existe pas";
        }

        // Vérifier que le segment existe
        $customSegment = $this->customSegmentRepository->findById($segmentId);
        if (!$customSegment) {
            $errors['segmentId'] = "Le segment n'existe pas";
        }

        // Vérifier que le contact est bien dans le segment
        if ($adminContact && $customSegment && $adminContact->getSegmentId() !== $segmentId) {
            $errors['contactId'] = "Le contact n'est pas dans ce segment";
        }

        if (!empty($errors)) {
            throw new ValidationException("Validation de la suppression du contact du segment échouée", $errors);
        }

        return [
            'contactId' => $contactId,
            'segmentId' => $segmentId
        ];
    }

    /**
     * Valide les données pour l'import de contacts administrateur
     * 
     * @param array $contacts
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateImport(array $contacts): array
    {
        $errors = [];
        $validatedContacts = [];

        foreach ($contacts as $index => $contact) {
            $contactErrors = [];

            // Validation du numéro de téléphone
            if (empty($contact['phoneNumber'])) {
                $contactErrors['phoneNumber'] = "Le numéro de téléphone est requis";
            } elseif (strlen($contact['phoneNumber']) < 8) {
                $contactErrors['phoneNumber'] = "Le numéro de téléphone doit contenir au moins 8 caractères";
            } elseif (strlen($contact['phoneNumber']) > 20) {
                $contactErrors['phoneNumber'] = "Le numéro de téléphone ne doit pas dépasser 20 caractères";
            }

            // Validation du nom (optionnel)
            if (isset($contact['name']) && strlen($contact['name']) > 100) {
                $contactErrors['name'] = "Le nom ne doit pas dépasser 100 caractères";
            }

            // Validation du segment (optionnel)
            if (isset($contact['segmentId']) && $contact['segmentId'] !== null) {
                if (!is_numeric($contact['segmentId'])) {
                    $contactErrors['segmentId'] = "L'ID du segment doit être un nombre";
                } elseif (!$this->customSegmentRepository->findById($contact['segmentId'])) {
                    $contactErrors['segmentId'] = "Le segment n'existe pas";
                }
            }

            if (!empty($contactErrors)) {
                $errors[$index] = $contactErrors;
            } else {
                $validatedContacts[] = $contact;
            }
        }

        if (!empty($errors)) {
            throw new ValidationException("Validation de l'import des contacts administrateur échouée", $errors);
        }

        return $validatedContacts;
    }
}

<?php

namespace App\Services\Validators;

use App\Repositories\CustomSegmentRepository;
use App\Services\Interfaces\RegexValidatorInterface;
use App\Exceptions\ValidationException;

/**
 * Validateur pour les segments personnalisés
 * 
 * Cette classe est responsable de la validation des segments personnalisés
 * et de leurs métadonnées associées.
 */
class CustomSegmentValidator extends AbstractValidator
{
    /**
     * @var CustomSegmentRepository
     */
    private $customSegmentRepository;

    /**
     * @var RegexValidatorInterface
     */
    private $regexValidator;

    /**
     * Constructeur
     * 
     * @param CustomSegmentRepository $customSegmentRepository
     * @param RegexValidatorInterface $regexValidator
     */
    public function __construct(
        CustomSegmentRepository $customSegmentRepository,
        RegexValidatorInterface $regexValidator
    ) {
        $this->customSegmentRepository = $customSegmentRepository;
        $this->regexValidator = $regexValidator;
    }

    /**
     * Valide les données pour la création d'un segment personnalisé
     * 
     * @param array $data Données à valider
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateCreate(array $data): array
    {
        $errors = $this->validateCreateData($data);

        if (!empty($errors)) {
            throw new ValidationException("Validation du segment personnalisé échouée", $errors);
        }

        return $data;
    }

    /**
     * Valide les données pour la mise à jour d'un segment personnalisé
     * 
     * @param int $id ID du segment à mettre à jour
     * @param array $data Données à valider
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateUpdate(int $id, array $data): array
    {
        $errors = $this->validateUpdateData($id, $data);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la mise à jour du segment personnalisé échouée", $errors);
        }

        return array_merge(['id' => $id], $data);
    }

    /**
     * Valide les données pour la suppression d'un segment personnalisé
     * 
     * @param int $id ID du segment à supprimer
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateDelete(int $id): array
    {
        $errors = $this->validateDeleteData($id);

        if (!empty($errors)) {
            throw new ValidationException("Validation de la suppression du segment personnalisé échouée", $errors);
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

        // Validation du nom
        if (empty($data['name'])) {
            $errors['name'] = "Le nom du segment est requis";
        } elseif (strlen($data['name']) < 3) {
            $errors['name'] = "Le nom du segment doit contenir au moins 3 caractères";
        } elseif (strlen($data['name']) > 50) {
            $errors['name'] = "Le nom du segment ne doit pas dépasser 50 caractères";
        } elseif ($this->customSegmentRepository->findByName($data['name'])) {
            $errors['name'] = "Ce nom de segment existe déjà";
        }

        // Validation du pattern (expression régulière)
        if (isset($data['pattern']) && !empty($data['pattern'])) {
            if (!$this->regexValidator->isValid($data['pattern'])) {
                $errors['pattern'] = "L'expression régulière n'est pas valide";
            }
        }

        // Validation de la description (optionnelle)
        if (isset($data['description']) && strlen($data['description']) > 255) {
            $errors['description'] = "La description ne doit pas dépasser 255 caractères";
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

        // Vérifier que le segment existe
        $customSegment = $this->customSegmentRepository->findById($id);
        if (!$customSegment) {
            $errors['id'] = "Le segment personnalisé n'existe pas";
            return $errors;
        }

        // Validation du nom (si présent)
        if (isset($data['name']) && !empty($data['name'])) {
            if (strlen($data['name']) < 3) {
                $errors['name'] = "Le nom du segment doit contenir au moins 3 caractères";
            } elseif (strlen($data['name']) > 50) {
                $errors['name'] = "Le nom du segment ne doit pas dépasser 50 caractères";
            } else {
                // Vérifier que le nom n'est pas déjà utilisé par un autre segment
                $existingSegment = $this->customSegmentRepository->findByName($data['name']);
                if ($existingSegment && $existingSegment->getId() !== $id) {
                    $errors['name'] = "Ce nom de segment existe déjà";
                }
            }
        }

        // Validation du pattern (si présent)
        if (isset($data['pattern']) && !empty($data['pattern'])) {
            if (!$this->regexValidator->isValid($data['pattern'])) {
                $errors['pattern'] = "L'expression régulière n'est pas valide";
            }
        }

        // Validation de la description (si présente)
        if (isset($data['description']) && strlen($data['description']) > 255) {
            $errors['description'] = "La description ne doit pas dépasser 255 caractères";
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

        // Vérifier que le segment existe
        $customSegment = $this->customSegmentRepository->findById($id);
        if (!$customSegment) {
            $errors['id'] = "Le segment personnalisé n'existe pas";
        }

        return $errors;
    }

    /**
     * Valide une expression régulière
     * 
     * @param string $pattern
     * @return bool
     */
    public function validateRegex(string $pattern): bool
    {
        return $this->regexValidator->isValid($pattern);
    }

    /**
     * Valide les données pour la création d'un segment personnalisé avec un nom et un pattern
     * 
     * @param string $name
     * @param string $pattern
     * @param string $description
     * @return array Données validées
     * @throws ValidationException Si les données sont invalides
     */
    public function validateCreateWithPattern(
        string $name,
        string $pattern,
        string $description = ''
    ): array {
        $data = [
            'name' => $name,
            'pattern' => $pattern,
            'description' => $description
        ];

        $errors = $this->validateCreateData($data);

        if (!empty($errors)) {
            throw new ValidationException("Validation du segment personnalisé échouée", $errors);
        }

        return $data;
    }
}

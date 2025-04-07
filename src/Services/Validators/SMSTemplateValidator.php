<?php

namespace App\Services\Validators;

use App\Exceptions\ValidationException;
use App\Models\SMSTemplate;
use App\Services\Interfaces\ValidatorInterface;

/**
 * Validateur pour les modèles de SMS
 */
class SMSTemplateValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * Valide les données pour la création d'une entité
     * 
     * @param array $data Données à valider
     * @return array Tableau d'erreurs (vide si aucune erreur)
     */
    protected function validateCreateData(array $data): array
    {
        return $this->validateTemplate($data);
    }

    /**
     * Valide les données pour la mise à jour d'une entité
     * 
     * @param int $id ID de l'entité à mettre à jour
     * @param array $data Données à valider
     * @return array Tableau d'erreurs (vide si aucune erreur)
     */
    protected function validateUpdateData(int $id, array $data): array
    {
        $errors = [];

        // Validation de l'ID
        if ($id <= 0) {
            $errors['id'] = 'L\'ID est invalide';
        }

        // Validation des autres champs
        $templateErrors = $this->validateTemplate($data);

        return array_merge($errors, $templateErrors);
    }

    /**
     * Valide les données pour la suppression d'une entité
     * 
     * @param int $id ID de l'entité à supprimer
     * @return array Tableau d'erreurs (vide si aucune erreur)
     */
    protected function validateDeleteData(int $id): array
    {
        $errors = [];

        if ($id <= 0) {
            $errors['id'] = 'L\'ID est invalide';
        }

        return $errors;
    }

    /**
     * Valide les champs communs d'un modèle de SMS
     * 
     * @param array $data Données à valider
     * @return array Tableau d'erreurs (vide si aucune erreur)
     */
    private function validateTemplate(array $data): array
    {
        $errors = [];

        // Validation du titre
        if (!isset($data['title']) || empty($data['title'])) {
            $errors['title'] = 'Le titre est obligatoire';
        } elseif (strlen($data['title']) > 100) {
            $errors['title'] = 'Le titre ne doit pas dépasser 100 caractères';
        }

        // Validation du contenu
        if (!isset($data['content']) || empty($data['content'])) {
            $errors['content'] = 'Le contenu est obligatoire';
        } elseif (strlen($data['content']) > 1000) {
            $errors['content'] = 'Le contenu ne doit pas dépasser 1000 caractères';
        }

        // Validation de la description (optionnelle)
        if (isset($data['description']) && strlen($data['description']) > 500) {
            $errors['description'] = 'La description ne doit pas dépasser 500 caractères';
        }

        // Validation de l'ID utilisateur
        if (!isset($data['userId']) || !is_numeric($data['userId']) || $data['userId'] <= 0) {
            $errors['userId'] = 'L\'ID utilisateur est invalide';
        }

        return $errors;
    }

    /**
     * Crée un modèle de SMS à partir de données validées
     * 
     * @param array $data Données validées
     * @return SMSTemplate Modèle de SMS créé
     */
    public function createFromValidatedData(array $data): SMSTemplate
    {
        $template = new SMSTemplate(
            $data['id'] ?? 0,
            $data['userId'],
            $data['title'],
            $data['content'],
            $data['description'] ?? null,
            $data['createdAt'] ?? null,
            $data['updatedAt'] ?? null
        );

        return $template;
    }
}

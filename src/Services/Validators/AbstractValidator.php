<?php

namespace App\Services\Validators;

use App\Services\Interfaces\ValidatorInterface;

/**
 * Classe abstraite pour les validateurs
 * 
 * Cette classe fournit une implémentation de base pour les validateurs
 * et définit les méthodes abstraites que les classes enfants doivent implémenter.
 */
abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * Valide les données pour la création d'une entité
     * 
     * @param array $data Données à valider
     * @return array Données validées
     * @throws \App\Exceptions\ValidationException Si les données sont invalides
     */
    public function validateCreate(array $data): array
    {
        $errors = $this->validateCreateData($data);

        if (!empty($errors)) {
            throw new \App\Exceptions\ValidationException("Validation échouée", $errors);
        }

        return $data;
    }

    /**
     * Valide les données pour la mise à jour d'une entité
     * 
     * @param int $id ID de l'entité à mettre à jour
     * @param array $data Données à valider
     * @return array Données validées
     * @throws \App\Exceptions\ValidationException Si les données sont invalides
     */
    public function validateUpdate(int $id, array $data): array
    {
        $errors = $this->validateUpdateData($id, $data);

        if (!empty($errors)) {
            throw new \App\Exceptions\ValidationException("Validation de la mise à jour échouée", $errors);
        }

        return array_merge(['id' => $id], $data);
    }

    /**
     * Valide les données pour la suppression d'une entité
     * 
     * @param int $id ID de l'entité à supprimer
     * @return array Données validées
     * @throws \App\Exceptions\ValidationException Si les données sont invalides
     */
    public function validateDelete(int $id): array
    {
        $errors = $this->validateDeleteData($id);

        if (!empty($errors)) {
            throw new \App\Exceptions\ValidationException("Validation de la suppression échouée", $errors);
        }

        return ['id' => $id];
    }

    /**
     * Valide les données pour la création d'une entité
     * 
     * @param array $data Données à valider
     * @return array Tableau d'erreurs (vide si aucune erreur)
     */
    abstract protected function validateCreateData(array $data): array;

    /**
     * Valide les données pour la mise à jour d'une entité
     * 
     * @param int $id ID de l'entité à mettre à jour
     * @param array $data Données à valider
     * @return array Tableau d'erreurs (vide si aucune erreur)
     */
    abstract protected function validateUpdateData(int $id, array $data): array;

    /**
     * Valide les données pour la suppression d'une entité
     * 
     * @param int $id ID de l'entité à supprimer
     * @return array Tableau d'erreurs (vide si aucune erreur)
     */
    abstract protected function validateDeleteData(int $id): array;
}

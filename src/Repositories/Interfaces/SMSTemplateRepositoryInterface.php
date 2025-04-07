<?php

namespace App\Repositories\Interfaces;

use App\Models\SMSTemplate;

/**
 * Interface pour le repository des modèles de SMS
 */
interface SMSTemplateRepositoryInterface extends RepositoryInterface
{
    /**
     * Récupérer tous les modèles de SMS d'un utilisateur
     * 
     * @param int $userId Identifiant de l'utilisateur
     * @param int $limit Limite de résultats (pagination)
     * @param int $offset Offset de résultats (pagination)
     * @return array Liste des modèles de SMS
     */
    public function findByUserId(int $userId, int $limit = 10, int $offset = 0): array;

    /**
     * Compter le nombre de modèles de SMS d'un utilisateur
     * 
     * @param int $userId Identifiant de l'utilisateur
     * @return int Nombre de modèles
     */
    public function countByUserId(int $userId): int;

    /**
     * Rechercher des modèles de SMS par titre ou contenu
     * 
     * @param string $query Terme de recherche
     * @param array|null $fields Champs à rechercher (null = tous les champs)
     * @param int|null $limit Limite de résultats (pagination)
     * @param int|null $offset Offset de résultats (pagination)
     * @return array Liste des modèles de SMS correspondants
     */
    public function search(string $query, ?array $fields = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * Rechercher des modèles de SMS par utilisateur et terme de recherche
     * 
     * @param int $userId Identifiant de l'utilisateur
     * @param string $search Terme de recherche
     * @param int $limit Limite de résultats (pagination)
     * @param int $offset Offset de résultats (pagination)
     * @return array Liste des modèles de SMS correspondants
     */
    public function searchByUser(int $userId, string $search, int $limit = 10, int $offset = 0): array;

    /**
     * Créer un nouveau modèle de SMS
     * 
     * @param SMSTemplate $template Modèle de SMS à créer
     * @return SMSTemplate Modèle créé avec ID généré
     */
    public function create(SMSTemplate $template): SMSTemplate;

    /**
     * Mettre à jour un modèle de SMS existant
     * 
     * @param SMSTemplate $template Modèle de SMS à mettre à jour
     * @return bool Succès de la mise à jour
     */
    public function update(SMSTemplate $template): bool;

    /**
     * Supprimer une entité
     * 
     * @param mixed $entity L'entité à supprimer
     * @return bool Succès de la suppression
     */
    public function delete($entity): bool;

    /**
     * Supprimer un modèle de SMS par son ID
     * 
     * @param int $id Identifiant du modèle à supprimer
     * @return bool Succès de la suppression
     */
    public function deleteById(int $id): bool;

    /**
     * Récupérer un modèle de SMS par son ID
     * 
     * @param int $id Identifiant du modèle
     * @return SMSTemplate|null Modèle trouvé ou null
     */
    public function findById(int $id): ?SMSTemplate;
}

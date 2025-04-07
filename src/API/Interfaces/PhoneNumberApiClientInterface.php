<?php

namespace App\API\Interfaces;

use App\Models\PhoneNumber;

/**
 * Interface PhoneNumberApiClientInterface
 * 
 * Interface spécifique pour les clients de l'API des numéros de téléphone.
 * Suit le principe d'Interface Segregation (ISP) de SOLID en exposant uniquement
 * les méthodes nécessaires aux clients de l'API des numéros de téléphone.
 */
interface PhoneNumberApiClientInterface
{
    /**
     * Récupère un numéro de téléphone par son ID
     * 
     * @param int $id L'ID du numéro de téléphone
     * @return array|null Les données du numéro de téléphone ou null si non trouvé
     */
    public function getPhoneNumber(int $id): ?array;

    /**
     * Récupère un numéro de téléphone par son numéro
     * 
     * @param string $number Le numéro de téléphone
     * @return array|null Les données du numéro de téléphone ou null si non trouvé
     */
    public function getPhoneNumberByNumber(string $number): ?array;

    /**
     * Récupère tous les numéros de téléphone
     * 
     * @param int|null $limit Limite le nombre de numéros retournés
     * @param int|null $offset Décalage pour la pagination
     * @return array Les numéros de téléphone
     */
    public function getPhoneNumbers(?int $limit = null, ?int $offset = null): array;

    /**
     * Crée un nouveau numéro de téléphone
     * 
     * @param string $number Le numéro de téléphone
     * @param string|null $civility La civilité
     * @param string|null $firstName Le prénom
     * @param string|null $name Le nom
     * @param string|null $company L'entreprise
     * @param string|null $sector Le secteur d'activité
     * @param string|null $notes Les notes
     * @return array Les données du numéro de téléphone créé
     */
    public function createPhoneNumber(
        string $number,
        ?string $civility = null,
        ?string $firstName = null,
        ?string $name = null,
        ?string $company = null,
        ?string $sector = null,
        ?string $notes = null
    ): array;

    /**
     * Met à jour un numéro de téléphone
     * 
     * @param int $id L'ID du numéro de téléphone
     * @param string|null $civility La civilité
     * @param string|null $firstName Le prénom
     * @param string|null $name Le nom
     * @param string|null $company L'entreprise
     * @param string|null $sector Le secteur d'activité
     * @param string|null $notes Les notes
     * @return array Les données du numéro de téléphone mis à jour
     */
    public function updatePhoneNumber(
        int $id,
        ?string $civility = null,
        ?string $firstName = null,
        ?string $name = null,
        ?string $company = null,
        ?string $sector = null,
        ?string $notes = null
    ): array;

    /**
     * Supprime un numéro de téléphone
     * 
     * @param int $id L'ID du numéro de téléphone
     * @return bool True si la suppression a réussi, false sinon
     */
    public function deletePhoneNumber(int $id): bool;

    /**
     * Recherche des numéros de téléphone
     * 
     * @param string $query La requête de recherche
     * @param int|null $limit Limite le nombre de numéros retournés
     * @param int|null $offset Décalage pour la pagination
     * @return array Les numéros de téléphone trouvés
     */
    public function searchPhoneNumbers(string $query, ?int $limit = null, ?int $offset = null): array;

    /**
     * Segmente un numéro de téléphone
     * 
     * @param string $number Le numéro de téléphone
     * @return array Les segments du numéro de téléphone
     */
    public function segmentPhoneNumber(string $number): array;
}

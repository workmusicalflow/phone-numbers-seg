<?php

namespace App\API\Interfaces;

/**
 * Interface SegmentApiClientInterface
 * 
 * Interface spécifique pour les clients de l'API des segments.
 * Suit le principe d'Interface Segregation (ISP) de SOLID en exposant uniquement
 * les méthodes nécessaires aux clients de l'API des segments.
 */
interface SegmentApiClientInterface
{
    /**
     * Récupère un segment personnalisé par son ID
     * 
     * @param int $id L'ID du segment personnalisé
     * @return array|null Les données du segment personnalisé ou null si non trouvé
     */
    public function getCustomSegment(int $id): ?array;

    /**
     * Récupère tous les segments personnalisés
     * 
     * @param int|null $limit Limite le nombre de segments retournés
     * @param int|null $offset Décalage pour la pagination
     * @return array Les segments personnalisés
     */
    public function getCustomSegments(?int $limit = null, ?int $offset = null): array;

    /**
     * Crée un nouveau segment personnalisé
     * 
     * @param string $name Le nom du segment personnalisé
     * @param string|null $description La description du segment personnalisé
     * @param string|null $pattern L'expression régulière pour la correspondance automatique
     * @return array Les données du segment personnalisé créé
     */
    public function createCustomSegment(string $name, ?string $description = null, ?string $pattern = null): array;

    /**
     * Met à jour un segment personnalisé
     * 
     * @param int $id L'ID du segment personnalisé
     * @param string $name Le nom du segment personnalisé
     * @param string|null $description La description du segment personnalisé
     * @param string|null $pattern L'expression régulière pour la correspondance automatique
     * @return array Les données du segment personnalisé mis à jour
     */
    public function updateCustomSegment(int $id, string $name, ?string $description = null, ?string $pattern = null): array;

    /**
     * Supprime un segment personnalisé
     * 
     * @param int $id L'ID du segment personnalisé
     * @return bool True si la suppression a réussi, false sinon
     */
    public function deleteCustomSegment(int $id): bool;

    /**
     * Récupère les numéros de téléphone dans un segment
     * 
     * @param int $segmentId L'ID du segment
     * @param int|null $limit Limite le nombre de numéros retournés
     * @param int|null $offset Décalage pour la pagination
     * @return array Les numéros de téléphone dans le segment
     */
    public function getPhoneNumbersInSegment(int $segmentId, ?int $limit = null, ?int $offset = null): array;

    /**
     * Ajoute un numéro de téléphone à un segment
     * 
     * @param int $segmentId L'ID du segment
     * @param int $phoneNumberId L'ID du numéro de téléphone
     * @return bool True si l'ajout a réussi, false sinon
     */
    public function addPhoneNumberToSegment(int $segmentId, int $phoneNumberId): bool;

    /**
     * Retire un numéro de téléphone d'un segment
     * 
     * @param int $segmentId L'ID du segment
     * @param int $phoneNumberId L'ID du numéro de téléphone
     * @return bool True si le retrait a réussi, false sinon
     */
    public function removePhoneNumberFromSegment(int $segmentId, int $phoneNumberId): bool;

    /**
     * Valide une expression régulière pour un segment personnalisé
     * 
     * @param string $pattern L'expression régulière à valider
     * @return array Le résultat de la validation
     */
    public function validateSegmentPattern(string $pattern): array;

    /**
     * Teste une expression régulière sur un numéro de téléphone
     * 
     * @param string $pattern L'expression régulière à tester
     * @param string $phoneNumber Le numéro de téléphone à tester
     * @return bool True si le numéro correspond au pattern, false sinon
     */
    public function testSegmentPattern(string $pattern, string $phoneNumber): bool;
}

<?php

namespace App\GraphQL\Controllers;

use App\Models\AdminContact;
use App\Repositories\AdminContactRepository;
use App\Repositories\CustomSegmentRepository;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Contrôleur GraphQL pour la gestion des contacts administrateur
 * 
 * @Type
 */
class AdminContactController
{
    private $adminContactRepository;
    private $customSegmentRepository;

    public function __construct(
        AdminContactRepository $adminContactRepository,
        CustomSegmentRepository $customSegmentRepository
    ) {
        $this->adminContactRepository = $adminContactRepository;
        $this->customSegmentRepository = $customSegmentRepository;
    }

    /**
     * Récupère tous les contacts administrateur
     * 
     * @Query
     * @return AdminContact[]
     */
    public function adminContacts(): array
    {
        // TODO: Ajouter une vérification d'authentification (admin uniquement)
        return $this->adminContactRepository->findAll();
    }

    /**
     * Récupère un contact administrateur par son ID
     * 
     * @Query
     * @param int $id
     * @return AdminContact|null
     */
    public function adminContact(int $id): ?AdminContact
    {
        // TODO: Ajouter une vérification d'authentification (admin uniquement)
        return $this->adminContactRepository->findById($id);
    }

    /**
     * Récupère les contacts administrateur d'un segment
     * 
     * @Query
     * @param int $segmentId
     * @return AdminContact[]
     */
    public function adminContactsBySegment(int $segmentId): array
    {
        // TODO: Ajouter une vérification d'authentification (admin uniquement)
        return $this->adminContactRepository->findBySegmentId($segmentId);
    }

    /**
     * Crée un nouveau contact administrateur
     * 
     * @Mutation
     * @param string $phoneNumber
     * @param string|null $name
     * @param int|null $segmentId
     * @return AdminContact
     */
    public function createAdminContact(
        string $phoneNumber,
        ?string $name = null,
        ?int $segmentId = null
    ): AdminContact {
        // TODO: Ajouter une vérification d'authentification (admin uniquement)

        // Vérifier si le segment existe (si segmentId est fourni)
        if ($segmentId !== null) {
            $segment = $this->customSegmentRepository->findById($segmentId);
            if (!$segment) {
                throw new \Exception("Segment non trouvé");
            }
        }

        // Vérifier si le numéro de téléphone est valide
        if (!preg_match('/^\+?[0-9]{10,15}$/', $phoneNumber)) {
            throw new \Exception("Format de numéro de téléphone invalide");
        }

        // Normaliser le numéro de téléphone (ajouter le préfixe + si nécessaire)
        if (substr($phoneNumber, 0, 1) !== '+') {
            $phoneNumber = '+' . $phoneNumber;
        }

        // Vérifier si le contact existe déjà
        $existingContact = $this->adminContactRepository->findByPhoneNumber($phoneNumber);
        if ($existingContact) {
            throw new \Exception("Un contact avec ce numéro de téléphone existe déjà");
        }

        // Créer le contact
        $adminContact = new AdminContact(null, $segmentId, $phoneNumber, $name);

        // Sauvegarder le contact
        return $this->adminContactRepository->save($adminContact);
    }

    /**
     * Met à jour un contact administrateur
     * 
     * @Mutation
     * @param int $id
     * @param string|null $name
     * @param int|null $segmentId
     * @return AdminContact
     */
    public function updateAdminContact(
        int $id,
        ?string $name = null,
        ?int $segmentId = null
    ): AdminContact {
        // TODO: Ajouter une vérification d'authentification (admin uniquement)

        // Récupérer le contact
        $adminContact = $this->adminContactRepository->findById($id);
        if (!$adminContact) {
            throw new \Exception("Contact administrateur non trouvé");
        }

        // Vérifier si le segment existe (si segmentId est fourni)
        if ($segmentId !== null) {
            $segment = $this->customSegmentRepository->findById($segmentId);
            if (!$segment) {
                throw new \Exception("Segment non trouvé");
            }
            $adminContact->setSegmentId($segmentId);
        }

        // Mettre à jour le nom
        if ($name !== null) {
            $adminContact->setName($name);
        }

        // Sauvegarder le contact
        return $this->adminContactRepository->save($adminContact);
    }

    /**
     * Supprime un contact administrateur
     * 
     * @Mutation
     * @param int $id
     * @return bool
     */
    public function deleteAdminContact(int $id): bool
    {
        // TODO: Ajouter une vérification d'authentification (admin uniquement)

        // Récupérer le contact
        $adminContact = $this->adminContactRepository->findById($id);
        if (!$adminContact) {
            throw new \Exception("Contact administrateur non trouvé");
        }

        // Supprimer le contact
        return $this->adminContactRepository->delete($id);
    }
}

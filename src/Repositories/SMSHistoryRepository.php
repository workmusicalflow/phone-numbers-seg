<?php

namespace App\Repositories;

use App\Models\SMSHistory;
use PDO;

/**
 * Repository pour la gestion des enregistrements d'historique SMS
 */
class SMSHistoryRepository
{
    /**
     * @var PDO Instance de PDO
     */
    private PDO $db;

    /**
     * Constructeur
     *
     * @param PDO $db Instance de PDO
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Enregistrer un nouvel historique SMS ou mettre à jour un existant
     *
     * @param SMSHistory $smsHistory
     * @return SMSHistory
     */
    public function save(SMSHistory $smsHistory): SMSHistory
    {
        if ($smsHistory->getId() === null) {
            // Insertion d'un nouvel enregistrement
            $stmt = $this->db->prepare(
                'INSERT INTO sms_history (
                    phone_number_id, phone_number, message, status, message_id, 
                    error_message, sender_address, sender_name, segment_id, created_at
                ) VALUES (
                    :phone_number_id, :phone_number, :message, :status, :message_id, 
                    :error_message, :sender_address, :sender_name, :segment_id, :created_at
                )'
            );

            $stmt->bindValue(':phone_number_id', $smsHistory->getPhoneNumberId(), PDO::PARAM_INT);
            $stmt->bindValue(':phone_number', $smsHistory->getPhoneNumber(), PDO::PARAM_STR);
            $stmt->bindValue(':message', $smsHistory->getMessage(), PDO::PARAM_STR);
            $stmt->bindValue(':status', $smsHistory->getStatus(), PDO::PARAM_STR);
            $stmt->bindValue(':message_id', $smsHistory->getMessageId(), PDO::PARAM_STR);
            $stmt->bindValue(':error_message', $smsHistory->getErrorMessage(), PDO::PARAM_STR);
            $stmt->bindValue(':sender_address', $smsHistory->getSenderAddress(), PDO::PARAM_STR);
            $stmt->bindValue(':sender_name', $smsHistory->getSenderName(), PDO::PARAM_STR);
            $stmt->bindValue(':segment_id', $smsHistory->getSegmentId(), PDO::PARAM_INT);
            $stmt->bindValue(':created_at', $smsHistory->getCreatedAt(), PDO::PARAM_STR);

            $stmt->execute();

            // Récupérer l'ID généré
            $id = (int) $this->db->lastInsertId();
            $smsHistory->setId($id);
        } else {
            // Mise à jour d'un enregistrement existant
            $stmt = $this->db->prepare(
                'UPDATE sms_history SET 
                    phone_number_id = :phone_number_id,
                    phone_number = :phone_number,
                    message = :message,
                    status = :status,
                    message_id = :message_id,
                    error_message = :error_message,
                    sender_address = :sender_address,
                    sender_name = :sender_name,
                    segment_id = :segment_id,
                    created_at = :created_at
                WHERE id = :id'
            );

            $stmt->bindValue(':id', $smsHistory->getId(), PDO::PARAM_INT);
            $stmt->bindValue(':phone_number_id', $smsHistory->getPhoneNumberId(), PDO::PARAM_INT);
            $stmt->bindValue(':phone_number', $smsHistory->getPhoneNumber(), PDO::PARAM_STR);
            $stmt->bindValue(':message', $smsHistory->getMessage(), PDO::PARAM_STR);
            $stmt->bindValue(':status', $smsHistory->getStatus(), PDO::PARAM_STR);
            $stmt->bindValue(':message_id', $smsHistory->getMessageId(), PDO::PARAM_STR);
            $stmt->bindValue(':error_message', $smsHistory->getErrorMessage(), PDO::PARAM_STR);
            $stmt->bindValue(':sender_address', $smsHistory->getSenderAddress(), PDO::PARAM_STR);
            $stmt->bindValue(':sender_name', $smsHistory->getSenderName(), PDO::PARAM_STR);
            $stmt->bindValue(':segment_id', $smsHistory->getSegmentId(), PDO::PARAM_INT);
            $stmt->bindValue(':created_at', $smsHistory->getCreatedAt(), PDO::PARAM_STR);

            $stmt->execute();
        }

        return $smsHistory;
    }

    /**
     * Trouver un enregistrement d'historique SMS par son ID
     *
     * @param int $id
     * @return SMSHistory|null
     */
    public function findById(int $id): ?SMSHistory
    {
        $stmt = $this->db->prepare('SELECT * FROM sms_history WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->createSMSHistoryFromRow($row);
    }

    /**
     * Trouver tous les enregistrements d'historique SMS
     *
     * @param int $limit Limite de résultats
     * @param int $offset Offset pour la pagination
     * @return array
     */
    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM sms_history ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->createSMSHistoryFromRow($row);
        }

        return $results;
    }

    /**
     * Trouver les enregistrements d'historique SMS par numéro de téléphone
     *
     * @param string $phoneNumber
     * @param int $limit Limite de résultats
     * @param int $offset Offset pour la pagination
     * @return array
     */
    public function findByPhoneNumber(string $phoneNumber, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM sms_history WHERE phone_number = :phone_number ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':phone_number', $phoneNumber, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->createSMSHistoryFromRow($row);
        }

        return $results;
    }

    /**
     * Trouver les enregistrements d'historique SMS par ID de numéro de téléphone
     *
     * @param int $phoneNumberId
     * @param int $limit Limite de résultats
     * @param int $offset Offset pour la pagination
     * @return array
     */
    public function findByPhoneNumberId(int $phoneNumberId, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM sms_history WHERE phone_number_id = :phone_number_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':phone_number_id', $phoneNumberId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->createSMSHistoryFromRow($row);
        }

        return $results;
    }

    /**
     * Trouver les enregistrements d'historique SMS par ID de segment
     *
     * @param int $segmentId
     * @param int $limit Limite de résultats
     * @param int $offset Offset pour la pagination
     * @return array
     */
    public function findBySegmentId(int $segmentId, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM sms_history WHERE segment_id = :segment_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':segment_id', $segmentId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->createSMSHistoryFromRow($row);
        }

        return $results;
    }

    /**
     * Trouver les enregistrements d'historique SMS par statut
     *
     * @param string $status
     * @param int $limit Limite de résultats
     * @param int $offset Offset pour la pagination
     * @return array
     */
    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM sms_history WHERE status = :status ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->createSMSHistoryFromRow($row);
        }

        return $results;
    }

    /**
     * Compter le nombre total d'enregistrements d'historique SMS
     *
     * @return int
     */
    public function count(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM sms_history');
        return (int) $stmt->fetchColumn();
    }

    /**
     * Supprimer un enregistrement d'historique SMS
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM sms_history WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Créer un objet SMSHistory à partir d'une ligne de résultat de requête
     *
     * @param array $row
     * @return SMSHistory
     */
    private function createSMSHistoryFromRow(array $row): SMSHistory
    {
        return new SMSHistory(
            (int) $row['id'],
            $row['phone_number'],
            $row['message'],
            $row['status'],
            $row['sender_address'],
            $row['sender_name'],
            $row['phone_number_id'] !== null ? (int) $row['phone_number_id'] : null,
            $row['message_id'],
            $row['error_message'],
            $row['segment_id'] !== null ? (int) $row['segment_id'] : null,
            $row['created_at']
        );
    }
}

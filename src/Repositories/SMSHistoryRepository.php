<?php

namespace App\Repositories;

use App\Models\SMSHistory;
use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use PDO;

/**
 * Repository pour la gestion des enregistrements d'historique SMS
 */
class SMSHistoryRepository implements SMSHistoryRepositoryInterface
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
     * Créer un nouvel enregistrement d'historique SMS
     *
     * @param string $phoneNumber Numéro de téléphone
     * @param string $message Message envoyé
     * @param string $status Statut de l'envoi
     * @param string|null $messageId ID du message retourné par l'API
     * @param string|null $errorMessage Message d'erreur en cas d'échec
     * @param string $senderAddress Adresse de l'expéditeur
     * @param string $senderName Nom de l'expéditeur
     * @param int|null $segmentId ID du segment associé
     * @param int|null $phoneNumberId ID du numéro de téléphone associé
     * @return SMSHistory
     */
    public function create(
        string $phoneNumber,
        string $message,
        string $status,
        ?string $messageId = null,
        ?string $errorMessage = null,
        string $senderAddress = 'tel:+2250595016840',
        string $senderName = 'Qualitas CI',
        ?int $segmentId = null,
        ?int $phoneNumberId = null
    ): SMSHistory {
        $smsHistory = new SMSHistory(
            null,
            $phoneNumber,
            $message,
            $status,
            $senderAddress,
            $senderName,
            $phoneNumberId,
            $messageId,
            $errorMessage,
            $segmentId
        );

        return $this->save($smsHistory);
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
        // Vérifier si le numéro est au format international ou local
        $isInternational = strpos($phoneNumber, '+225') === 0;
        $isLocal = !$isInternational && (strlen($phoneNumber) === 10 || strlen($phoneNumber) === 8);

        if ($isInternational) {
            // Format international: rechercher aussi le format local
            $localNumber = $this->convertToLocalFormat($phoneNumber);

            $stmt = $this->db->prepare('SELECT * FROM sms_history WHERE phone_number = :phone_number OR phone_number = :local_number ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue(':phone_number', $phoneNumber, PDO::PARAM_STR);
            $stmt->bindValue(':local_number', $localNumber, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        } elseif ($isLocal) {
            // Format local: rechercher aussi le format international
            $internationalNumber = $this->convertToInternationalFormat($phoneNumber);

            $stmt = $this->db->prepare('SELECT * FROM sms_history WHERE phone_number = :phone_number OR phone_number = :international_number ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue(':phone_number', $phoneNumber, PDO::PARAM_STR);
            $stmt->bindValue(':international_number', $internationalNumber, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        } else {
            // Recherche exacte
            $stmt = $this->db->prepare('SELECT * FROM sms_history WHERE phone_number = :phone_number ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
            $stmt->bindValue(':phone_number', $phoneNumber, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->createSMSHistoryFromRow($row);
        }

        return $results;
    }

    /**
     * Convertir un numéro de téléphone du format international au format local
     *
     * @param string $phoneNumber
     * @return string
     */
    private function convertToLocalFormat(string $phoneNumber): string
    {
        // Si le numéro commence par +225, le convertir en format local
        if (strpos($phoneNumber, '+225') === 0) {
            $localNumber = substr($phoneNumber, 4); // Enlever le +225

            // Si le numéro commence par 0, le laisser tel quel
            if (strpos($localNumber, '0') === 0) {
                return $localNumber;
            }

            // Sinon, ajouter un 0 au début
            return '0' . $localNumber;
        }

        return $phoneNumber;
    }

    /**
     * Convertir un numéro de téléphone du format local au format international
     *
     * @param string $phoneNumber
     * @return string
     */
    private function convertToInternationalFormat(string $phoneNumber): string
    {
        // Supprimer tous les caractères non numériques
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Si le numéro commence par 0 et a 10 chiffres, c'est un numéro local
        if (strlen($cleaned) === 10 && substr($cleaned, 0, 1) === '0') {
            // Convertir en format international (Côte d'Ivoire +225)
            return '+225' . $cleaned;
        }

        // Si le numéro a 8 chiffres, c'est un numéro local sans le 0
        if (strlen($cleaned) === 8) {
            // Convertir en format international (Côte d'Ivoire +225)
            return '+2250' . $cleaned;
        }

        return $phoneNumber;
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
     * Alias de count() pour l'interface CountableRepositoryInterface
     *
     * @return int
     */
    public function countAll(): int
    {
        return $this->count();
    }

    /**
     * Compte le nombre de SMS envoyés à une date spécifique
     * 
     * @param string $date Date au format Y-m-d
     * @return int
     */
    public function countByDate(string $date): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM sms_history WHERE DATE(created_at) = :date');
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Récupère les comptes quotidiens de SMS pour une plage de dates
     * 
     * @param string $startDate Date de début au format Y-m-d
     * @param string $endDate Date de fin au format Y-m-d
     * @return array Tableau associatif avec les dates et les comptes
     */
    public function getDailyCountsForDateRange(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare('
            SELECT 
                DATE(created_at) as date, 
                COUNT(*) as count 
            FROM 
                sms_history 
            WHERE 
                DATE(created_at) BETWEEN :start_date AND :end_date 
            GROUP BY 
                DATE(created_at) 
            ORDER BY 
                DATE(created_at)
        ');

        $stmt->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        $stmt->bindValue(':end_date', $endDate, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trouve des entités selon des critères spécifiques
     * 
     * @param array $criteria Critères de recherche
     * @param array|null $orderBy Critères de tri
     * @param int|null $limit Nombre maximum d'entités à retourner
     * @param int|null $offset Décalage pour la pagination
     * @return array
     */
    public function findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null): array
    {
        $sql = 'SELECT * FROM sms_history WHERE 1=1';
        $params = [];

        // Ajouter les critères de recherche
        foreach ($criteria as $field => $value) {
            $sql .= " AND $field = :$field";
            $params[$field] = $value;
        }

        // Ajouter les critères de tri
        if ($orderBy !== null && !empty($orderBy)) {
            $sql .= ' ORDER BY';
            $first = true;
            foreach ($orderBy as $field => $direction) {
                if (!$first) {
                    $sql .= ',';
                }
                $sql .= " $field $direction";
                $first = false;
            }
        } else {
            // Tri par défaut
            $sql .= ' ORDER BY created_at DESC';
        }

        // Ajouter la limite et l'offset
        if ($limit !== null) {
            $sql .= ' LIMIT :limit';
            if ($offset !== null) {
                $sql .= ' OFFSET :offset';
            }
        }

        $stmt = $this->db->prepare($sql);

        // Lier les paramètres des critères
        foreach ($params as $field => $value) {
            $stmt->bindValue(":$field", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        // Lier les paramètres de limite et d'offset
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            if ($offset !== null) {
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
        }

        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->createSMSHistoryFromRow($row);
        }

        return $results;
    }

    /**
     * Trouve une entité par son identifiant
     * 
     * @param int $id Identifiant de l'entité
     * @return object|null
     */
    public function find(int $id): ?object
    {
        return $this->findById($id);
    }

    /**
     * Mettre à jour l'ID de segment pour les enregistrements d'historique SMS récents
     * correspondant aux numéros de téléphone spécifiés
     *
     * @param array $phoneNumbers Tableau de numéros de téléphone
     * @param int $segmentId ID du segment à définir
     * @return bool Statut de succès
     */
    public function updateSegmentIdForPhoneNumbers(array $phoneNumbers, int $segmentId): bool
    {
        if (empty($phoneNumbers)) {
            return false;
        }

        // Préparer les placeholders pour la requête IN
        $placeholders = implode(',', array_fill(0, count($phoneNumbers), '?'));

        // Mettre à jour les enregistrements d'historique SMS pour les numéros spécifiés
        $stmt = $this->db->prepare("UPDATE sms_history SET segment_id = ? WHERE phone_number IN ($placeholders) AND segment_id IS NULL");

        // Lier les paramètres
        $stmt->bindValue(1, $segmentId, PDO::PARAM_INT);
        $i = 2;
        foreach ($phoneNumbers as $phoneNumber) {
            $stmt->bindValue($i++, $phoneNumber, PDO::PARAM_STR);
        }

        return $stmt->execute();
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

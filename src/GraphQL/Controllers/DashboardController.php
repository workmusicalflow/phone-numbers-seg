<?php

namespace App\GraphQL\Controllers;

use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Type;
// Utiliser les classes concrètes et l'interface spécifique
use App\Repositories\UserRepository;
use App\Repositories\PhoneNumberRepository;
use App\Repositories\SenderNameRepository;
use App\Repositories\SMSOrderRepository;
use App\Repositories\Interfaces\SMSHistoryRepositoryInterface;
use App\Models\User;
use App\Models\SenderName;
use App\Models\SMSOrder;
use App\Models\SMSHistory;

/**
 * Contrôleur GraphQL pour le tableau de bord administrateur
 *
 * @Type
 */
class DashboardController
{
    // Utiliser les types concrets/spécifiques pour les propriétés
    private UserRepository $userRepository;
    private PhoneNumberRepository $phoneNumberRepository;
    private SMSHistoryRepositoryInterface $smsHistoryRepository;
    private SenderNameRepository $senderNameRepository;
    private SMSOrderRepository $smsOrderRepository;

    public function __construct(
        // Utiliser les types concrets/spécifiques dans le constructeur
        UserRepository $userRepository,
        PhoneNumberRepository $phoneNumberRepository,
        SMSHistoryRepositoryInterface $smsHistoryRepository,
        SenderNameRepository $senderNameRepository,
        SMSOrderRepository $smsOrderRepository
    ) {
        $this->userRepository = $userRepository;
        $this->phoneNumberRepository = $phoneNumberRepository;
        $this->smsHistoryRepository = $smsHistoryRepository;
        $this->senderNameRepository = $senderNameRepository;
        $this->smsOrderRepository = $smsOrderRepository;
    }

    /**
     * Récupère les statistiques générales pour le tableau de bord
     *
     * @Query
     * @return array{usersCount: int, totalSmsCredits: int, lastUpdated: string}
     */
    #[Query]
    public function dashboardStats(): array
    {
        $totalUsers = $this->userRepository->countAll();
        // Note: totalSMSSent n'est pas retourné dans le type DashboardStats du schéma actuel
        // $totalSMSSent = $this->smsHistoryRepository->countAll();

        // Calculer le total des crédits SMS disponibles pour tous les utilisateurs
        // Optimisation: Utiliser une requête SUM() dans le repository si possible
        $users = $this->userRepository->findAll();
        $totalCredits = 0;
        foreach ($users as $user) {
            // Assurez-vous que le modèle User a une méthode getSmsCredit() ou que la propriété est publique
            if (method_exists($user, 'getSmsCredit')) {
                $totalCredits += $user->getSmsCredit();
            } elseif (property_exists($user, 'smsCredit')) {
                $totalCredits += $user->smsCredit; // Accès direct si public
            }
        }

        return [
            'usersCount' => $totalUsers,
            'totalSmsCredits' => $totalCredits,
            'lastUpdated' => (new \DateTime())->format('c')
        ];
    }

    /**
     * Récupère l'activité récente pour le tableau de bord
     *
     * @Query
     * @return array<int, array{type: string, description: string, date: string}>
     */
    #[Query]
    public function recentActivity(): array
    {
        $activities = [];

        // Récupérer les 5 derniers utilisateurs créés
        $recentUsers = $this->userRepository->findBy([], ['createdAt' => 'DESC'], 5);
        foreach ($recentUsers as $user) {
            $activities[] = [
                'type' => 'user',
                'description' => sprintf('Nouvel utilisateur créé: %s', $user->getUsername()), // Utiliser getter
                'date' => $user->getCreatedAt() ? $user->getCreatedAt()->format('c') : '' // Utiliser getter et vérifier null
            ];
        }

        // Récupérer les 5 derniers SMS envoyés
        // Optimisation: Récupérer les utilisateurs associés en une seule requête
        $recentSMS = $this->smsHistoryRepository->findBy([], ['createdAt' => 'DESC'], 5);
        foreach ($recentSMS as $sms) {
            // Utiliser findById maintenant que userRepository est correctement typé
            $user = $this->userRepository->findById($sms->getUserId()); // Utiliser getter
            $username = $user ? $user->getUsername() : 'Utilisateur inconnu'; // Utiliser getter
            $activities[] = [
                'type' => 'sms',
                'description' => sprintf('%d SMS envoyés par %s', $sms->getMessageCount(), $username), // Utiliser getter
                'date' => $sms->getCreatedAt() ? $sms->getCreatedAt()->format('c') : '' // Utiliser getter et vérifier null
            ];
        }

        // Récupérer les 5 dernières commandes de crédits
        // Optimisation: Récupérer les utilisateurs associés en une seule requête
        $recentOrders = $this->smsOrderRepository->findBy([], ['createdAt' => 'DESC'], 5);
        foreach ($recentOrders as $order) {
            // Utiliser findById maintenant que userRepository est correctement typé
            $user = $this->userRepository->findById($order->getUserId()); // Utiliser getter
            $username = $user ? $user->getUsername() : 'Utilisateur inconnu'; // Utiliser getter
            $activities[] = [
                'type' => 'order',
                'description' => sprintf('Commande de %d crédits par %s', $order->getQuantity(), $username), // Utiliser getter
                'date' => $order->getCreatedAt() ? $order->getCreatedAt()->format('c') : '' // Utiliser getter et vérifier null
            ];
        }

        // Récupérer les 5 derniers noms d'expéditeur approuvés
        $recentSenderNames = $this->senderNameRepository->findBy(['status' => 'approved'], ['updatedAt' => 'DESC'], 5);
        foreach ($recentSenderNames as $senderName) {
            $activities[] = [
                'type' => 'senderName',
                'description' => sprintf('Nom d\'expéditeur "%s" approuvé', $senderName->getName()), // Utiliser getter
                'date' => $senderName->getUpdatedAt() ? $senderName->getUpdatedAt()->format('c') : '' // Utiliser getter et vérifier null
            ];
        }

        // Trier toutes les activités par date (la plus récente en premier)
        usort($activities, function ($a, $b) {
            // Gérer les dates potentiellement nulles ou invalides
            $timeA = $a['date'] ? strtotime($a['date']) : 0;
            $timeB = $b['date'] ? strtotime($b['date']) : 0;
            return $timeB - $timeA;
        });

        // Limiter à 10 activités
        return array_slice($activities, 0, 10);
    }

    /**
     * Récupère les demandes de nom d'expéditeur en attente
     *
     * @Query
     * @return array<int, array{id: int, name: string, username: string}>
     */
    #[Query]
    public function pendingSenderNames(): array
    {
        // Optimisation: Récupérer les utilisateurs associés en une seule requête
        $pendingSenderNames = $this->senderNameRepository->findBy(['status' => 'pending']);
        $result = [];

        // Pré-charger les utilisateurs si possible
        $userIds = array_map(fn($sn) => $sn->getUserId(), $pendingSenderNames);
        $users = $this->userRepository->findByIds($userIds); // Supposant que findByIds existe
        $usersById = [];
        foreach ($users as $user) {
            $usersById[$user->getId()] = $user;
        }


        foreach ($pendingSenderNames as $senderName) {
            // Utiliser les utilisateurs pré-chargés
            $user = $usersById[$senderName->getUserId()] ?? null;
            $username = $user ? $user->getUsername() : 'Utilisateur inconnu'; // Utiliser getter

            $result[] = [
                'id' => $senderName->getId(), // Utiliser getter
                'name' => $senderName->getName(), // Utiliser getter
                'username' => $username
            ];
        }

        return $result;
    }

    /**
     * Récupère les commandes de crédits en attente
     *
     * @Query
     * @return array<int, array{id: int, quantity: int, username: string}>
     */
    #[Query]
    public function pendingOrders(): array
    {
        // Optimisation: Récupérer les utilisateurs associés en une seule requête
        $pendingOrders = $this->smsOrderRepository->findBy(['status' => 'pending']);
        $result = [];

        // Pré-charger les utilisateurs si possible
        $userIds = array_map(fn($o) => $o->getUserId(), $pendingOrders);
        $users = $this->userRepository->findByIds($userIds); // Supposant que findByIds existe
        $usersById = [];
        foreach ($users as $user) {
            $usersById[$user->getId()] = $user;
        }

        foreach ($pendingOrders as $order) {
            // Utiliser les utilisateurs pré-chargés
            $user = $usersById[$order->getUserId()] ?? null;
            $username = $user ? $user->getUsername() : 'Utilisateur inconnu'; // Utiliser getter

            $result[] = [
                'id' => $order->getId(), // Utiliser getter
                'quantity' => $order->getQuantity(), // Utiliser getter
                'username' => $username
            ];
        }

        return $result;
    }

    /**
     * Récupère les données pour le graphique d'envoi de SMS (30 derniers jours)
     *
     * @Query
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    #[Query]
    public function smsChartData(): array
    {
        $labels = [];
        $data = [];
        $endDate = new \DateTime();
        $startDate = (new \DateTime())->modify('-29 days'); // 30 jours incluant aujourd'hui

        // Optimisation: Récupérer les comptes par jour en une seule requête
        $dailyCounts = $this->smsHistoryRepository->getDailyCountsForDateRange($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        $countsByDate = [];
        foreach ($dailyCounts as $row) {
            $countsByDate[$row['date']] = (int)$row['count'];
        }


        // Générer les dates pour les 30 derniers jours
        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $dateFormatted = $currentDate->format('Y-m-d');
            $labels[] = $currentDate->format('d/m');
            $data[] = $countsByDate[$dateFormatted] ?? 0; // Utiliser le compte pré-calculé ou 0
            $currentDate->modify('+1 day');
        }


        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
}

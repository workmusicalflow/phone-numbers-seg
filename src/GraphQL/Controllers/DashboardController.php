<?php

namespace App\GraphQL\Controllers;

use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Type;
use App\Repositories\Interfaces\DashboardRepositoryInterface;
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
    private DashboardRepositoryInterface $userRepository;
    private DashboardRepositoryInterface $phoneNumberRepository;
    private SMSHistoryRepositoryInterface $smsHistoryRepository;
    private DashboardRepositoryInterface $senderNameRepository;
    private DashboardRepositoryInterface $smsOrderRepository;

    public function __construct(
        DashboardRepositoryInterface $userRepository,
        DashboardRepositoryInterface $phoneNumberRepository,
        SMSHistoryRepositoryInterface $smsHistoryRepository,
        DashboardRepositoryInterface $senderNameRepository,
        DashboardRepositoryInterface $smsOrderRepository
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
     * @return array
     */
    public function dashboardStats(): array
    {
        $totalUsers = $this->userRepository->count();
        $totalPhoneNumbers = $this->phoneNumberRepository->count();
        $totalSMSSent = $this->smsHistoryRepository->count();

        // Calculer le total des crédits SMS disponibles pour tous les utilisateurs
        $users = $this->userRepository->findAll();
        $totalCredits = 0;
        foreach ($users as $user) {
            $totalCredits += $user->credits;
        }

        return [
            'totalUsers' => $totalUsers,
            'totalPhoneNumbers' => $totalPhoneNumbers,
            'totalSMSSent' => $totalSMSSent,
            'totalCredits' => $totalCredits
        ];
    }

    /**
     * Récupère l'activité récente pour le tableau de bord
     * 
     * @Query
     * @return array
     */
    public function recentActivity(): array
    {
        $activities = [];

        // Récupérer les 5 derniers utilisateurs créés
        $recentUsers = $this->userRepository->findBy([], ['createdAt' => 'DESC'], 5);
        foreach ($recentUsers as $user) {
            $activities[] = [
                'type' => 'user',
                'description' => sprintf('Nouvel utilisateur créé: %s', $user->username),
                'date' => $user->createdAt->format('c')
            ];
        }

        // Récupérer les 5 derniers SMS envoyés
        $recentSMS = $this->smsHistoryRepository->findBy([], ['createdAt' => 'DESC'], 5);
        foreach ($recentSMS as $sms) {
            $user = $this->userRepository->find($sms->userId);
            $username = $user ? $user->username : 'Utilisateur inconnu';
            $activities[] = [
                'type' => 'sms',
                'description' => sprintf('%d SMS envoyés par %s', $sms->messageCount, $username),
                'date' => $sms->createdAt->format('c')
            ];
        }

        // Récupérer les 5 dernières commandes de crédits
        $recentOrders = $this->smsOrderRepository->findBy([], ['createdAt' => 'DESC'], 5);
        foreach ($recentOrders as $order) {
            $user = $this->userRepository->find($order->userId);
            $username = $user ? $user->username : 'Utilisateur inconnu';
            $activities[] = [
                'type' => 'order',
                'description' => sprintf('Commande de %d crédits par %s', $order->quantity, $username),
                'date' => $order->createdAt->format('c')
            ];
        }

        // Récupérer les 5 derniers noms d'expéditeur approuvés
        $recentSenderNames = $this->senderNameRepository->findBy(['status' => 'approved'], ['updatedAt' => 'DESC'], 5);
        foreach ($recentSenderNames as $senderName) {
            $activities[] = [
                'type' => 'senderName',
                'description' => sprintf('Nom d\'expéditeur "%s" approuvé', $senderName->name),
                'date' => $senderName->updatedAt->format('c')
            ];
        }

        // Trier toutes les activités par date (la plus récente en premier)
        usort($activities, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        // Limiter à 10 activités
        return array_slice($activities, 0, 10);
    }

    /**
     * Récupère les demandes de nom d'expéditeur en attente
     * 
     * @Query
     * @return array
     */
    public function pendingSenderNames(): array
    {
        $pendingSenderNames = $this->senderNameRepository->findBy(['status' => 'pending']);
        $result = [];

        foreach ($pendingSenderNames as $senderName) {
            $user = $this->userRepository->find($senderName->userId);
            $username = $user ? $user->username : 'Utilisateur inconnu';

            $result[] = [
                'id' => $senderName->id,
                'name' => $senderName->name,
                'username' => $username
            ];
        }

        return $result;
    }

    /**
     * Récupère les commandes de crédits en attente
     * 
     * @Query
     * @return array
     */
    public function pendingOrders(): array
    {
        $pendingOrders = $this->smsOrderRepository->findBy(['status' => 'pending']);
        $result = [];

        foreach ($pendingOrders as $order) {
            $user = $this->userRepository->find($order->userId);
            $username = $user ? $user->username : 'Utilisateur inconnu';

            $result[] = [
                'id' => $order->id,
                'quantity' => $order->quantity,
                'username' => $username
            ];
        }

        return $result;
    }

    /**
     * Récupère les données pour le graphique d'envoi de SMS (30 derniers jours)
     * 
     * @Query
     * @return array
     */
    public function smsChartData(): array
    {
        $labels = [];
        $data = [];

        // Générer les dates pour les 30 derniers jours
        $today = new \DateTime();
        for ($i = 29; $i >= 0; $i--) {
            $date = clone $today;
            $date->modify("-$i days");
            $labels[] = $date->format('d/m');

            // Format de date pour la comparaison dans la base de données
            $dateFormatted = $date->format('Y-m-d');

            // Compter le nombre de SMS envoyés pour cette date
            $count = $this->smsHistoryRepository->countByDate($dateFormatted);
            $data[] = $count;
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
}

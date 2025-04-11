<?php

/**
 * Script de conversion des numéros de téléphone en contacts
 * 
 * Ce script convertit les numéros de téléphone existants dans la table phone_numbers
 * en contacts dans la table contacts, associés à un utilisateur spécifique.
 * 
 * Usage: php scripts/utils/convert_phone_numbers_to_contacts.php [--user-id=2] [--dry-run] [--limit=100] [--offset=0]
 */

// Initialisation
require_once __DIR__ . '/../../vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Charger les définitions du conteneur DI
$definitions = require __DIR__ . '/../../src/config/di.php';

// Construire le conteneur DI
$containerBuilder = new DI\ContainerBuilder();
$containerBuilder->addDefinitions($definitions);
$container = $containerBuilder->build();

// Récupérer les repositories nécessaires
$phoneNumberRepository = $container->get(\App\Repositories\PhoneNumberRepository::class);
$contactRepository = $container->get(\App\Repositories\ContactRepository::class);

// Analyser les arguments de ligne de commande
$options = getopt('', ['user-id::', 'dry-run', 'limit::', 'offset::']);
$userId = isset($options['user-id']) ? (int)$options['user-id'] : 2; // Par défaut: AfricaQSHE (ID 2)
$dryRun = isset($options['dry-run']);
$limit = isset($options['limit']) ? (int)$options['limit'] : null;
$offset = isset($options['offset']) ? (int)$options['offset'] : 0;

// Statistiques
$stats = [
    'total' => 0,
    'created' => 0,
    'duplicates' => 0,
    'errors' => 0
];
$errors = [];

// Fonction pour générer un nom à partir d'un numéro de téléphone
function generateNameFromNumber($number)
{
    // Extraire les derniers chiffres du numéro pour créer un identifiant unique
    $lastDigits = substr($number, -6);
    return "Contact " . $lastDigits;
}

// Afficher l'en-tête
echo "=================================================================\n";
echo "CONVERSION DES NUMÉROS DE TÉLÉPHONE EN CONTACTS\n";
echo "=================================================================\n";
echo "Mode: " . ($dryRun ? "Simulation (dry-run)" : "Exécution réelle") . "\n";
echo "Utilisateur cible: ID $userId\n";
if ($limit) {
    echo "Limite: $limit numéros (à partir de l'offset $offset)\n";
}
echo "-----------------------------------------------------------------\n\n";

try {
    // Récupérer les numéros de téléphone
    $phoneNumbers = $limit
        ? $phoneNumberRepository->findAll($limit, $offset)
        : $phoneNumberRepository->findAll();

    $totalPhoneNumbers = count($phoneNumbers);
    $stats['total'] = $totalPhoneNumbers;

    echo "Nombre total de numéros à traiter: $totalPhoneNumbers\n\n";

    if ($totalPhoneNumbers === 0) {
        echo "Aucun numéro de téléphone trouvé. Fin du traitement.\n";
        exit(0);
    }

    // Démarrer une transaction si ce n'est pas un dry run
    if (!$dryRun) {
        $contactRepository->beginTransaction();
    }

    // Traiter chaque numéro de téléphone
    foreach ($phoneNumbers as $index => $phoneNumber) {
        $number = $phoneNumber->getNumber();
        $progress = ($index + 1) . '/' . $totalPhoneNumbers;

        echo "[$progress] Traitement du numéro: $number... ";

        try {
            // Vérifier si un contact avec ce numéro existe déjà pour cet utilisateur
            $existingContacts = $contactRepository->findBy([
                'user_id' => $userId,
                'phone_number' => $number
            ]);

            if (!empty($existingContacts)) {
                echo "IGNORÉ (déjà existant)\n";
                $stats['duplicates']++;
                continue;
            }

            // Préparer les données du contact
            $firstName = $phoneNumber->getFirstName() ?: '';
            $lastName = $phoneNumber->getName() ?: '';

            // Si ni prénom ni nom n'est disponible, générer un nom à partir du numéro
            $name = trim("$firstName $lastName");
            if (empty($name)) {
                $name = generateNameFromNumber($number);
            }

            // Créer le contact
            $contact = new App\Models\Contact(
                0, // ID sera généré
                $userId,
                $name,
                $number,
                null, // email
                $phoneNumber->getNotes()
            );

            if (!$dryRun) {
                $contactRepository->create($contact);
            }

            echo "CRÉÉ avec le nom '$name'\n";
            $stats['created']++;
        } catch (Exception $e) {
            echo "ERREUR: " . $e->getMessage() . "\n";
            $stats['errors']++;
            $errors[] = [
                'number' => $number,
                'message' => $e->getMessage()
            ];
        }
    }

    // Valider la transaction si ce n'est pas un dry run
    if (!$dryRun) {
        $contactRepository->commit();
    }

    // Afficher le résumé
    echo "\n=================================================================\n";
    echo "RÉSUMÉ DE LA CONVERSION\n";
    echo "=================================================================\n";
    echo "Total traité: " . $stats['total'] . "\n";
    echo "Contacts créés: " . $stats['created'] . "\n";
    echo "Doublons ignorés: " . $stats['duplicates'] . "\n";
    echo "Erreurs: " . $stats['errors'] . "\n";

    // Afficher les erreurs détaillées si nécessaire
    if ($stats['errors'] > 0) {
        echo "\nDétail des erreurs:\n";
        foreach ($errors as $index => $error) {
            echo ($index + 1) . ". Numéro: " . $error['number'] . " - " . $error['message'] . "\n";
        }
    }

    echo "\n" . ($dryRun ? "SIMULATION TERMINÉE" : "CONVERSION TERMINÉE") . "\n";

    if ($dryRun) {
        echo "\nPour exécuter la conversion réelle, relancez sans l'option --dry-run\n";
    }
} catch (Exception $e) {
    // En cas d'erreur globale, annuler la transaction
    if (!$dryRun) {
        $contactRepository->rollback();
    }

    echo "\nERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "La conversion a été annulée.\n";
    exit(1);
}

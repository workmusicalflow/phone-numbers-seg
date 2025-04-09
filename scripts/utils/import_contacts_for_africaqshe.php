<?php

/**
 * Script pour importer les contacts du fichier CSV et les associer à l'utilisateur AfricaQSHE
 */

// Définir le chemin racine de l'application
define('APP_ROOT', dirname(dirname(__DIR__)));

// Charger l'autoloader de Composer
require APP_ROOT . '/vendor/autoload.php';

// Connexion à la base de données
try {
    $dbFile = APP_ROOT . '/src/database/database.sqlite';
    if (!file_exists($dbFile)) {
        throw new Exception('Fichier de base de données introuvable.');
    }

    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connexion à la base de données réussie.\n";
} catch (Exception $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage() . "\n");
}

// Initialiser le repository de contacts
$contactRepository = new App\Repositories\ContactRepository($pdo);

// ID de l'utilisateur AfricaQSHE
$userId = 2;

// Vérifier si l'utilisateur existe
try {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("L'utilisateur avec l'ID $userId n'existe pas.\n");
    }

    echo "Utilisateur trouvé: " . $user['username'] . " (ID: " . $user['id'] . ")\n";
} catch (Exception $e) {
    die("Erreur lors de la vérification de l'utilisateur: " . $e->getMessage() . "\n");
}

// Chemin vers le fichier CSV
$csvFile = APP_ROOT . '/Copie de contacts.csv';

if (!file_exists($csvFile)) {
    die("Le fichier CSV n'existe pas: $csvFile\n");
}

// Lire le fichier CSV
try {
    $handle = fopen($csvFile, 'r');
    if (!$handle) {
        throw new Exception("Impossible d'ouvrir le fichier CSV.");
    }

    // Lire l'en-tête
    $header = fgetcsv($handle);
    if (!$header) {
        throw new Exception("Impossible de lire l'en-tête du fichier CSV.");
    }

    // Trouver l'index des colonnes
    $firstNameIndex = array_search('First Name', $header);
    $lastNameIndex = array_search('Last Name', $header);
    $organizationIndex = array_search('Organization', $header);
    $numberIndex = array_search('number', $header);

    if ($numberIndex === false) {
        throw new Exception("La colonne 'number' est introuvable dans le fichier CSV.");
    }

    echo "En-tête du fichier CSV: " . implode(', ', $header) . "\n";

    // Préparer les contacts à importer
    $contacts = [];
    $line = 2; // Commencer à la ligne 2 (après l'en-tête)

    while (($data = fgetcsv($handle)) !== false) {
        // Vérifier si le numéro de téléphone est présent
        if (!isset($data[$numberIndex]) || empty($data[$numberIndex])) {
            echo "Ligne $line: Numéro de téléphone manquant, ignoré.\n";
            $line++;
            continue;
        }

        // Nettoyer le numéro de téléphone (supprimer les espaces)
        $phoneNumber = str_replace(' ', '', $data[$numberIndex]);

        // Construire le nom à partir des données disponibles
        $name = '';
        if ($firstNameIndex !== false && isset($data[$firstNameIndex]) && !empty($data[$firstNameIndex])) {
            $name .= $data[$firstNameIndex] . ' ';
        }
        if ($lastNameIndex !== false && isset($data[$lastNameIndex]) && !empty($data[$lastNameIndex])) {
            $name .= $data[$lastNameIndex];
        }
        $name = trim($name);

        // Si le nom est vide, utiliser l'organisation ou le numéro de téléphone comme nom
        if (empty($name)) {
            if ($organizationIndex !== false && isset($data[$organizationIndex]) && !empty($data[$organizationIndex])) {
                $name = $data[$organizationIndex];
            } else {
                $name = $phoneNumber; // Utiliser le numéro comme nom par défaut
            }
        }

        // Préparer les notes (utiliser l'organisation si disponible)
        $notes = '';
        if ($organizationIndex !== false && isset($data[$organizationIndex]) && !empty($data[$organizationIndex])) {
            $notes = "Organisation: " . $data[$organizationIndex];
        }

        // Ajouter le contact à la liste
        $contacts[] = [
            'name' => $name,
            'phoneNumber' => $phoneNumber,
            'email' => null,
            'notes' => $notes
        ];

        echo "Ligne $line: Contact préparé - Nom: $name, Numéro: $phoneNumber\n";
        $line++;
    }

    fclose($handle);

    // Vérifier si des contacts ont été trouvés
    if (empty($contacts)) {
        throw new Exception("Aucun contact valide trouvé dans le fichier CSV.");
    }

    echo "Nombre total de contacts à importer: " . count($contacts) . "\n";

    // Importer les contacts
    try {
        $importedContacts = $contactRepository->bulkCreate($contacts, $userId);
        echo "Importation réussie! " . count($importedContacts) . " contacts ont été importés pour l'utilisateur AfricaQSHE.\n";

        // Afficher les détails des contacts importés
        foreach ($importedContacts as $index => $contact) {
            echo ($index + 1) . ". ID: " . $contact->getId() . ", Nom: " . $contact->getName() . ", Numéro: " . $contact->getPhoneNumber() . "\n";
        }
    } catch (Exception $e) {
        throw new Exception("Erreur lors de l'importation des contacts: " . $e->getMessage());
    }
} catch (Exception $e) {
    die("Erreur: " . $e->getMessage() . "\n");
}

echo "Terminé.\n";

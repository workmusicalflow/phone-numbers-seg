<?php

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Obtenir l'EntityManager
$entityManager = require __DIR__ . '/../src/bootstrap-doctrine.php';

// Fonction pour afficher les résultats de test
function testResult($testName, $success)
{
    echo str_pad($testName, 50, '.') . ($success ? " [RÉUSSI]" : " [ÉCHEC]") . PHP_EOL;
    return $success;
}

// Fonction pour afficher un séparateur
function printSeparator()
{
    echo str_repeat('-', 70) . PHP_EOL;
}

// Initialiser les compteurs
$totalTests = 0;
$passedTests = 0;

echo PHP_EOL;
echo "=== TESTS DES OPÉRATIONS ESSENTIELLES AVEC DOCTRINE ORM ===" . PHP_EOL;
echo "Date d'exécution: " . date('Y-m-d H:i:s') . PHP_EOL;
printSeparator();

// Définir les utilisateurs de test
$users = [
    [
        'id' => 1,
        'username' => 'Admin',
        'password' => 'oraclesms2025-0',
        'description' => 'Administrateur'
    ],
    [
        'id' => 2,
        'username' => 'AfricaQSHE',
        'password' => 'Qualitas@2024',
        'description' => 'Utilisateur standard'
    ]
];

// Définir les numéros de téléphone à utiliser pour les tests
$phoneNumbers = [
    '0777104936',
    '002250141399354',
    '+2250554272605'
];

// Exécuter les tests pour chaque utilisateur
foreach ($users as $user) {
    $userId = $user['id'];
    $username = $user['username'];

    echo PHP_EOL . "=== TESTS POUR L'UTILISATEUR: {$username} (ID: {$userId}) ===" . PHP_EOL;
    printSeparator();

    // ====================================================================
    // 1. TESTS HISTORIQUE SMS
    // ====================================================================
    echo PHP_EOL . "--- TESTS HISTORIQUE SMS ---" . PHP_EOL;

    try {
        $smsHistoryRepository = new App\Repositories\Doctrine\SMSHistoryRepository($entityManager);

        // Compter les entrées existantes
        $countBefore = $smsHistoryRepository->countByUserId($userId);
        echo "Nombre d'entrées d'historique SMS pour $username: $countBefore" . PHP_EOL;

        // Test de suppression de l'historique SMS
        $result = $smsHistoryRepository->removeAllByUserId($userId);
        $totalTests++;
        $passedTests += testResult("Suppression de l'historique SMS pour $username", $result);

        // Vérifier que l'historique est vide
        $countAfter = $smsHistoryRepository->countByUserId($userId);
        echo "Nombre d'entrées après suppression: $countAfter" . PHP_EOL;
        $totalTests++;
        $passedTests += testResult("Vérification de la suppression de l'historique pour $username", $countAfter === 0);
    } catch (Exception $e) {
        echo "ERREUR lors des tests d'historique SMS pour $username: " . $e->getMessage() . PHP_EOL;
    }

    printSeparator();

    // ====================================================================
    // 2. TESTS CONTACTS
    // ====================================================================
    echo PHP_EOL . "--- TESTS CONTACTS POUR $username ---" . PHP_EOL;

    try {
        $contactRepository = new App\Repositories\Doctrine\ContactRepository($entityManager);
        $contactIds = [];

        // Test d'ajout de contacts avec les numéros spécifiés
        foreach ($phoneNumbers as $index => $phoneNumber) {
            $contact = new App\Entities\Contact();
            $contact->setUserId($userId);
            $contact->setName("Contact Test $username " . ($index + 1));
            $contact->setPhoneNumber($phoneNumber);
            $contact->setEmail("test.$username." . ($index + 1) . "@example.com");
            $contact->setNotes("Contact créé pour test Doctrine ORM - Utilisateur $username");

            $savedContact = $contactRepository->save($contact);
            $contactId = $savedContact->getId();
            $contactIds[] = $contactId;

            $totalTests++;
            $passedTests += testResult("Création du contact avec numéro $phoneNumber pour $username", $contactId > 0);

            // Vérifier que le contact existe
            $foundContact = $contactRepository->findById($contactId);
            $totalTests++;
            $passedTests += testResult("Vérification de l'existence du contact $contactId pour $username", $foundContact !== null);
        }

        // Test de modification d'un contact
        if (!empty($contactIds)) {
            $contactToUpdate = $contactRepository->findById($contactIds[0]);
            $originalName = $contactToUpdate->getName();
            $newName = "Contact Modifié $username";

            $contactToUpdate->setName($newName);
            $contactToUpdate->setEmail("modifie.$username@example.com");
            $updatedContact = $contactRepository->save($contactToUpdate);

            // Vérifier les modifications
            $refreshedContact = $contactRepository->findById($contactIds[0]);
            $totalTests++;
            $passedTests += testResult(
                "Modification du nom du contact de '$originalName' à '$newName'",
                $refreshedContact->getName() === $newName
            );

            $totalTests++;
            $passedTests += testResult(
                "Modification de l'email du contact pour $username",
                $refreshedContact->getEmail() === "modifie.$username@example.com"
            );
        }

        // ====================================================================
        // 3. TESTS GROUPES DE CONTACTS
        // ====================================================================
        echo PHP_EOL . "--- TESTS GROUPES DE CONTACTS POUR $username ---" . PHP_EOL;

        $contactGroupRepository = new App\Repositories\Doctrine\ContactGroupRepository($entityManager);

        // Test de création de groupe
        $group = new App\Entities\ContactGroup();
        $group->setUserId($userId);
        $group->setName("Groupe Test $username");
        $group->setDescription("Groupe créé pour test Doctrine ORM - Utilisateur $username");
        $savedGroup = $contactGroupRepository->save($group);
        $groupId = $savedGroup->getId();

        $totalTests++;
        $passedTests += testResult("Création d'un groupe de contacts pour $username", $groupId > 0);

        // Test de modification de groupe
        $groupToUpdate = $contactGroupRepository->findById($groupId);
        $originalName = $groupToUpdate->getName();
        $newName = "Groupe Modifié $username";

        $groupToUpdate->setName($newName);
        $groupToUpdate->setDescription("Description modifiée pour test - $username");
        $updatedGroup = $contactGroupRepository->save($groupToUpdate);

        // Vérifier les modifications
        $refreshedGroup = $contactGroupRepository->findById($groupId);
        $totalTests++;
        $passedTests += testResult(
            "Modification du nom du groupe de '$originalName' à '$newName'",
            $refreshedGroup->getName() === $newName
        );

        $totalTests++;
        $passedTests += testResult(
            "Modification de la description du groupe pour $username",
            $refreshedGroup->getDescription() === "Description modifiée pour test - $username"
        );

        // Test d'ajout de contacts au groupe
        $successCount = 0;
        foreach ($contactIds as $contactId) {
            $result = $contactGroupRepository->addContactToGroup($contactId, $groupId);
            if ($result) {
                $successCount++;
            }
        }

        $totalTests++;
        $passedTests += testResult(
            "Ajout de " . count($contactIds) . " contacts au groupe pour $username",
            $successCount === count($contactIds)
        );

        // Vérifier que les contacts sont dans le groupe
        $contactsInGroup = $contactGroupRepository->getContactsInGroup($groupId);
        $foundCount = 0;
        foreach ($contactsInGroup as $contact) {
            if (in_array($contact->getId(), $contactIds)) {
                $foundCount++;
            }
        }

        $totalTests++;
        $passedTests += testResult(
            "Vérification de l'appartenance des contacts au groupe pour $username",
            $foundCount === count($contactIds)
        );

        // Test de suppression d'un contact du groupe
        if (!empty($contactIds)) {
            $contactIdToRemove = $contactIds[0];
            $result = $contactGroupRepository->removeContactFromGroup($contactIdToRemove, $groupId);

            $totalTests++;
            $passedTests += testResult(
                "Suppression d'un contact du groupe pour $username",
                $result
            );

            // Vérifier que le contact n'est plus dans le groupe
            $contactsInGroup = $contactGroupRepository->getContactsInGroup($groupId);
            $found = false;
            foreach ($contactsInGroup as $contact) {
                if ($contact->getId() == $contactIdToRemove) {
                    $found = true;
                    break;
                }
            }

            $totalTests++;
            $passedTests += testResult(
                "Vérification de la non-appartenance du contact au groupe pour $username",
                !$found
            );
        }

        // Test de suppression du groupe
        $result = $contactGroupRepository->deleteById($groupId);
        $totalTests++;
        $passedTests += testResult("Suppression du groupe pour $username", $result);

        // Vérifier que le groupe n'existe plus
        $deletedGroup = $contactGroupRepository->findById($groupId);
        $totalTests++;
        $passedTests += testResult(
            "Vérification de la suppression du groupe pour $username",
            $deletedGroup === null
        );

        // ====================================================================
        // 4. NETTOYAGE - SUPPRESSION DES CONTACTS DE TEST
        // ====================================================================
        echo PHP_EOL . "--- NETTOYAGE DES DONNÉES DE TEST POUR $username ---" . PHP_EOL;

        // Supprimer les contacts créés pour le test
        $successCount = 0;
        foreach ($contactIds as $contactId) {
            $result = $contactRepository->deleteById($contactId);
            if ($result) {
                $successCount++;
            }
        }

        $totalTests++;
        $passedTests += testResult(
            "Suppression des " . count($contactIds) . " contacts de test pour $username",
            $successCount === count($contactIds)
        );

        // Vérifier que les contacts n'existent plus
        $foundCount = 0;
        foreach ($contactIds as $contactId) {
            $contact = $contactRepository->findById($contactId);
            if ($contact !== null) {
                $foundCount++;
            }
        }

        $totalTests++;
        $passedTests += testResult(
            "Vérification de la suppression des contacts pour $username",
            $foundCount === 0
        );
    } catch (Exception $e) {
        echo "ERREUR lors des tests pour $username: " . $e->getMessage() . PHP_EOL;
    }

    printSeparator();
}

printSeparator();

// ====================================================================
// RÉSUMÉ DES TESTS
// ====================================================================
echo PHP_EOL . "=== RÉSUMÉ DES TESTS ===" . PHP_EOL;
echo "Tests exécutés: $totalTests" . PHP_EOL;
echo "Tests réussis: $passedTests" . PHP_EOL;
$successRate = ($totalTests > 0) ? round(($passedTests / $totalTests) * 100) : 0;
echo "Taux de réussite: $successRate%" . PHP_EOL;

if ($passedTests === $totalTests) {
    echo PHP_EOL . "✅ TOUS LES TESTS ONT RÉUSSI - L'implémentation Doctrine ORM est prête pour la production!" . PHP_EOL;
} else {
    echo PHP_EOL . "⚠️ CERTAINS TESTS ONT ÉCHOUÉ - Veuillez corriger les problèmes avant le déploiement." . PHP_EOL;
}

echo PHP_EOL;

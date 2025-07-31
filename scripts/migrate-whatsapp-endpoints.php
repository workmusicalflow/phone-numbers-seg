#!/usr/bin/env php
<?php
/**
 * Script de migration des endpoints WhatsApp
 * 
 * Ce script aide à la migration et consolidation des endpoints WhatsApp
 * pour éviter la confusion entre les différentes versions.
 */

// Couleurs pour l'output
$colors = [
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'reset' => "\033[0m"
];

// Fonction d'aide pour l'affichage
function println($message, $color = null) {
    global $colors;
    if ($color && isset($colors[$color])) {
        echo $colors[$color] . $message . $colors['reset'] . PHP_EOL;
    } else {
        echo $message . PHP_EOL;
    }
}

// Vérifier qu'on est dans le bon répertoire
$projectRoot = dirname(__DIR__);
if (!file_exists($projectRoot . '/public/api/whatsapp')) {
    println("Erreur: Ce script doit être exécuté depuis le répertoire racine du projet.", 'red');
    exit(1);
}

println("=== Migration des endpoints WhatsApp ===", 'green');
println("");

// Étape 1: Vérifier l'état actuel
println("1. Vérification de l'état actuel...", 'yellow');

$endpoints = [
    'send-template.php' => 'Ancienne version (obsolète)',
    'send-template-v2.php' => 'Version actuelle (production)',
    'send-template-simple.php' => 'Mock pour tests'
];

foreach ($endpoints as $file => $description) {
    $path = $projectRoot . '/public/api/whatsapp/' . $file;
    if (file_exists($path)) {
        println("  ✓ $file : $description", 'green');
    } else {
        println("  ✗ $file : Non trouvé", 'red');
    }
}

// Demander confirmation
println("");
println("Cette migration va:", 'yellow');
println("  1. Archiver send-template.php dans /archive/whatsapp/api/");
println("  2. Déplacer send-template-simple.php dans /tests/mocks/whatsapp/");
println("  3. Créer une redirection de send-template.php vers send-template-v2.php");
println("");
echo "Voulez-vous continuer? (y/n) ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim($line) != 'y') {
    println("Migration annulée.", 'red');
    exit(0);
}
fclose($handle);

// Étape 2: Créer les répertoires nécessaires
println("");
println("2. Création des répertoires...", 'yellow');

$dirs = [
    $projectRoot . '/archive/whatsapp/api',
    $projectRoot . '/tests/mocks/whatsapp'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            println("  ✓ Créé: $dir", 'green');
        } else {
            println("  ✗ Impossible de créer: $dir", 'red');
            exit(1);
        }
    } else {
        println("  ✓ Existe déjà: $dir", 'green');
    }
}

// Étape 3: Archiver les fichiers
println("");
println("3. Archivage des anciens fichiers...", 'yellow');

// Archiver send-template.php
$source = $projectRoot . '/public/api/whatsapp/send-template.php';
$dest = $projectRoot . '/archive/whatsapp/api/send-template-v1.php';
if (file_exists($source)) {
    if (copy($source, $dest)) {
        println("  ✓ Archivé: send-template.php → archive/whatsapp/api/send-template-v1.php", 'green');
    } else {
        println("  ✗ Erreur lors de l'archivage de send-template.php", 'red');
    }
}

// Déplacer send-template-simple.php
$source = $projectRoot . '/public/api/whatsapp/send-template-simple.php';
$dest = $projectRoot . '/tests/mocks/whatsapp/send-template-mock.php';
if (file_exists($source)) {
    if (rename($source, $dest)) {
        println("  ✓ Déplacé: send-template-simple.php → tests/mocks/whatsapp/send-template-mock.php", 'green');
    } else {
        println("  ✗ Erreur lors du déplacement de send-template-simple.php", 'red');
    }
}

// Étape 4: Créer un fichier de redirection
println("");
println("4. Création du fichier de redirection...", 'yellow');

$redirectContent = '<?php
/**
 * Redirection vers la version actuelle de l\'endpoint
 * 
 * Ce fichier redirige les anciennes requêtes vers le nouvel endpoint
 * pour maintenir la compatibilité pendant la migration.
 * 
 * @deprecated Utiliser send-template-v2.php directement
 */

// Logger l\'utilisation de l\'ancien endpoint
error_log("[WhatsApp API] Utilisation de l\'ancien endpoint send-template.php détectée");

// Rediriger vers le nouvel endpoint
require __DIR__ . \'/send-template-v2.php\';
';

$redirectPath = $projectRoot . '/public/api/whatsapp/send-template.php';
if (file_put_contents($redirectPath, $redirectContent)) {
    println("  ✓ Fichier de redirection créé", 'green');
} else {
    println("  ✗ Erreur lors de la création du fichier de redirection", 'red');
}

// Étape 5: Créer un README pour la documentation
println("");
println("5. Création de la documentation...", 'yellow');

$readmeContent = '# WhatsApp API Endpoints

## Structure actuelle

- **send-template-v2.php** : Endpoint de production actuel
- **send-template.php** : Redirection vers send-template-v2.php (pour compatibilité)
- **upload.php** : Upload de médias WhatsApp
- **webhook.php** : Webhook pour les callbacks Meta
- **status.php** : Vérification du statut des messages

## Fichiers archivés

- **/archive/whatsapp/api/send-template-v1.php** : Ancienne version obsolète
- **/tests/mocks/whatsapp/send-template-mock.php** : Mock pour tests locaux

## Migration

Pour les nouvelles intégrations, utiliser directement `send-template-v2.php`.
L\'ancien endpoint `send-template.php` reste disponible pour compatibilité mais
redirige vers la v2.

Consulter `/docs/whatsapp-api-endpoints-clarification.md` pour plus de détails.
';

$readmePath = $projectRoot . '/public/api/whatsapp/README.md';
if (file_put_contents($readmePath, $readmeContent)) {
    println("  ✓ README.md créé", 'green');
} else {
    println("  ✗ Erreur lors de la création du README", 'red');
}

// Résumé
println("");
println("=== Migration terminée ===", 'green');
println("");
println("Actions effectuées:");
println("  ✓ Anciens fichiers archivés");
println("  ✓ Fichier de redirection créé");
println("  ✓ Documentation mise à jour");
println("");
println("Prochaines étapes:", 'yellow');
println("  1. Tester l'endpoint send-template-v2.php");
println("  2. Mettre à jour les références frontend si nécessaire");
println("  3. Après validation, renommer send-template-v2.php → send-template.php");
println("");
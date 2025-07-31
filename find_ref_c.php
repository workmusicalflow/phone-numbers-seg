#!/usr/bin/env php
<?php
// --- Couleurs ANSI ---
define('ANSI_RESET', "\033[0m");
define('ANSI_YELLOW', "\033[1;33m");
define('ANSI_CYAN', "\033[0;36m");
define('ANSI_RED', "\033[0;31m");
define('ANSI_GREEN', "\033[0;32m");
define('ANSI_GRAY', "\033[0;90m");
define('ANSI_BG_RED', "\033[41m");

// --- Gestion des arguments avec getopt() ---
$shortOptions = "hc:i:e:l:p:f:";
$longOptions = [
    "help",
    "context:",
    "include:",
    "exclude:",
    "limit:",
    "path:",
    "file-pattern:",
    "list-files" // Nouvelle option pour lister les fichiers sans recherche de contenu
];

$options = getopt($shortOptions, $longOptions);

// --- Récupération des options ---
$contextLines = isset($options['c']) ? (int)$options['c'] : (isset($options['context']) ? (int)$options['context'] : 0);
$fileLimit = isset($options['l']) ? (float)$options['l'] : (isset($options['limit']) ? (float)$options['limit'] : 10);
$startDirInput = isset($options['p']) ? $options['p'] : (isset($options['path']) ? $options['path'] : getcwd());
$filePattern = isset($options['f']) ? $options['f'] : (isset($options['file-pattern']) ? $options['file-pattern'] : null);
$listFilesOnly = isset($options['list-files']);

// Normaliser le chemin de départ
$startDir = realpath($startDirInput);
if ($startDir === false) {
    echo ANSI_RED . "Erreur: Le chemin de recherche spécifié '{$startDirInput}' est invalide." . ANSI_RESET . "\n";
    exit(1);
}

// --- Récupération du terme à rechercher (maintenant optionnel) ---
$searchTerm = null;
// Les arguments non-optionnels sont à la fin de $argv
$nonOptionArgs = [];
for ($i = 1; $i < $argc; $i++) {
    $isOption = false;
    $isOptionValue = false;
    // Vérifie si l'argument est une option courte ou longue
    if (strpos($argv[$i], '-') === 0) {
        $isOption = true;
        // Vérifie si l'option prend une valeur et si l'argument suivant est cette valeur
        $optName = ltrim($argv[$i], '-');
        if (isset($options[substr($optName, 0, 1)]) && strlen($optName) == 1 && is_string($options[substr($optName, 0, 1)])) { // option courte avec valeur
            if ($i + 1 < $argc && $options[substr($optName, 0, 1)] === $argv[$i + 1]) $isOptionValue = true;
        } elseif (in_array($optName . ":", $longOptions) || in_array(rtrim($optName, "=") . ":", $longOptions)) { // option longue avec valeur
            if ($i + 1 < $argc && isset($options[rtrim($optName, "=")]) && $options[rtrim($optName, "=")] === $argv[$i + 1]) $isOptionValue = true;
            elseif (strpos($argv[$i], "=") !== false) $isOptionValue = false; // la valeur est attachée
        }
    }
    // Vérifie si l'argument précédent était une option attendant une valeur
    if ($i > 1 && strpos($argv[$i - 1], '-') === 0) {
        $prevOptName = ltrim($argv[$i - 1], '-');
        if (isset($options[substr($prevOptName, 0, 1)]) && strlen($prevOptName) == 1 && is_string($options[substr($prevOptName, 0, 1)]) && $options[substr($prevOptName, 0, 1)] === $argv[$i]) {
            continue; // C'est la valeur d'une option courte
        } elseif ((in_array($prevOptName . ":", $longOptions) || in_array(rtrim($prevOptName, "=") . ":", $longOptions)) && isset($options[rtrim($prevOptName, "=")]) && $options[rtrim($prevOptName, "=")] === $argv[$i]) {
            continue; // C'est la valeur d'une option longue
        }
    }

    if (!$isOption && !$isOptionValue) {
        $nonOptionArgs[] = $argv[$i];
    }
}

if (!$listFilesOnly && count($nonOptionArgs) > 0) {
    $searchTerm = end($nonOptionArgs);
} elseif (!$listFilesOnly && count($nonOptionArgs) === 0) {
    // Si on ne liste pas les fichiers et qu'aucun terme n'est donné
    if (!isset($options['h']) && !isset($options['help'])) {
        echo ANSI_RED . "Erreur: Terme de recherche manquant. Utilisez --list-files pour lister les fichiers ou --help pour l'aide." . ANSI_RESET . "\n";
        displayHelp();
        exit(1);
    }
}


// Afficher l'aide si demandé
if (isset($options['h']) || isset($options['help'])) {
    displayHelp();
    exit(0);
}


// --- Configuration ---
$excludedDirs = [
    '.git',
    'node_modules',
    'vendor',
    'storage',
    'cache',
    '.idea',
    '.vscode',
    'build',
    'dist',
];
if (isset($options['e']) || isset($options['exclude'])) {
    $customExcludes = isset($options['e']) ? $options['e'] : $options['exclude'];
    $additionalExcludes = explode(',', $customExcludes);
    $excludedDirs = array_merge($excludedDirs, array_map('trim', $additionalExcludes));
}
$normalizedExcludedDirs = [];
foreach ($excludedDirs as $exDir) {
    if (strpos($exDir, DIRECTORY_SEPARATOR) === 0 || preg_match('/^[A-Za-z]:\\\\/', $exDir)) {
        $normalizedExcludedDirs[] = realpath($exDir);
    } else {
        $normalizedExcludedDirs[] = realpath($startDir . DIRECTORY_SEPARATOR . $exDir);
    }
}
$excludedDirs = array_filter($normalizedExcludedDirs);

$includedExtensions = [
    'php',
    'js',
    'ts',
    'jsx',
    'tsx',
    'vue',
    'svelte',
    'html',
    'htm',
    'css',
    'scss',
    'sass',
    'less',
    'json',
    'yaml',
    'yml',
    'md',
    'txt',
    'env',
    'sh',
    'bash',
    'py',
    'rb',
];
if (isset($options['i']) || isset($options['include'])) {
    $customIncludes = isset($options['i']) ? $options['i'] : $options['include'];
    $includedExtensions = array_map('trim', explode(',', $customIncludes));
}

$excludedFiles = [basename(__FILE__)];



if ($listFilesOnly) {
    echo ANSI_GREEN . "Listage des fichiers dans : " . ANSI_YELLOW . $startDir . ANSI_RESET . "\n";
} else {
    echo ANSI_GREEN . "Recherche de '" . ANSI_RED . $searchTerm . ANSI_GREEN . "' dans : " . ANSI_YELLOW . $startDir . ANSI_RESET . "\n";
}

if ($filePattern) {
    echo ANSI_GREEN . "Pattern de fichier : '" . ANSI_RED . $filePattern . ANSI_GREEN . "'" . ANSI_RESET . "\n";
}
echo ANSI_GREEN . "Exclusion des répertoires : " . implode(', ', array_map(function ($d) use ($startDir) {
    $relPath = str_replace($startDir . DIRECTORY_SEPARATOR, '', $d);
    return $relPath === $d ? $d : './' . $relPath; // Afficher relatif pour la lisibilité
}, $excludedDirs)) . ANSI_RESET . "\n";

if (!empty($includedExtensions) && !$filePattern) { // N'afficher que si filePattern n'est pas utilisé
    echo ANSI_GREEN . "Inclusion des extensions : " . implode(', ', $includedExtensions) . ANSI_RESET . "\n";
}
if ($contextLines > 0 && !$listFilesOnly) {
    echo ANSI_GREEN . "Affichage de " . $contextLines . " lignes de contexte avant/après" . ANSI_RESET . "\n";
}
echo "--------------------------------------------------\n";

$foundCount = 0;         // Pour les occurrences du terme
$fileWithMatchCount = 0; // Pour les fichiers contenant le terme
$filesListedCount = 0;   // Pour les fichiers listés (mode --list-files)
$filesScannedTotal = 0;  // Nombre total de fichiers rencontrés avant filtrage fin

$directoryIterator = new RecursiveDirectoryIterator($startDir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS | FilesystemIterator::CURRENT_AS_FILEINFO);
$recursiveIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::SELF_FIRST);

$matchingFilesList = []; // Pour stocker les fichiers correspondants en mode listage

foreach ($recursiveIterator as $fileInfo) {
    /** @var SplFileInfo $fileInfo */
    $pathname = $fileInfo->getRealPath();
    if ($pathname === false) continue;

    $filename = $fileInfo->getFilename();

    $isExcluded = false;
    foreach ($excludedDirs as $excludedDir) {
        if ($excludedDir && strpos($pathname, $excludedDir) === 0) {
            $isExcluded = true;
            if ($fileInfo->isDir()) {
                $depth = $recursiveIterator->getDepth();
                while ($recursiveIterator->valid() && $recursiveIterator->getDepth() > $depth) {
                    $recursiveIterator->next();
                }
                // Avancer l'itérateur principal pour s'assurer qu'on sort bien du répertoire
                if ($recursiveIterator->valid()) $recursiveIterator->next();
                // Il faut continuer la boucle externe pour passer au prochain élément au même niveau
                // que le répertoire exclu
                if (!$recursiveIterator->valid()) break; // Fin de l'itération
                $pathname = $recursiveIterator->current()->getRealPath();
                if ($pathname === false) continue;
                $filename = $recursiveIterator->current()->getFilename();
            }
            break;
        }
    }
    if ($isExcluded && $fileInfo->isDir()) continue; // Si le répertoire lui-même est exclu, on saute.
    if ($isExcluded) continue; // Pour les fichiers dans un répertoire qui n'était pas lui-même à la racine des exclus


    if ($fileInfo->isFile()) {
        $filesScannedTotal++;

        if (in_array($filename, $excludedFiles)) {
            continue;
        }

        if ($filePattern && !fnmatch($filePattern, $filename)) {
            continue;
        }

        if (!$filePattern) {
            $extension = strtolower($fileInfo->getExtension());
            if (!empty($includedExtensions) && !in_array($extension, $includedExtensions)) {
                continue;
            }
        }

        // Si on est en mode listage, on ajoute le fichier et on continue
        if ($listFilesOnly) {
            $filesListedCount++;
            $matchingFilesList[] = $pathname;
            continue; // Pas besoin de lire le contenu
        }

        // --- Suite de la logique de recherche de contenu (si $searchTerm n'est pas null) ---
        if (!$searchTerm) continue; // Sécurité, ne devrait pas arriver si la logique d'arg est bonne

        if (!$fileInfo->isReadable()) {
            echo ANSI_RED . "Erreur: Impossible de lire le fichier " . ANSI_YELLOW . $pathname . ANSI_RESET . "\n";
            continue;
        }

        $fileSizeMB = $fileInfo->getSize() / (1024 * 1024);
        if ($fileSizeMB > $fileLimit) {
            echo ANSI_YELLOW . "Avertissement: Fichier ignoré car trop volumineux (" . number_format($fileSizeMB, 2) . " MB): " . $pathname . ANSI_RESET . "\n";
            continue;
        }

        $fileHasMatch = false;
        $matches = []; // [lineNumber => lineContent]
        $lineNumber = 0;
        $handle = @fopen($pathname, "r");
        if ($handle === false) {
            echo ANSI_RED . "Erreur: Échec de l'ouverture du fichier " . ANSI_YELLOW . $pathname . ANSI_RESET . "\n";
            continue;
        }

        $lineBuffer = new SplDoublyLinkedList(); // Plus efficace pour FIFO
        $maxBuffer = $contextLines * 2 + 1 + 5; // Marge pour le contexte

        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            $lineBuffer->push([$lineNumber, $line]); // Stocker [numLigne, contenuLigne]
            if ($lineBuffer->count() > $maxBuffer) {
                $lineBuffer->shift();
            }

            if (strpos($line, $searchTerm) !== false) {
                if (!$fileHasMatch) {
                    $fileHasMatch = true;
                    $fileWithMatchCount++;
                }
                $matches[$lineNumber] = $line;
                $foundCount++;
            }
        }
        fclose($handle);

        if ($fileHasMatch) {
            echo ANSI_YELLOW . $pathname . ANSI_RESET . "\n";
            $matchLineNumbers = array_keys($matches);
            sort($matchLineNumbers);
            $lastLineDisplayed = 0;

            // Convertir SplDoublyLinkedList en array pour accès par clé (numéro de ligne)
            $contextArray = [];
            foreach ($lineBuffer as $bufferedItem) {
                $contextArray[$bufferedItem[0]] = $bufferedItem[1];
            }

            foreach ($matchLineNumbers as $matchLineNumber) {
                // Contexte avant
                for ($i = max(1, $matchLineNumber - $contextLines); $i < $matchLineNumber; $i++) {
                    if (isset($contextArray[$i]) && $i > $lastLineDisplayed) {
                        if (!isset($matches[$i])) { // Ne pas réafficher une ligne de match comme contexte
                            echo ANSI_GRAY . "  Ligne " . $i . ": " . rtrim($contextArray[$i]) . ANSI_RESET . "\n";
                        }
                    }
                }
                // Ligne de match
                $lineContent = $matches[$matchLineNumber];
                $highlightedLine = str_replace($searchTerm, ANSI_BG_RED . $searchTerm . ANSI_RESET . ANSI_RED, rtrim($lineContent));
                echo "  " . ANSI_CYAN . "Ligne " . $matchLineNumber . ":" . ANSI_RESET . " " . ANSI_RED . $highlightedLine . ANSI_RESET . "\n";

                // Contexte après
                for ($i = $matchLineNumber + 1; $i <= $matchLineNumber + $contextLines; $i++) {
                    if (isset($contextArray[$i])) {
                        if (!isset($matches[$i])) { // Ne pas réafficher une ligne de match comme contexte
                            echo ANSI_GRAY . "  Ligne " . $i . ": " . rtrim($contextArray[$i]) . ANSI_RESET . "\n";
                        }
                    }
                }
                $lastLineDisplayed = $matchLineNumber + $contextLines;
                $nextMatchIndex = array_search($matchLineNumber, $matchLineNumbers) + 1;
                if ($nextMatchIndex < count($matchLineNumbers) && $matchLineNumbers[$nextMatchIndex] > $lastLineDisplayed + 1) {
                    echo ANSI_GRAY . "  ..." . ANSI_RESET . "\n";
                }
            }
            echo "\n";
        }
    }
}

// Affichage si mode --list-files
if ($listFilesOnly) {
    if ($filesListedCount > 0) {
        echo ANSI_GREEN . "Fichiers correspondants aux critères :" . ANSI_RESET . "\n";
        foreach ($matchingFilesList as $filePath) {
            echo ANSI_YELLOW . $filePath . ANSI_RESET . "\n";
        }
    }
    echo "--------------------------------------------------\n";
    echo ANSI_GREEN . "Listage terminé." . ANSI_RESET . "\n";
    echo "Nombre total de fichiers scannés (avant filtrage fin) : " . $filesScannedTotal . "\n";
    echo "Résultat : " . ANSI_YELLOW . $filesListedCount . ANSI_RESET . " fichier(s) trouvé(s) correspondant aux critères.\n";
} else { // Affichage mode recherche de contenu
    echo "--------------------------------------------------\n";
    echo ANSI_GREEN . "Recherche terminée." . ANSI_RESET . "\n";
    echo "Nombre total de fichiers scannés (avant filtrage fin) : " . $filesScannedTotal . "\n";
    echo "Résultat : " . ANSI_RED . $foundCount . ANSI_RESET . " occurrence(s) trouvée(s) dans " . ANSI_YELLOW . $fileWithMatchCount . ANSI_RESET . " fichier(s).\n";
}


function displayHelp()
{
    $scriptName = basename(__FILE__);
    echo <<<HELP
Usage: php {$scriptName} [OPTIONS] [terme_recherche]

Si <terme_recherche> est fourni, recherche le contenu.
Si --list-files est utilisé, <terme_recherche> est ignoré et les fichiers sont listés.

OPTIONS:
  -h, --help                 Affiche cette aide
  --list-files             Liste les fichiers correspondant aux critères sans chercher de contenu.
  -c, --context=NOMBRE       Affiche N lignes de contexte avant/après chaque correspondance (si recherche de contenu)
  -i, --include=LISTE        Liste d'extensions à inclure (ex: "php,js")
                           (Ignoré si --file-pattern est utilisé)
  -e, --exclude=LISTE        Liste de répertoires supplémentaires à exclure (ex: "tests,build")
  -l, --limit=NOMBRE         Limite de taille des fichiers en MB (défaut: 10)
  -p, --path=CHEMIN          Répertoire de départ pour la recherche (défaut: répertoire courant)
  -f, --file-pattern=PATTERN Pattern de nom de fichier à rechercher (ex: "*.php", "User*.js")
                             Si utilisé, l'option --include est ignorée.

Exemples (Recherche de contenu):
  php {$scriptName} VITE_GRAPHQL_ENDPOINT
  php {$scriptName} --context=2 "function getUsers"
  php {$scriptName} -p="src/Services" -f="WhatsApp*.php" "sendTemplateMessage"

Exemples (Listage de fichiers):
  php {$scriptName} --list-files -p "src" -f "*.vue"
  php {$scriptName} --list-files --path="app/Http/Controllers" --include="php"
  php {$scriptName} --list-files -e="vendor,node_modules"

HELP;
}

exit(0);

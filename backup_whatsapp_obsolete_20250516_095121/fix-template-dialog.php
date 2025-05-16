<?php
/**
 * Script pour remplacer la fonction retryLoadTemplates dans le composant WhatsAppTemplateMessageDialog.vue
 */

// Chemins de fichiers
$filePath = __DIR__ . '/../frontend/src/components/whatsapp/WhatsAppTemplateMessageDialog.vue';
$backupPath = __DIR__ . '/../frontend/src/components/whatsapp/WhatsAppTemplateMessageDialog.vue.backup';

// Sauvegarder une copie du fichier original
copy($filePath, $backupPath);
echo "Fichier sauvegardé: $backupPath\n";

// Lire le contenu du fichier
$content = file_get_contents($filePath);

// Rechercher le début et la fin de la fonction retryLoadTemplates
$startPattern = "async function retryLoadTemplates() {";
$endPattern = "isLoadingTemplates.value = false;";

// Trouver la position de début
$startPos = strpos($content, $startPattern);
if ($startPos === false) {
    echo "Erreur: Impossible de trouver le début de la fonction retryLoadTemplates\n";
    exit(1);
}

// Trouver la position de fin
$tmpContent = substr($content, $startPos);
$endPos = strpos($tmpContent, $endPattern);
if ($endPos === false) {
    echo "Erreur: Impossible de trouver la fin de la fonction retryLoadTemplates\n";
    exit(1);
}

// Inclure la ligne de fin
$endPos += strlen($endPattern) + 3; // +3 pour la ligne "  }"

// Extraire la fonction complète
$oldFunction = substr($tmpContent, 0, $endPos);
echo "Ancienne fonction trouvée (" . strlen($oldFunction) . " caractères)\n";

// Nouvelle fonction
$newFunction = <<<'EOD'
async function retryLoadTemplates() {
  // Utiliser le module séparé pour charger les templates avec fallback robuste
  await loadTemplatesWithFallback({
    whatsappUserTemplateStore,
    authStore,
    $q,
    loadingState: isLoadingTemplates
  });
}
EOD;

echo "Nouvelle fonction préparée (" . strlen($newFunction) . " caractères)\n";

// Remplacer la fonction
$newContent = str_replace($oldFunction, $newFunction, $content);

// Vérifier que le remplacement a fonctionné
if ($newContent === $content) {
    echo "Erreur: Le remplacement n'a pas fonctionné\n";
    echo "Voici les 100 premiers caractères de la fonction:\n";
    echo substr($oldFunction, 0, 100) . "...\n";
    exit(1);
}

// Écrire le contenu modifié
file_put_contents($filePath, $newContent);
echo "Fonction remplacée avec succès!\n";
#!/bin/bash

# Le répertoire actuel
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
ROOT_DIR="$(dirname "$DIR")"

# Créer le répertoire de logs s'il n'existe pas
mkdir -p "$ROOT_DIR/var/logs"

echo "=== VÉRIFICATION DES TEMPLATES WHATSAPP ==="
echo "Début des tests: $(date)"

# 1. Vérifier la base de données pour les templates de l'utilisateur 2
echo
echo "Vérification directe des templates en base de données..."
sqlite3 "$ROOT_DIR/var/database.sqlite" "SELECT id, user_id, template_name, language_code FROM whatsapp_user_templates WHERE user_id = 2;"

# 2. Lancer le script de test PHP
echo
echo "Exécution du script de test PHP..."
php "$DIR/test-whatsapp-templates-fix.php"

# 3. Afficher les résultats
echo
echo "Résultats des tests:"
tail -n 20 "$ROOT_DIR/var/logs/test-templates-fix.log"

echo
echo "Tests terminés à $(date)"
echo "Voir $ROOT_DIR/var/logs/test-templates-fix.log pour les détails complets."
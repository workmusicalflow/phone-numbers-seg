#!/bin/bash
# Script d'audit complet des fonctionnalités WhatsApp

DB_PATH="/Users/ns2poportable/Desktop/phone-numbers-seg/var/database.sqlite"
OUTPUT_DIR="/Users/ns2poportable/Desktop/phone-numbers-seg/var/audit-results"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
AUDIT_LOG="$OUTPUT_DIR/whatsapp_audit_$TIMESTAMP.log"

# Créer le répertoire de sortie s'il n'existe pas
mkdir -p "$OUTPUT_DIR"

# Fonction pour exécuter des requêtes SQLite et enregistrer les résultats
execute_query() {
    local query="$1"
    local output_file="$2"
    
    echo "Exécution de la requête: $query"
    echo "Résultats enregistrés dans: $output_file"
    
    echo "=== REQUÊTE: $query ===" >> "$output_file"
    sqlite3 "$DB_PATH" "$query" >> "$output_file"
    echo -e "\n\n" >> "$output_file"
}

echo "=== AUDIT DES FONCTIONNALITÉS WHATSAPP ===" > "$AUDIT_LOG"
echo "Date: $(date)" >> "$AUDIT_LOG"
echo "Base de données: $DB_PATH" >> "$AUDIT_LOG"
echo -e "\n\n" >> "$AUDIT_LOG"

# 1. Analyse des tables liées à WhatsApp
echo "=== TABLES LIÉES À WHATSAPP ===" >> "$AUDIT_LOG"
sqlite3 "$DB_PATH" ".tables" | grep -i whatsapp >> "$AUDIT_LOG"
echo -e "\n\n" >> "$AUDIT_LOG"

# 2. Schéma des tables WhatsApp
for table in $(sqlite3 "$DB_PATH" ".tables" | grep -i whatsapp); do
    echo "=== SCHÉMA DE LA TABLE: $table ===" >> "$AUDIT_LOG"
    sqlite3 "$DB_PATH" ".schema $table" >> "$AUDIT_LOG"
    echo -e "\n\n" >> "$AUDIT_LOG"
done

# 3. Analyse des données dans chaque table WhatsApp
for table in $(sqlite3 "$DB_PATH" ".tables" | grep -i whatsapp); do
    echo "=== DONNÉES DE LA TABLE: $table ===" >> "$AUDIT_LOG"
    echo "Nombre d'enregistrements: $(sqlite3 "$DB_PATH" "SELECT COUNT(*) FROM $table")" >> "$AUDIT_LOG"
    
    # Afficher un échantillon des données (limité à 10 pour éviter des sorties trop volumineuses)
    echo "Échantillon de données:" >> "$AUDIT_LOG"
    sqlite3 "$DB_PATH" ".mode column" >> "$AUDIT_LOG"
    sqlite3 "$DB_PATH" ".headers on" >> "$AUDIT_LOG"
    sqlite3 "$DB_PATH" "SELECT * FROM $table LIMIT 10" >> "$AUDIT_LOG"
    echo -e "\n\n" >> "$AUDIT_LOG"
done

# 4. Analyse spécifique des templates WhatsApp
echo "=== ANALYSE DES TEMPLATES WHATSAPP ===" >> "$AUDIT_LOG"
execute_query "SELECT COUNT(*) AS total_templates FROM whatsapp_templates" "$AUDIT_LOG"
execute_query "SELECT COUNT(*) AS total_user_templates FROM whatsapp_user_templates" "$AUDIT_LOG"
execute_query "SELECT user_id, COUNT(*) AS template_count FROM whatsapp_user_templates GROUP BY user_id" "$AUDIT_LOG"

# 5. Vérification des utilisateurs ayant des templates
echo "=== UTILISATEURS AVEC TEMPLATES ===" >> "$AUDIT_LOG"
execute_query "SELECT u.id, u.username, COUNT(wut.id) AS template_count 
              FROM users u 
              LEFT JOIN whatsapp_user_templates wut ON u.id = wut.user_id 
              GROUP BY u.id, u.username" "$AUDIT_LOG"

# 6. Vérification des contacts WhatsApp
echo "=== CONTACTS WHATSAPP ===" >> "$AUDIT_LOG"
execute_query "SELECT COUNT(*) AS total_contacts FROM contacts" "$AUDIT_LOG"
execute_query "SELECT COUNT(*) AS whatsapp_enabled_contacts FROM contacts WHERE is_whatsapp_enabled = 1" "$AUDIT_LOG"
execute_query "SELECT COUNT(*) AS whatsapp_verified_contacts FROM contacts WHERE is_whatsapp_verified = 1" "$AUDIT_LOG"

# 7. Analyse des messages WhatsApp
echo "=== MESSAGES WHATSAPP ===" >> "$AUDIT_LOG"
execute_query "SELECT COUNT(*) AS total_messages FROM whatsapp_messages" "$AUDIT_LOG"
execute_query "SELECT message_type, COUNT(*) AS count FROM whatsapp_messages GROUP BY message_type" "$AUDIT_LOG"
execute_query "SELECT status, COUNT(*) AS count FROM whatsapp_messages GROUP BY status" "$AUDIT_LOG"

# 8. Analyse des événements WhatsApp
echo "=== ÉVÉNEMENTS WHATSAPP ===" >> "$AUDIT_LOG"
execute_query "SELECT COUNT(*) AS total_events FROM whatsapp_events" "$AUDIT_LOG"
execute_query "SELECT event_type, COUNT(*) AS count FROM whatsapp_events GROUP BY event_type" "$AUDIT_LOG"

# 9. Vérification des relations entre tables WhatsApp
echo "=== RELATIONS ENTRE TABLES WHATSAPP ===" >> "$AUDIT_LOG"
execute_query "SELECT 
                (SELECT COUNT(*) FROM whatsapp_user_templates wut 
                 LEFT JOIN users u ON wut.user_id = u.id 
                 WHERE u.id IS NULL) AS orphaned_user_templates,
                (SELECT COUNT(*) FROM whatsapp_messages wm 
                 LEFT JOIN contacts c ON wm.contact_phone_number = c.phone_number 
                 WHERE c.id IS NULL) AS orphaned_messages" "$AUDIT_LOG"

# 10. Analyse des performances (requêtes complexes)
echo "=== ANALYSE DES TEMPLATES PAR UTILISATEUR ET LANGUE ===" >> "$AUDIT_LOG"
execute_query "SELECT u.username, wut.language_code, COUNT(*) AS template_count 
              FROM whatsapp_user_templates wut 
              JOIN users u ON wut.user_id = u.id 
              GROUP BY u.username, wut.language_code 
              ORDER BY u.username, template_count DESC" "$AUDIT_LOG"

# 11. Vérification des contraintes d'intégrité
echo "=== VÉRIFICATION DES CONTRAINTES D'INTÉGRITÉ ===" >> "$AUDIT_LOG"
execute_query "PRAGMA foreign_key_check" "$AUDIT_LOG"

# 12. Tests spécifiques pour l'utilisateur 2 (testuser)
echo "=== TESTS SPÉCIFIQUES POUR TESTUSER (ID=2) ===" >> "$AUDIT_LOG"
execute_query "SELECT * FROM users WHERE id = 2" "$AUDIT_LOG"
execute_query "SELECT * FROM whatsapp_user_templates WHERE user_id = 2" "$AUDIT_LOG"
execute_query "SELECT 
                (SELECT COUNT(*) FROM contacts WHERE user_id = 2) AS contacts_count,
                (SELECT COUNT(*) FROM contacts WHERE user_id = 2 AND is_whatsapp_enabled = 1) AS whatsapp_contacts_count,
                (SELECT COUNT(*) FROM whatsapp_messages wm JOIN contacts c ON wm.contact_phone_number = c.phone_number WHERE c.user_id = 2) AS messages_count" "$AUDIT_LOG"

echo "Audit terminé. Résultats disponibles dans $AUDIT_LOG"
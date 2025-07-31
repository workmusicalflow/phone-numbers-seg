#!/bin/bash

# Script pour nettoyer les fichiers obsolètes de l'intégration WhatsApp
# Créer un backup avant de supprimer

echo "Nettoyage des fichiers obsolètes WhatsApp..."
echo "========================================="

# Créer un dossier de backup avec timestamp
BACKUP_DIR="backup_whatsapp_obsolete_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Fonction pour sauvegarder et supprimer
backup_and_remove() {
    file="$1"
    if [ -f "$file" ]; then
        echo "Sauvegarde et suppression de: $file"
        cp "$file" "$BACKUP_DIR/" 2>/dev/null
        rm "$file"
    elif [ -d "$file" ]; then
        echo "Sauvegarde et suppression du dossier: $file"
        cp -r "$file" "$BACKUP_DIR/" 2>/dev/null
        rm -rf "$file"
    fi
}

# 1. Supprimer les fichiers de backup des entités
backup_and_remove "src/Entities/WhatsApp/backup"

# 2. Supprimer les entités obsolètes
backup_and_remove "src/Entities/WhatsApp/WhatsAppMessage.php"
backup_and_remove "src/Entities/WhatsApp/WhatsAppMessageExisting.php"

# 3. Supprimer les fichiers .fix
backup_and_remove "src/Repositories/Doctrine/WhatsApp/WhatsAppUserTemplateRepository.php.fix"

# 4. Scripts de migration obsolètes
backup_and_remove "scripts/migrate-whatsapp-data.php"
backup_and_remove "scripts/migrate_whatsapp_messages.php"
backup_and_remove "scripts/migration/migrate-whatsapp-messages-to-history.php"
backup_and_remove "scripts/migration/prepare-whatsapp-migration.php"
backup_and_remove "scripts/replace-whatsapp-entities.php"
backup_and_remove "scripts/convert-whatsapp-entities-to-attributes.php"
backup_and_remove "scripts/fix-whatsapp-entities-attributes.php"

# 5. Scripts de création de tables obsolètes
backup_and_remove "scripts/clean-whatsapp-tables.php"
backup_and_remove "scripts/create-whatsapp-tables-safe.sql"
backup_and_remove "scripts/create-whatsapp-tables-sqlite.sql"
backup_and_remove "scripts/create-whatsapp-user-tables.php"
backup_and_remove "scripts/create-whatsapp-user-templates-sqlite.sql"

# 6. Scripts de test obsolètes
backup_and_remove "scripts/test-existing-whatsapp-tables.php"
backup_and_remove "scripts/test-whatsapp-entities-fixed.php"
backup_and_remove "scripts/test-whatsapp-data.php"
backup_and_remove "scripts/test-whatsapp-integration-complete.php"
backup_and_remove "scripts/test-whatsapp-service-simple.php"
backup_and_remove "scripts/test-whatsapp-service.php"
backup_and_remove "scripts/update-whatsapp-doctrine-schema.php"

# 7. Scripts d'audit
backup_and_remove "scripts/whatsapp-audit.sh"
backup_and_remove "scripts/whatsapp-audit-fixed.sh"

# 8. Scripts de templates obsolètes
backup_and_remove "scripts/check-whatsapp-templates.php"
backup_and_remove "scripts/debug-graphql-whatsapp-templates.php"
backup_and_remove "scripts/fix-template-dialog.php"
backup_and_remove "scripts/fix-user-template-issue.php"
backup_and_remove "scripts/run-whatsapp-templates-fix.sh"
backup_and_remove "scripts/test-direct-whatsapp-templates.php"
backup_and_remove "scripts/test-direct-whatsapp-user-templates.php"
backup_and_remove "scripts/test-whatsapp-templates-fix.php"

# 9. Fichiers public obsolètes
backup_and_remove "public/emergency-whatsapp-templates.php"
backup_and_remove "public/fallback-whatsapp-templates.php.backup"
backup_and_remove "public/test-whatsapp-templates.php"

# 10. Fichiers frontend temporaires
backup_and_remove "frontend/src/components/whatsapp/WhatsAppTemplateMessageDialog.vue.backup"
backup_and_remove "frontend/src/components/whatsapp/emergency-templates.js"
backup_and_remove "frontend/src/components/whatsapp/retry-templates-loader.js"

echo ""
echo "Nettoyage terminé !"
echo "Les fichiers ont été sauvegardés dans: $BACKUP_DIR"
echo ""
echo "Total des fichiers/dossiers supprimés:"
find "$BACKUP_DIR" -type f | wc -l

echo ""
echo "Pour restaurer un fichier, utilisez:"
echo "cp $BACKUP_DIR/nom_du_fichier destination"
# Backup des fichiers temporaires WhatsApp

Ce dossier contient une sauvegarde des fichiers temporaires et des solutions d'urgence qui ont été créés pour résoudre les problèmes avec l'API GraphQL et les templates WhatsApp.

## Fichiers sauvegardés

### Contrôleurs d'urgence
- `WhatsAppEmergencyController.php` - Contrôleur d'urgence initial
- `WhatsAppUltraFixController.php` - Version ultra-simplifiée du contrôleur d'urgence
- `WhatsAppDirectFixController.php` - Contrôleur avec injection directe

### Configuration
- `emergency.php` - Configuration DI pour les composants d'urgence

### Proxy et pages de test
- `graphql-proxy.php` - Proxy d'interception GraphQL
- `test-proxy.html` - Page HTML pour tester le proxy

### Scripts de diagnostic et de réparation
- `diagnostic-fix.php` - Script de diagnostic pour identifier les problèmes
- `apply-emergency-fix.php` - Script pour appliquer les correctifs d'urgence
- `fix-graphql-templates.php` - Script de correction des templates

## Contexte

Ces fichiers ont été créés pour résoudre temporairement un problème avec l'API GraphQL qui retournait des valeurs nulles pour des champs non-nullable. Cette erreur se produisait particulièrement avec la requête `fetchApprovedWhatsAppTemplates`.

La solution permanente consiste à mettre en place une architecture mixte REST-GraphQL, comme décrit dans la documentation `/docs/whatsapp-rest-graphql-architecture.md`.

**Rapport de nettoyage créé le 21 mai 2025**
# Système de Synchronisation des Templates WhatsApp

Ce document décrit le système de synchronisation des templates WhatsApp entre l'API Cloud de Meta et la base de données locale Oracle.

## Vue d'ensemble

Le système de synchronisation des templates WhatsApp permet de maintenir une copie locale des templates disponibles dans l'API Cloud de Meta. Cela offre plusieurs avantages :

- **Performance améliorée** : Réduit les appels API vers Meta en ayant une copie locale des templates
- **Fonctionnalité hors ligne** : Permet d'accéder aux templates même si l'API Meta est temporairement indisponible
- **Traçabilité** : Garde un historique des modifications apportées aux templates
- **Personnalisation** : Permet d'ajouter des métadonnées supplémentaires aux templates

## Architecture

Le système est composé des éléments suivants :

1. **`WhatsAppTemplateSyncService`** : Service principal qui orchestre la synchronisation
2. **`WhatsAppApiClient`** : Client pour l'API Cloud de Meta qui permet de récupérer les templates
3. **`WhatsAppTemplateRepository`** : Repository pour accéder et manipuler les templates en base de données
4. **`WhatsAppUserTemplateRepository`** : Repository pour gérer les associations template-utilisateur
5. **Scripts de synchronisation** : Pour l'exécution manuelle ou automatisée via cron

## Fonctionnalités principales

### Synchronisation des templates

La synchronisation récupère tous les templates disponibles depuis l'API Meta et les enregistre dans la base de données locale. Pour chaque template :

- Si le template n'existe pas localement : il est créé
- Si le template existe déjà : il est mis à jour si nécessaire (changement de statut, composants, etc.)
- Les templates sont identifiés par leur nom et leur code de langue

### Synchronisation avec les utilisateurs

Une fois les templates synchronisés avec la base de données, ils peuvent être associés à des utilisateurs spécifiques, généralement l'administrateur. Cette étape crée des enregistrements dans la table `whatsapp_user_templates`.

### Désactivation des templates orphelins

Les templates qui n'existent plus dans l'API Meta mais sont présents dans la base de données locale sont marqués comme inactifs. Cette approche permet de conserver l'historique tout en évitant de proposer des templates qui ne sont plus disponibles.

### Génération de rapports

Le service peut générer des rapports détaillés sur l'état des templates, avec des statistiques sur :
- Le nombre total de templates
- Leur répartition par statut (approuvé, en attente, rejeté)
- Leur répartition par catégorie et par langue
- La comparaison entre les templates locaux et ceux disponibles via l'API

## Utilisation

### Via script manuel

Le script `sync-whatsapp-templates.php` permet de lancer la synchronisation manuellement avec différentes options :

```bash
# Synchronisation standard
php scripts/active/whatsapp/sync-whatsapp-templates.php

# Forcer la mise à jour de tous les templates
php scripts/active/whatsapp/sync-whatsapp-templates.php --force

# Désactiver les templates orphelins
php scripts/active/whatsapp/sync-whatsapp-templates.php --disable

# Générer un rapport détaillé
php scripts/active/whatsapp/sync-whatsapp-templates.php --report

# Combinaisons d'options
php scripts/active/whatsapp/sync-whatsapp-templates.php --force --disable --report
```

### Via tâche cron

Pour une synchronisation automatique et régulière, utilisez le script `sync-whatsapp-templates-cron.php` :

```bash
# Exemple de configuration cron pour exécution toutes les 6 heures
0 */6 * * * php /path/to/sync-whatsapp-templates-cron.php >> /var/log/whatsapp-sync.log 2>&1
```

Ce script effectue une synchronisation complète avec désactivation des templates orphelins et journalise les résultats.

### Via le service dans le code

Vous pouvez également utiliser le service directement dans votre code :

```php
// Récupérer le service depuis le conteneur DI
$syncService = $container->get(App\Services\Interfaces\WhatsApp\WhatsAppTemplateSyncServiceInterface::class);

// Synchroniser les templates
$stats = $syncService->syncTemplates();

// Synchroniser avec les utilisateurs
$syncService->syncTemplatesWithUsers();

// Désactiver les templates orphelins
$syncService->disableOrphanedTemplates();

// Générer un rapport
$report = $syncService->generateTemplateReport();
```

## Considérations techniques

### Transactions et gestion des erreurs

Toutes les opérations de synchronisation sont exécutées dans des transactions pour garantir la cohérence des données. En cas d'erreur, les transactions sont annulées pour éviter les données partiellement synchronisées.

### Journalisation

Le service utilise le LoggerInterface PSR-3 pour journaliser toutes les opérations importantes et les erreurs. Ces logs peuvent être consultés pour diagnostiquer les problèmes ou pour auditer les activités de synchronisation.

### Optimisations

Le service est conçu pour minimiser les appels API à Meta et les opérations en base de données :

- Les templates sont chargés en une seule requête API
- Seules les modifications nécessaires sont appliquées en base de données
- La désactivation est préférée à la suppression pour préserver l'historique

### Quotas API

Attention aux quotas de l'API WhatsApp Business Cloud. La synchronisation peut consommer une partie significative de votre quota quotidien. Il est recommandé de ne pas exécuter la synchronisation trop fréquemment (une fois par jour ou moins est généralement suffisant).

## Dépannage

### Problèmes courants

1. **Erreurs d'authentification** : Vérifiez que votre token d'accès à l'API Meta est valide et dispose des permissions nécessaires
2. **Timeout** : Si vous avez beaucoup de templates, augmentez le timeout du client HTTP
3. **Problèmes de base de données** : Vérifiez les logs pour identifier les erreurs spécifiques

### Logs

Les logs de synchronisation peuvent être trouvés dans :
- Les logs généraux de l'application 
- Les fichiers de sortie des scripts cron si configurés

## Évolutions futures

Améliorations potentielles pour les versions futures :

1. Synchronisation incrémentielle basée sur les modifications récentes
2. Interface utilisateur pour visualiser et gérer la synchronisation
3. Webhooks pour notification automatique des modifications de templates
4. Sauvegarde et restauration des templates
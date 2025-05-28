# Configuration du Cron Job WhatsApp (macOS)

## Vue d'ensemble

Le système WhatsApp nécessite son propre cron job, **distinct** de celui des SMS. Ce cron job traite la file d'attente des messages WhatsApp en respectant les limites de l'API (100 messages/minute).

**Note** : Cette documentation est spécifique à macOS.

## Fichiers concernés

- **Script** : `scripts/cron/process_whatsapp_queue.php`
- **Log** : `var/logs/whatsapp_queue.log`
- **Lock** : `/tmp/whatsapp_queue_processor.lock`
- **Table DB** : `whatsapp_queue`

## Installation

### 1. Créer le répertoire de logs et vérifier les permissions

```bash
# Créer le répertoire de logs s'il n'existe pas
mkdir -p var/logs
chmod 755 var/logs

# Vérifier que le script a bien un shebang
head -1 scripts/cron/process_whatsapp_queue.php
# Devrait afficher : #!/usr/bin/env php

# Vérifier qu'il est exécutable
ls -la scripts/cron/process_whatsapp_queue.php
# Les permissions devraient montrer -rwxr-xr-x
```

### 2. Tester manuellement

Avant d'ajouter au cron, testez le script :

```bash
cd /Users/ns2poportable/Desktop/phone-numbers-seg
php scripts/cron/process_whatsapp_queue.php
```

Vérifiez que le log est créé :

```bash
tail -f var/logs/whatsapp_queue.log
```

### 3. Ajouter au crontab

Ouvrez le crontab :

```bash
crontab -e
```

Ajoutez une de ces lignes (les deux sont équivalentes) :

**Option 1 - Exécution directe (recommandée)** :
```bash
# Traitement de la queue WhatsApp toutes les minutes
* * * * * /Users/ns2poportable/Desktop/phone-numbers-seg/scripts/cron/process_whatsapp_queue.php >> /Users/ns2poportable/Desktop/phone-numbers-seg/var/logs/whatsapp_queue_cron.log 2>&1
```

**Option 2 - Avec l'interpréteur PHP** :
```bash
# Traitement de la queue WhatsApp toutes les minutes
* * * * * /usr/bin/php /Users/ns2poportable/Desktop/phone-numbers-seg/scripts/cron/process_whatsapp_queue.php >> /Users/ns2poportable/Desktop/phone-numbers-seg/var/logs/whatsapp_queue_cron.log 2>&1
```

**Note** : Le log du cron (`whatsapp_queue_cron.log`) est différent du log de l'application (`whatsapp_queue.log`) pour séparer les erreurs système des logs métier.

### 4. Configuration alternative avec script shell

Si vous préférez, créez un script wrapper :

```bash
#!/bin/bash
# scripts/cron/run_whatsapp_queue.sh

cd /Users/ns2poportable/Desktop/phone-numbers-seg
/usr/bin/php scripts/cron/process_whatsapp_queue.php
```

Puis dans le crontab :

```bash
* * * * * /Users/ns2poportable/Desktop/phone-numbers-seg/scripts/cron/run_whatsapp_queue.sh >> /Users/ns2poportable/Desktop/phone-numbers-seg/var/logs/whatsapp_queue_cron.log 2>&1
```

## Monitoring

### Vérifier que le cron fonctionne

**1. Vérifier les logs** :

```bash
# Log de l'application (messages traités, erreurs métier)
tail -f var/logs/whatsapp_queue.log

# Log du cron (erreurs système, problèmes d'exécution)
tail -f var/logs/whatsapp_queue_cron.log
```

**2. Vérifier la table queue** :

```sql
SELECT status, COUNT(*)
FROM whatsapp_queue
GROUP BY status;
```

**3. Vérifier le processus** :

```bash
ps aux | grep whatsapp_queue
```

### Format des logs

Le log devrait contenir des entrées comme :

```log
[2025-01-26 10:00:01] whatsapp_queue.INFO: Démarrage du traitement de la queue WhatsApp
[2025-01-26 10:00:01] whatsapp_queue.INFO: 25 messages en attente
[2025-01-26 10:00:02] whatsapp_queue.INFO: Message envoyé avec succès {"messageId":"xxx","recipient":"+225xxx"}
[2025-01-26 10:00:03] whatsapp_queue.INFO: Traitement terminé {"processed":25,"success":24,"failed":1}
```

## Différences avec le cron SMS

| Aspect     | Cron SMS                  | Cron WhatsApp                 |
| ---------- | ------------------------- | ----------------------------- |
| Script     | `process_sms_queue.php`   | `process_whatsapp_queue.php`  |
| Table      | `sms_queue`               | `whatsapp_queue`              |
| Log        | `logs/cron/sms_queue.log` | `var/logs/whatsapp_queue.log` |
| Rate limit | Selon opérateur           | 100 msg/minute                |
| API        | Orange API                | Meta WhatsApp API             |
| Base de données | SQLite            | SQLite                        |

## Gestion des erreurs

Le processeur gère automatiquement :

- **Lock file** : Évite les exécutions multiples
- **Retry** : 3 tentatives par message
- **Rate limiting** : Pause si limite atteinte
- **Logging** : Tous les événements sont tracés

## Maintenance

### Nettoyer les anciens messages

Le script de cron nettoie automatiquement les anciens messages (plus de 30 jours). Aucune configuration supplémentaire n'est nécessaire.

### Rotation des logs


Pour macOS, utilisez le script de rotation manuel fourni :

```bash
# Ajouter au crontab pour rotation quotidienne à minuit
0 0 * * * /Users/ns2poportable/Desktop/phone-numbers-seg/scripts/cron/rotate_whatsapp_logs.sh
```

Ou configurez avec `newsyslog.conf` :

```bash
# Ajouter à /etc/newsyslog.conf
/Users/ns2poportable/Desktop/phone-numbers-seg/var/logs/whatsapp_queue.log    644  30  *    @T00  GZ
```

## Troubleshooting

### Le cron ne s'exécute pas

1. Vérifiez les permissions du script
2. Vérifiez le chemin PHP dans le crontab
3. Consultez les logs système : `log show --predicate 'process == "cron"' --last 1h`

### Messages bloqués en "processing"

1. Vérifiez le lock file :
   ```bash
   ls -la /tmp/whatsapp_queue_processor.lock
   ```

2. Si nécessaire, supprimez-le :
   ```bash
   rm /tmp/whatsapp_queue_processor.lock
   ```

3. Relancez manuellement le script

### Performances lentes

1. Vérifiez la taille de la queue
2. Augmentez le batch size dans `WhatsAppQueueProcessor`
3. Vérifiez les temps de réponse de l'API WhatsApp

## Commandes utiles

```bash
# Voir le statut de la queue (SQLite)
sqlite3 var/database.sqlite "SELECT status, COUNT(*) FROM whatsapp_queue GROUP BY status"

# Voir les derniers messages traités
sqlite3 var/database.sqlite "SELECT * FROM whatsapp_queue ORDER BY updated_at DESC LIMIT 10"

# Compter les messages par batch
sqlite3 var/database.sqlite "SELECT batch_id, COUNT(*) FROM whatsapp_queue GROUP BY batch_id"

# Script de vérification du statut
php scripts/check-whatsapp-queue-status.php
```

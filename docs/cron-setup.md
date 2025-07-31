# Configuration des tâches cron pour Oracle SMS

Ce document décrit les tâches cron configurées pour le système Oracle SMS.

## Traitement de la file d'attente SMS

Une tâche cron est configurée pour exécuter le script de traitement de la file d'attente SMS chaque minute.
Cette tâche garantit que les SMS mis en file d'attente sont envoyés rapidement.

### Configuration

```
* * * * * /usr/bin/php /Users/ns2poportable/Desktop/phone-numbers-seg/scripts/cron/process_sms_queue.php --batch-size=50 --max-runtime=55 >> /Users/ns2poportable/Desktop/phone-numbers-seg/logs/cron/sms_queue.log 2>&1
```

### Paramètres du script

- `--batch-size=50` : Traite jusqu'à 50 SMS par lot
- `--max-runtime=55` : Limite le temps d'exécution à 55 secondes (pour éviter les chevauchements entre les exécutions)
- Les logs sont enregistrés dans `/Users/ns2poportable/Desktop/phone-numbers-seg/logs/cron/sms_queue.log`

### Vérification des logs

Pour vérifier si la tâche cron fonctionne correctement, consultez les logs avec la commande :

```bash
tail -f /Users/ns2poportable/Desktop/phone-numbers-seg/logs/cron/sms_queue.log
```

### Maintenance

Si vous devez modifier la configuration de la tâche cron :

1. Ouvrir l'éditeur crontab : `crontab -e`
2. Modifier la ligne concernée
3. Sauvegarder et quitter l'éditeur

### Dépannage

Si les SMS restent bloqués en statut "PENDING" :

1. Vérifiez que le daemon cron est actif : `ps aux | grep cron`
2. Exécutez manuellement le script pour voir s'il y a des erreurs :
   ```bash
   php /Users/ns2poportable/Desktop/phone-numbers-seg/scripts/cron/process_sms_queue.php --verbose
   ```
3. Vérifiez les permissions du script : il doit être exécutable
4. Consultez les logs d'erreur
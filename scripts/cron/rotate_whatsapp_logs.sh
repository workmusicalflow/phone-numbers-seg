#!/bin/bash
# Script de rotation des logs WhatsApp pour macOS
# À exécuter via cron (ex: 0 0 * * * /path/to/rotate_whatsapp_logs.sh)

LOGDIR="/Users/ns2poportable/Desktop/phone-numbers-seg/var/logs"
LOGFILE="whatsapp_queue.log"
KEEP_DAYS=30

# Aller dans le répertoire des logs
cd "$LOGDIR" || exit 1

# Si le fichier log existe et n'est pas vide
if [ -s "$LOGFILE" ]; then
    # Créer un backup avec timestamp
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    mv "$LOGFILE" "${LOGFILE}.${TIMESTAMP}"
    
    # Créer un nouveau fichier log vide
    touch "$LOGFILE"
    
    # Compresser les anciens logs
    gzip "${LOGFILE}.${TIMESTAMP}"
    
    # Supprimer les logs de plus de X jours
    find . -name "${LOGFILE}.*.gz" -mtime +${KEEP_DAYS} -delete
    
    echo "Log rotation completed at $(date)"
fi
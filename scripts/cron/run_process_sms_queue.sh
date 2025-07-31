#!/bin/bash

LOG_DIR="/Users/ns2poportable/Desktop/phone-numbers-seg/logs/cron"
LOG_FILE="$LOG_DIR/sms_queue.log"

# LOG TÉMOIN ABSOLUMENT EN PREMIER
echo "[$(date)] --- run_process_sms_queue.sh STARTED by cron ---" >> "$LOG_FILE" 2>&1

# Le reste de votre script...
set -e
PROJECT_ROOT="/Users/ns2poportable/Desktop/phone-numbers-seg"
SCRIPT_PATH="$PROJECT_ROOT/scripts/cron/process_sms_queue.php"
mkdir -p "$LOG_DIR" # Peut rester ici, mais le log témoin est avant

# Ajout des logs de debug (peut être utile de les laisser pour l'instant)
echo "Running as user: $(whoami)" >> "$LOG_FILE" 2>&1
echo "Initial Working Directory: $(pwd)" >> "$LOG_FILE" 2>&1 # Ceci montrera le dossier HOME de cron
echo "PATH variable: $PATH" >> "$LOG_FILE" 2>&1

cd "$PROJECT_ROOT"
echo "Working Directory after cd: $(pwd)" >> "$LOG_FILE" 2>&1 # Vérifier que le cd a fonctionné

# Exécution PHP
echo "Executing PHP script: /usr/local/opt/php@8.3/bin/php $SCRIPT_PATH --batch-size=50 --max-runtime=290 --verbose" >> "$LOG_FILE" 2>&1
/usr/local/opt/php@8.3/bin/php "$SCRIPT_PATH" --batch-size=50 --max-runtime=290 --verbose >> "$LOG_FILE" 2>&1
PHP_EXIT_CODE=$?
echo "PHP script finished with exit code: $PHP_EXIT_CODE" >> "$LOG_FILE" 2>&1

echo "[$(date)] --- run_process_sms_queue.sh FINISHED by cron ---" >> "$LOG_FILE" 2>&1

exit $PHP_EXIT_CODE # Sortir avec le code du script PHP
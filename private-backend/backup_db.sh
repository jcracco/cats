#!/bin/bash
# backup_db.sh — Daily MySQL backup for cats_db
# Location: /path/to/private/cats/backup_db.sh
# Make executable: chmod +x backup_db.sh

DB_USER="cats_jer"
DB_PASS="a5Y%c2PSjmmu?r2f"
DB_NAME="cats_db"
BACKUP_DIR="$(dirname "$0")/backups"  # relative to script location
DATE=$(date +%Y%m%d)

# Create backup
/usr/bin/mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/cats_$DATE.sql"

# Delete backups older than 30 days
find "$BACKUP_DIR" -name "*.sql" -mtime +30 -delete

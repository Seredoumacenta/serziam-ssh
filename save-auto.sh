# Script de sauvegarde
#!/bin/bash
BACKUP_DIR="/backups/serziam"
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u serziam_user -p'MotDePasseFort123!' serziam_ssh > $BACKUP_DIR/db_backup_$DATE.sql
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz /var/www/html/serziam-ssh/
find $BACKUP_DIR -type f -mtime +7 -delete

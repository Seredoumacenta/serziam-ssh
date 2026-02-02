# Éditer les crontab
sudo crontab -e

# Ajouter ces lignes:
*/5 * * * * /usr/bin/php /var/www/html/serziam-ssh/api/cron.php > /dev/null 2>&1
0 0 * * * /usr/bin/php /var/www/html/serziam-ssh/api/daily_update.php > /dev/null 2>&1

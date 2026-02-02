# Mettre à jour le serveur
sudo apt update && sudo apt upgrade -y

# Installer Apache, PHP, MySQL
sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql php-curl php-gd php-mbstring php-xml php-zip -y

# Activer les modules Apache
sudo a2enmod rewrite
sudo systemctl restart apache2

# Configurer MySQL
sudo mysql_secure_installation

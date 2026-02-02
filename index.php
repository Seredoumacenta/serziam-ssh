<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'serziam_ssh');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration du site
define('SITE_NAME', 'Serziam Technologie SSH');
define('SITE_URL', 'http://localhost/serziam-ssh');
define('ADMIN_USER', 'yayacamara');
define('ADMIN_PASS_HASH', password_hash('yayacamara1995', PASSWORD_DEFAULT));

// Configuration USSD (masquée)
define('ORANGE_NUMBER', '622001839');
define('MTN_NUMBER', '663199359');

// Démarrer la session
session_start();

// Connexion à la base de données
function getDB() {
    static $db = null;
    
    if ($db === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8';
            $db = new PDO($dsn, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }
    
    return $db;
}

// Fonction de sécurité
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Vérifier si l'admin est connecté
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Redirection
function redirect($url) {
    header("Location: $url");
    exit();
}
?>

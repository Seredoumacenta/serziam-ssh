<?php
require_once '../config/database.php';
$db = getDB();

// Supprimer les serveurs payés expirés (après 10 minutes)
$db->query("UPDATE servers SET status = 'expired' WHERE status = 'payed' AND payed_date < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");

// Marquer les transactions en attente comme échouées après 30 minutes
$db->query("UPDATE transactions SET status = 'failed' WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
?>

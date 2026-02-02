<?php
require_once '../config/database.php';
$db = getDB();

// Mettre à jour les prix et jours restants
$db->query("
    UPDATE servers 
    SET current_price = GREATEST(0, price - (FLOOR(DATEDIFF(NOW(), created_at)) * (price / days))),
        days_left = GREATEST(0, days - FLOOR(DATEDIFF(NOW(), created_at))),
        status = CASE 
            WHEN (days - FLOOR(DATEDIFF(NOW(), created_at))) <= 0 THEN 'expired'
            ELSE status 
        END
    WHERE status = 'active' AND is_payed = 0
");

// Désactiver les anciennes annonces
$db->query("UPDATE announcements SET is_active = 0 WHERE expires_at < NOW()");
?>

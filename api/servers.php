
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$db = getDB();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        // Récupérer tous les serveurs actifs
        $stmt = $db->query("SELECT * FROM servers WHERE status = 'active' ORDER BY created_at DESC");
        $servers = $stmt->fetchAll();
        
        // Décoder les caractéristiques JSON
        foreach ($servers as &$server) {
            $server['features'] = json_decode($server['features'] ?? '[]', true);
        }
        
        echo json_encode($servers);
        break;
        
    case 'update_prices':
        // Mettre à jour les prix quotidiennement (cron job)
        $stmt = $db->query("
            UPDATE servers 
            SET current_price = GREATEST(0, price - (FLOOR(DATEDIFF(NOW(), created_at)) * (price / days))),
                days_left = GREATEST(0, days - FLOOR(DATEDIFF(NOW(), created_at))),
                status = CASE 
                    WHEN (days - FLOOR(DATEDIFF(NOW(), created_at))) <= 0 THEN 'expired'
                    ELSE status 
                END
            WHERE status = 'active' AND is_payed = 0
        ");
        
        echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
        break;
        
    case 'check_expired':
        // Vérifier les serveurs payés expirés (après 10 minutes)
        $stmt = $db->query("
            UPDATE servers 
            SET status = 'expired' 
            WHERE status = 'payed' 
            AND is_payed = 1 
            AND payed_date < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        ");
        
        echo json_encode(['success' => true, 'expired' => $stmt->rowCount()]);
        break;
        
    case 'add':
        // Ajouter un nouveau serveur (admin seulement)
        if (!isAdminLoggedIn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès interdit']);
            exit;
        }
        
        $name = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? 4000;
        $days = $_POST['days'] ?? 4;
        $features = $_POST['features'] ?? '';
        $config = $_POST['config'] ?? '';
        $config_file = $_POST['config_file'] ?? '';
        
        // Traiter l'upload d'image
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = '../assets/uploads/';
            $filename = uniqid() . '_' . basename($_FILES['image']['name']);
            $target = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $image_url = 'assets/uploads/' . $filename;
            }
        }
        
        // Convertir les caractéristiques en JSON
        $features_array = array_filter(array_map('trim', explode("\n", $features)));
        $features_json = json_encode($features_array);
        
        $stmt = $db->prepare("
            INSERT INTO servers (name, price, current_price, days, days_left, features, config_content, config_file, image_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $name,
            $price,
            $price,
            $days,
            $days,
            $features_json,
            $config,
            $config_file,
            $image_url
        ]);
        
        $server_id = $db->lastInsertId();
        
        // Log de l'action
        $stmt = $db->prepare("INSERT INTO logs (action, details, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            'server_added',
            "Nouveau serveur ajouté: $name (ID: $server_id)",
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
        
        echo json_encode(['success' => true, 'server_id' => $server_id]);
        break;
        
    case 'delete':
        // Supprimer un serveur (admin seulement)
        if (!isAdminLoggedIn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès interdit']);
            exit;
        }
        
        $server_id = $_POST['server_id'] ?? 0;
        
        $stmt = $db->prepare("DELETE FROM servers WHERE id = ?");
        $stmt->execute([$server_id]);
        
        // Log de l'action
        $stmt = $db->prepare("INSERT INTO logs (action, details, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            'server_deleted',
            "Serveur supprimé (ID: $server_id)",
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
        
        echo json_encode(['success' => true]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action non valide']);
}
?>

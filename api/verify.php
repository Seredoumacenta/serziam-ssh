<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$db = getDB();

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? '';

// Fonction pour extraire les informations de transaction
function parseTransactionMessage($message) {
    $patterns = [
        // Pattern Orange Money
        '/Bonjour,Envoi de:\s*([\d.,]+)GNF.*?(\d{9}).*?reference:([A-Z0-9.]+)/i',
        // Pattern Mobile Money
        '/([\d.,]+)GNF.*?(\d{9}).*?reference:([A-Z0-9.]+)/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $message, $matches)) {
            $amount = (float) str_replace([',', '.'], ['', ''], $matches[1]);
            $phone = $matches[2];
            $reference = $matches[3];
            
            return [
                'amount' => $amount,
                'phone' => $phone,
                'reference' => $reference,
                'operator' => strpos($message, 'Orange') !== false ? 'orange' : 'mtn'
            ];
        }
    }
    
    return null;
}

// Vérifier la transaction
$transaction = parseTransactionMessage($message);

if (!$transaction) {
    echo json_encode([
        'success' => false,
        'message' => 'Format de message invalide'
    ]);
    exit;
}

// Vérifier si la référence existe déjà
$stmt = $db->prepare("SELECT * FROM transactions WHERE transaction_ref = ?");
$stmt->execute([$transaction['reference']]);
$existing = $stmt->fetch();

if ($existing) {
    echo json_encode([
        'success' => false,
        'message' => 'Cette transaction a déjà été traitée'
    ]);
    exit;
}

// Trouver un serveur correspondant au montant
$stmt = $db->prepare("SELECT * FROM servers WHERE status = 'active' AND is_payed = 0 AND ABS(current_price - ?) <= 100 ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$transaction['amount']]);
$server = $stmt->fetch();

if (!$server) {
    // Chercher avec une marge plus large
    $stmt = $db->prepare("SELECT * FROM servers WHERE status = 'active' AND is_payed = 0 ORDER BY ABS(current_price - ?) LIMIT 1");
    $stmt->execute([$transaction['amount']]);
    $server = $stmt->fetch();
    
    if (!$server) {
        echo json_encode([
            'success' => false,
            'message' => 'Aucun serveur correspondant au montant payé'
        ]);
        exit;
    }
}

// Début de la transaction
$db->beginTransaction();

try {
    // Enregistrer la transaction
    $stmt = $db->prepare("INSERT INTO transactions (server_id, transaction_ref, amount, operator, message, status, user_ip, verified_at) VALUES (?, ?, ?, ?, ?, 'verified', ?, NOW())");
    $stmt->execute([
        $server['id'],
        $transaction['reference'],
        $transaction['amount'],
        $transaction['operator'],
        $message,
        $_SERVER['REMOTE_ADDR']
    ]);
    
    // Marquer le serveur comme payé
    $stmt = $db->prepare("UPDATE servers SET is_payed = 1, status = 'payed', payed_date = NOW() WHERE id = ?");
    $stmt->execute([$server['id']]);
    
    // Commit de la transaction
    $db->commit();
    
    // Log de l'action
    $stmt = $db->prepare("INSERT INTO logs (action, details, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        'payment_verified',
        "Transaction {$transaction['reference']} vérifiée pour le serveur {$server['id']}",
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Paiement vérifié avec succès!',
        'config' => $server['config_content'],
        'configFile' => $server['config_file'],
        'serverId' => $server['id']
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du traitement: ' . $e->getMessage()
    ]);
}
?>

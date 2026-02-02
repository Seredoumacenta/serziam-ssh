<?php
require_once '../config/database.php';

// Vérifier la connexion admin
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();

// Récupérer les statistiques
$stats = [
    'total_servers' => $db->query("SELECT COUNT(*) FROM servers")->fetchColumn(),
    'active_servers' => $db->query("SELECT COUNT(*) FROM servers WHERE status = 'active'")->fetchColumn(),
    'total_transactions' => $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn(),
    'total_revenue' => $db->query("SELECT SUM(amount) FROM transactions WHERE status = 'verified'")->fetchColumn() ?: 0,
    'pending_transactions' => $db->query("SELECT COUNT(*) FROM transactions WHERE status = 'pending'")->fetchColumn(),
    'total_testimonials' => $db->query("SELECT COUNT(*) FROM testimonials")->fetchColumn(),
];

// Récupérer les transactions récentes
$recent_transactions = $db->query("
    SELECT t.*, s.name as server_name 
    FROM transactions t 
    LEFT JOIN servers s ON t.server_id = s.id 
    ORDER BY t.created_at DESC 
    LIMIT 10
")->fetchAll();

// Récupérer les serveurs récents
$recent_servers = $db->query("SELECT * FROM servers ORDER BY created_at DESC LIMIT 10")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2><i class="fas fa-cogs"></i> <?php echo SITE_NAME; ?></h2>
                <p>Administration</p>
            </div>
            
            <div class="admin-profile">
                <div class="profile-info">
                    <i class="fas fa-user-circle"></i>
                    <div>
                        <strong><?php echo $_SESSION['admin_username']; ?></strong>
                        <small>Administrateur</small>
                    </div>
                </div>
            </div>
            
            <nav class="admin-menu">
                <a href="dashboard.php" class="active">
                    <i class="fas fa-tachometer-alt"></i> Tableau de bord
                </a>
                <a href="servers.php">
                    <i class="fas fa-server"></i> Serveurs
                </a>
                <a href="transactions.php">
                    <i class="fas fa-exchange-alt"></i> Transactions
                </a>
                <a href="announcements.php">
                    <i class="fas fa-bullhorn"></i> Annonces
                </a>
                <a href="testimonials.php">
                    <i class="fas fa-star"></i> Témoignages
                </a>
                <a href="settings.php">
                    <i class="fas fa-cog"></i> Paramètres
                </a>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <!-- Header -->
            <div class="admin-header">
                <h1>Tableau de Bord</h1>
                <div class="header-actions">
                    <span class="welcome">Bonjour, <?php echo $_SESSION['admin_username']; ?>!</span>
                    <button class="btn-refresh" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4e73df;">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_servers']; ?></h3>
                        <p>Serveurs Totaux</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #1cc88a;">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['active_servers']; ?></h3>
                        <p>Serveurs Actifs</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #36b9cc;">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_transactions']; ?></h3>
                        <p>Transactions</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #f6c23e;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_revenue'], 0, ',', ' '); ?> GNF</h3>
                        <p>Revenu Total</p>
                    </div>
                </div>
            </div>
            
            <!-- Charts and Tables -->
            <div class="dashboard-grid">
                <!-- Transactions Récentes -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Transactions Récentes</h3>
                        <a href="transactions.php" class="btn-view-all">Voir tout</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Référence</th>
                                        <th>Montant</th>
                                        <th>Serveur</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_transactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo substr($transaction['transaction_ref'], 0, 12) . '...'; ?></td>
                                            <td><?php echo number_format($transaction['amount'], 0, ',', ' '); ?> GNF</td>
                                            <td><?php echo $transaction['server_name'] ?: 'N/A'; ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                                    <?php echo $transaction['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Serveurs Récents -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-server"></i> Serveurs Récents</h3>
                        <a href="servers.php" class="btn-view-all">Voir tout</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Prix</th>
                                        <th>Jours</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_servers as $server): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($server['name']); ?></td>
                                            <td><?php echo number_format($server['current_price'], 0, ',', ' '); ?> GNF</td>
                                            <td><?php echo $server['days_left']; ?> jours</td>
                                            <td>
                                                <span class="status-badge status-<?php echo $server['status']; ?>">
                                                    <?php echo $server['status']; ?>
                                                </span>
                                            </td>
                                            <td class="action-buttons">
                                                <button class="btn-action btn-edit" 
                                                        onclick="editServer(<?php echo $server['id']; ?>)"
                                                        title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-action btn-delete" 
                                                        onclick="deleteServer(<?php echo $server['id']; ?>)"
                                                        title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3><i class="fas fa-bolt"></i> Actions Rapides</h3>
                <div class="actions-grid">
                    <button class="action-btn" onclick="openModal('addServerModal')">
                        <i class="fas fa-plus-circle"></i>
                        <span>Ajouter un Serveur</span>
                    </button>
                    <button class="action-btn" onclick="window.location.href='announcements.php'">
                        <i class="fas fa-bullhorn"></i>
                        <span>Créer une Annonce</span>
                    </button>
                    <button class="action-btn" onclick="window.location.href='transactions.php'">
                        <i class="fas fa-search"></i>
                        <span>Vérifier une Transaction</span>
                    </button>
                    <button class="action-btn" onclick="exportData()">
                        <i class="fas fa-download"></i>
                        <span>Exporter les Données</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modals -->
    <div id="addServerModal" class="admin-modal">
        <!-- Contenu du modal d'ajout de serveur -->
        <div class="modal-content">
            <h3><i class="fas fa-plus-circle"></i> Ajouter un Nouveau Serveur</h3>
            <form id="addServerForm" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="serverName">Nom du Serveur *</label>
                        <input type="text" id="serverName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="serverPrice">Prix (GNF) *</label>
                        <input type="number" id="serverPrice" name="price" value="4000" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="serverDays">Durée (jours) *</label>
                        <input type="number" id="serverDays" name="days" value="4" required>
                    </div>
                    <div class="form-group">
                        <label for="serverImage">Image</label>
                        <input type="file" id="serverImage" name="image" accept="image/*">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="serverDescription">Description</label>
                    <textarea id="serverDescription" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="serverFeatures">Caractéristiques (une par ligne)</label>
                    <textarea id="serverFeatures" name="features" rows="4" placeholder="100 Mbps&#10;Illimité&#10;Support 24/7"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="serverConfig">Configuration *</label>
                    <textarea id="serverConfig" name="config" rows="6" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="configFile">Nom du fichier de configuration</label>
                    <input type="text" id="configFile" name="config_file" placeholder="serveur.ovpn">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <button type="button" class="btn-cancel" onclick="closeModal('addServerModal')">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        // Initialiser DataTables
        $(document).ready(function() {
            $('.data-table').DataTable({
                pageLength: 5,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
                }
            });
        });
        
        // Gestion du formulaire d'ajout de serveur
        document.getElementById('addServerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('../api/servers.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Serveur ajouté avec succès!');
                    location.reload();
                } else {
                    alert('Erreur: ' + result.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion au serveur');
            }
        });
    </script>
</body>
</html>

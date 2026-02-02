<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = getDB();

// Récupérer les serveurs actifs
$stmt = $db->prepare("SELECT * FROM servers WHERE status = 'active' ORDER BY created_at DESC");
$stmt->execute();
$servers = $stmt->fetchAll();

// Récupérer les annonces actives
$stmt = $db->prepare("SELECT * FROM announcements WHERE is_active = TRUE ORDER BY created_at DESC");
$stmt->execute();
$announcements = $stmt->fetchAll();

// Récupérer les témoignages approuvés
$stmt = $db->prepare("SELECT * FROM testimonials WHERE is_approved = TRUE ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$testimonials = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Serziam Technologie SSH - Serveurs VPN/SSH Premium">
    <meta name="robots" content="index, follow">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔒</text></svg>">
</head>
<body>
    <!-- Bandeau d'informations animé -->
    <div class="news-bar">
        <div class="news-content" id="newsContent">
            <?php foreach($announcements as $announcement): ?>
                <span>📢 <?php echo htmlspecialchars($announcement['content']); ?></span> • 
            <?php endforeach; ?>
        </div>
    </div>

    <!-- En-tête -->
    <header>
        <div class="container">
            <h1><i class="fas fa-server"></i> <?php echo SITE_NAME; ?></h1>
            <p class="tagline">Serveurs VPN/SSH Haute Performance</p>
        </div>
    </header>

    <!-- Zone de vérification de paiement -->
    <div class="payment-verification">
        <div class="container">
            <h3><i class="fas fa-check-circle"></i> Vérifier Votre Paiement</h3>
            <div class="verification-box">
                <textarea id="transactionMessage" 
                          placeholder="Collez ici le message de confirmation de transaction reçu sur votre téléphone...
Exemple: Bonjour,Envoi de: 4000.00GNF vers le 622001839, reference:PP260130.2102.C58818. Orange Money vous remercie"></textarea>
                <button onclick="verifyPayment()" class="btn-verify">
                    <i class="fas fa-search"></i> Vérifier la Transaction
                </button>
                <div id="verificationResult"></div>
            </div>
        </div>
    </div>

    <!-- Section des serveurs -->
    <main class="container">
        <div class="servers-grid" id="serversContainer">
            <?php if(empty($servers)): ?>
                <div class="no-servers">
                    <i class="fas fa-info-circle"></i>
                    <p>Aucun serveur disponible pour le moment.</p>
                </div>
            <?php else: ?>
                <?php foreach($servers as $server): 
                    $features = json_decode($server['features'] ?? '[]', true);
                    $priceColor = $server['current_price'] < $server['price'] ? 'style="color: #dc3545;"' : '';
                    $buttonClass = $server['is_payed'] ? 'btn-payed' : '';
                    $buttonText = $server['is_payed'] ? 'Déjà Payé' : 'Payer avec';
                    $daysText = $server['days_left'] > 0 ? 
                        "<i class='fas fa-clock'></i> {$server['days_left']} jours restants" : 
                        "<i class='fas fa-exclamation-triangle'></i> Expiré";
                ?>
                    <div class="server-card" data-id="<?php echo $server['id']; ?>">
                        <img src="<?php echo $server['image_url'] ?: 'https://via.placeholder.com/400x200?text=Serveur+VPN'; ?>" 
                             alt="<?php echo htmlspecialchars($server['name']); ?>" 
                             class="server-image">
                        <div class="server-info">
                            <h3><?php echo htmlspecialchars($server['name']); ?></h3>
                            <p class="server-description"><?php echo htmlspecialchars($server['description'] ?? ''); ?></p>
                            <div class="server-price">
                                <span <?php echo $priceColor; ?>><?php echo number_format($server['current_price'], 0, ',', ' '); ?> GNF</span>
                                <?php if($server['current_price'] < $server['price']): ?>
                                    <small style="text-decoration: line-through; color: #999;">
                                        <?php echo number_format($server['price'], 0, ',', ' '); ?> GNF
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="server-days">
                                <?php echo $daysText; ?>
                            </div>
                            <ul class="server-features">
                                <?php foreach($features as $feature): ?>
                                    <li><?php echo htmlspecialchars($feature); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="payment-buttons">
                                <button onclick="payWithOrange(<?php echo $server['id']; ?>, <?php echo $server['current_price']; ?>)" 
                                        class="btn-orange <?php echo $buttonClass; ?>"
                                        <?php echo $server['is_payed'] ? 'disabled' : ''; ?>>
                                    <i class="fas fa-mobile-alt"></i> 
                                    <?php echo $server['is_payed'] ? 'Déjà Payé' : 'Orange Money'; ?>
                                </button>
                                <button onclick="payWithMTN(<?php echo $server['id']; ?>, <?php echo $server['current_price']; ?>)" 
                                        class="btn-mtn <?php echo $buttonClass; ?>"
                                        <?php echo $server['is_payed'] ? 'disabled' : ''; ?>>
                                    <i class="fas fa-mobile-alt"></i> 
                                    <?php echo $server['is_payed'] ? 'Déjà Payé' : 'Mobile Money'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Section de témoignages -->
    <section class="testimonials">
        <div class="container">
            <h2><i class="fas fa-star"></i> Avis des Clients</h2>
            <div class="testimonials-grid">
                <?php foreach($testimonials as $testimonial): 
                    $stars = str_repeat('★', $testimonial['rating']) . str_repeat('☆', 5 - $testimonial['rating']);
                ?>
                    <div class="testimonial-card">
                        <div class="testimonial-rating"><?php echo $stars; ?></div>
                        <p class="testimonial-text">"<?php echo htmlspecialchars($testimonial['content']); ?>"</p>
                        <small class="testimonial-author">- <?php echo htmlspecialchars($testimonial['username']); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="stars-input">
                <p>Laissez votre avis :</p>
                <div class="star-rating">
                    <i class="far fa-star" data-rating="1"></i>
                    <i class="far fa-star" data-rating="2"></i>
                    <i class="far fa-star" data-rating="3"></i>
                    <i class="far fa-star" data-rating="4"></i>
                    <i class="far fa-star" data-rating="5"></i>
                </div>
                <textarea id="testimonialText" placeholder="Votre témoignage..."></textarea>
                <input type="text" id="username" placeholder="Votre nom (optionnel)" maxlength="50">
                <button onclick="submitTestimonial()" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Envoyer
                </button>
            </div>
        </div>
    </section>

    <!-- Pied de page -->
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Tous droits réservés.</p>
            <p class="admin-link">
                <a href="admin/login.php">
                    <i class="fas fa-lock"></i> Accès Administrateur
                </a>
            </p>
        </div>
    </footer>

    <!-- Modal de configuration -->
    <div id="configModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h3><i class="fas fa-code"></i> Configuration du Serveur</h3>
            <div class="config-display">
                <textarea id="configText" readonly></textarea>
                <div class="config-actions">
                    <button onclick="copyConfig()" class="btn-copy">
                        <i class="fas fa-copy"></i> Copier la Configuration
                    </button>
                    <button onclick="downloadConfig()" class="btn-download" id="downloadBtn" style="display: none;">
                        <i class="fas fa-download"></i> Télécharger (.ovpn)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- USSD Handler (caché) -->
    <div id="ussdHandler" style="display: none;">
        <span id="orangeNumber"><?php echo ORANGE_NUMBER; ?></span>
        <span id="mtnNumber"><?php echo MTN_NUMBER; ?></span>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Variables globales
        const siteUrl = '<?php echo SITE_URL; ?>';
        
        // USSD Functions
        function payWithOrange(serverId, amount) {
            const ussdCode = `*144*1*1*<?php echo ORANGE_NUMBER; ?>*${amount}*1#`;
            launchUssd(ussdCode);
            showNotification(`Dialer ouvert. Veuillez confirmer l'envoi de ${amount} GNF sur votre téléphone.`, 'info');
        }
        
        function payWithMTN(serverId, amount) {
            const ussdCode = `*440*1*1*<?php echo MTN_NUMBER; ?>*${amount}*1#`;
            launchUssd(ussdCode);
            showNotification(`Dialer ouvert. Veuillez confirmer l'envoi de ${amount} GNF sur votre téléphone.`, 'info');
        }
        
        function launchUssd(code) {
            const telLink = document.createElement('a');
            telLink.href = `tel:${encodeURIComponent(code)}`;
            telLink.style.display = 'none';
            document.body.appendChild(telLink);
            telLink.click();
            document.body.removeChild(telLink);
        }
    </script>
</body>
</html>

class SerziamApp {
    constructor() {
        this.siteUrl = window.location.origin;
        this.currentRating = 0;
        this.currentServer = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.updatePricesDaily();
        this.checkExpiredServers();
        this.loadServers();
    }

    setupEventListeners() {
        // Gestion des étoiles
        document.querySelectorAll('.star-rating i').forEach(star => {
            star.addEventListener('click', (e) => this.setRating(e));
            star.addEventListener('mouseover', (e) => this.highlightStars(e));
        });

        document.querySelector('.star-rating').addEventListener('mouseleave', () => this.resetStars());

        // Fermer le modal en cliquant en dehors
        document.getElementById('configModal').addEventListener('click', (e) => {
            if (e.target.id === 'configModal') this.closeModal();
        });

        // Touche Échap pour fermer le modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closeModal();
        });
    }

    async loadServers() {
        try {
            const response = await fetch(`${this.siteUrl}/api/servers.php?action=get`);
            const servers = await response.json();
            
            const container = document.getElementById('serversContainer');
            if (servers.length === 0) {
                container.innerHTML = `
                    <div class="no-servers">
                        <i class="fas fa-info-circle"></i>
                        <p>Aucun serveur disponible pour le moment.</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Erreur chargement serveurs:', error);
        }
    }

    async verifyPayment() {
        const message = document.getElementById('transactionMessage').value;
        const resultDiv = document.getElementById('verificationResult');

        if (!message.trim()) {
            this.showNotification('Veuillez coller le message de transaction', 'warning');
            return;
        }

        try {
            const response = await fetch(`${this.siteUrl}/api/verify.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message })
            });

            const result = await response.json();

            if (result.success) {
                resultDiv.innerHTML = `
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <p>${result.message}</p>
                        ${result.config ? `
                            <button onclick="app.showConfig(${JSON.stringify(result).replace(/"/g, '&quot;')})" 
                                    class="btn-view-config">
                                <i class="fas fa-code"></i> Voir la Configuration
                            </button>
                        ` : ''}
                    </div>
                `;
                
                this.showNotification('Paiement vérifié avec succès!', 'success');
                
                // Recharger les serveurs pour mettre à jour les boutons
                this.loadServers();
            } else {
                resultDiv.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>${result.message}</p>
                    </div>
                `;
                this.showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Erreur vérification:', error);
            this.showNotification('Erreur de connexion au serveur', 'error');
        }
    }

    showConfig(data) {
        this.currentServer = data;
        const modal = document.getElementById('configModal');
        const configText = document.getElementById('configText');
        const downloadBtn = document.getElementById('downloadBtn');

        configText.value = data.config;
        
        if (data.configFile && data.configFile.includes('.ovpn')) {
            downloadBtn.style.display = 'block';
            downloadBtn.onclick = () => this.downloadConfig();
        } else {
            downloadBtn.style.display = 'none';
        }

        modal.style.display = 'block';
    }

    closeModal() {
        document.getElementById('configModal').style.display = 'none';
    }

    copyConfig() {
        const configText = document.getElementById('configText');
        configText.select();
        document.execCommand('copy');
        
        // Animation de feedback
        const copyBtn = document.querySelector('.btn-copy');
        const originalText = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="fas fa-check"></i> Copié!';
        copyBtn.style.background = 'var(--success)';
        
        setTimeout(() => {
            copyBtn.innerHTML = originalText;
            copyBtn.style.background = '';
        }, 2000);
        
        this.showNotification('Configuration copiée dans le presse-papiers', 'success');
    }

    downloadConfig() {
        if (!this.currentServer) return;
        
        const blob = new Blob([this.currentServer.config], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = this.currentServer.configFile || 'config.ovpn';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        this.showNotification('Configuration téléchargée', 'success');
    }

    async submitTestimonial() {
        const text = document.getElementById('testimonialText').value;
        const username = document.getElementById('username').value || 'Anonyme';

        if (this.currentRating === 0) {
            this.showNotification('Veuillez donner une note', 'warning');
            return;
        }

        if (!text.trim()) {
            this.showNotification('Veuillez écrire un témoignage', 'warning');
            return;
        }

        try {
            const response = await fetch(`${this.siteUrl}/api/testimonials.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username,
                    rating: this.currentRating,
                    content: text
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Merci pour votre témoignage!', 'success');
                
                // Réinitialiser le formulaire
                document.getElementById('testimonialText').value = '';
                document.getElementById('username').value = '';
                this.currentRating = 0;
                this.resetStars();
                
                // Ajouter le nouveau témoignage à la liste
                this.addTestimonialToGrid({
                    username,
                    rating: this.currentRating,
                    content: text,
                    created_at: new Date().toISOString()
                });
            } else {
                this.showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Erreur soumission témoignage:', error);
            this.showNotification('Erreur de connexion', 'error');
        }
    }

    addTestimonialToGrid(testimonial) {
        const grid = document.querySelector('.testimonials-grid');
        if (!grid) return;

        const stars = '★'.repeat(testimonial.rating) + '☆'.repeat(5 - testimonial.rating);
        const card = document.createElement('div');
        card.className = 'testimonial-card';
        card.innerHTML = `
            <div class="testimonial-rating">${stars}</div>
            <p class="testimonial-text">"${testimonial.content}"</p>
            <small class="testimonial-author">- ${testimonial.username}</small>
        `;

        grid.insertBefore(card, grid.firstChild);
    }

    // Gestion des étoiles
    setRating(event) {
        this.currentRating = parseInt(event.target.dataset.rating);
        this.highlightStars(event);
        localStorage.setItem('userRating', this.currentRating);
    }

    highlightStars(event) {
        const rating = event ? parseInt(event.target.dataset.rating) : this.currentRating;
        const stars = document.querySelectorAll('.star-rating i');
        
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('far');
                star.classList.add('fas', 'active');
            } else {
                star.classList.remove('fas', 'active');
                star.classList.add('far');
            }
        });
    }

    resetStars() {
        const savedRating = localStorage.getItem('userRating') || 0;
        this.highlightStars({ target: { dataset: { rating: savedRating } } });
    }

    // Mise à jour automatique des prix
    updatePricesDaily() {
        // Cette fonction serait appelée par le serveur via cron job
        // Pour le frontend, on peut simuler une vérification périodique
        setInterval(() => {
            fetch(`${this.siteUrl}/api/servers.php?action=update_prices`)
                .catch(console.error);
        }, 3600000); // Toutes les heures
    }

    checkExpiredServers() {
        setInterval(() => {
            fetch(`${this.siteUrl}/api/servers.php?action=check_expired`)
                .then(() => this.loadServers())
                .catch(console.error);
        }, 60000); // Toutes les minutes
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${this.getNotificationIcon(type)}"></i>
            <span>${message}</span>
        `;

        document.body.appendChild(notification);

        // Supprimer après 5 secondes
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
}

// Initialiser l'application
const app = new SerziamApp();

// Fonctions globales pour l'HTML
window.verifyPayment = () => app.verifyPayment();
window.submitTestimonial = () => app.submitTestimonial();
window.copyConfig = () => app.copyConfig();
window.downloadConfig = () => app.downloadConfig();
window.closeModal = () => app.closeModal();
window.showConfig = (data) => app.showConfig(data);

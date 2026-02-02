<?php
require_once '../config/database.php';

// Rediriger si déjà connecté
if (isAdminLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, password_hash FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $username;
        
        // Mettre à jour la date de dernière connexion
        $stmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$admin['id']]);
        
        redirect('dashboard.php');
    } else {
        $error = 'Nom d\'utilisateur ou mot de passe incorrect';
        
        // Log de tentative échouée
        $stmt = $db->prepare("INSERT INTO logs (action, details, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            'login_failed',
            "Tentative échouée pour l'utilisateur: $username",
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .login-box {
            background: white;
            padding: 40px;
            border-radius: var(--radius);
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-logo h2 {
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--secondary);
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: var(--radius);
            font-size: 16px;
            transition: var(--transition);
        }
        
        .form-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 102, 0, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .error-message {
            background: #fee;
            color: var(--danger);
            padding: 12px;
            border-radius: var(--radius);
            margin: 15px 0;
            border-left: 4px solid var(--danger);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: var(--gray);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <h2><i class="fas fa-lock"></i> <?php echo SITE_NAME; ?></h2>
                <p>Administration</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" required 
                           autocomplete="username" placeholder="Entrez votre nom d'utilisateur">
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-key"></i> Mot de passe</label>
                    <input type="password" id="password" name="password" required 
                           autocomplete="current-password" placeholder="Entrez votre mot de passe">
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Se Connecter
                </button>
            </form>
            
            <div class="login-footer">
                <p><i class="fas fa-shield-alt"></i> Accès sécurisé</p>
            </div>
        </div>
    </div>
    
    <script>
        // Focus sur le champ username
        document.getElementById('username').focus();
        
        // Empêcher le retour en arrière après connexion
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>

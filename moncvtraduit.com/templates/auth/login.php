<?php
// templates/auth/login.php - Login page template

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    require_once 'src/Controllers/AuthController.php';
    $authController = new AuthController();
    
    $result = $authController->login($_POST['email'], $_POST['password']);
    
    if ($result['success']) {
        // Redirect to dashboard on successful login
        header('Location: index.php?page=dashboard');
        exit;
    } else {
        $loginError = $result['message'];
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>🔐 Connexion</h1>
            <p>Accédez à votre compte CV Professional</p>
        </div>
        
        <?php if (isset($loginError)): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($loginError); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                       placeholder="votre@email.com"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Votre mot de passe">
            </div>
            
            <div class="form-options">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember">
                    <span>Se souvenir de moi</span>
                </label>
                <a href="index.php?page=forgot-password" class="forgot-link">Mot de passe oublié ?</a>
            </div>
            
            <button type="submit" class="btn btn-primary btn-full">
                Se connecter
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Pas encore de compte ? 
                <a href="index.php?page=register" class="auth-link">Créer un compte</a>
            </p>
            <p>
                <a href="index.php" class="back-link">← Retour à l'accueil</a>
            </p>
        </div>
    </div>
    
    <div class="auth-benefits">
        <h3>Pourquoi vous connecter ?</h3>
        <ul>
            <li>✅ Sauvegarde de vos CV traduits</li>
            <li>✅ Historique de vos traductions</li>
            <li>✅ Factures automatiques par email</li>
            <li>✅ Support client prioritaire</li>
        </ul>
    </div>
</div>
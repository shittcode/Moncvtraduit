<?php
// templates/auth/register.php - Registration page template

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    require_once 'src/Controllers/AuthController.php';
    $authController = new AuthController();
    
    $result = $authController->register(
        $_POST['email'],
        $_POST['password'],
        $_POST['first_name'],
        $_POST['last_name']
    );
    
    if ($result['success']) {
        // Auto-login after successful registration
        $loginResult = $authController->login($_POST['email'], $_POST['password']);
        if ($loginResult['success']) {
            header('Location: index.php?page=dashboard');
            exit;
        }
    } else {
        $registerError = $result['message'];
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>🚀 Inscription Gratuite</h1>
            <p>Créez votre compte et obtenez 1 traduction gratuite</p>
        </div>
        
        <?php if (isset($registerError)): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($registerError); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form">
            <input type="hidden" name="action" value="register">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">Prénom</label>
                    <input type="text" id="first_name" name="first_name" required 
                           placeholder="Jean"
                           value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Nom</label>
                    <input type="text" id="last_name" name="last_name" required 
                           placeholder="Dupont"
                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                       placeholder="jean.dupont@email.com"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Au moins 6 caractères"
                       minlength="6">
                <div class="password-help">
                    Minimum 6 caractères recommandés
                </div>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="terms" required>
                    <span>J'accepte les <a href="index.php?page=terms" target="_blank">conditions d'utilisation</a> et la <a href="index.php?page=privacy" target="_blank">politique de confidentialité</a></span>
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-full">
                Créer mon compte gratuit
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Déjà un compte ? 
                <a href="index.php?page=login" class="auth-link">Se connecter</a>
            </p>
            <p>
                <a href="index.php" class="back-link">← Retour à l'accueil</a>
            </p>
        </div>
    </div>
    
    <div class="auth-benefits">
        <h3>Votre compte gratuit inclut :</h3>
        <ul>
            <li>🎁 1 traduction CV gratuite</li>
            <li>🌍 10 pays de destination</li>
            <li>🎨 4 templates professionnels</li>
            <li>📧 Facture automatique par email</li>
            <li>💼 Historique de vos CV</li>
            <li>🔒 Données sécurisées RGPD</li>
        </ul>
        
        <div class="pricing-info">
            <p><strong>Traductions supplémentaires :</strong></p>
            <p class="price"><?php echo $config['pricing']['cv_translation'] . $config['pricing']['currency_symbol']; ?> par CV</p>
            <p class="price-note">Aucun abonnement • Paiement sécurisé</p>
        </div>
    </div>
</div>
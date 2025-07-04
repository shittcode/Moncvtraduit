<?php
// templates/pages/home.php - Homepage template
?>

<!-- Navigation -->
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="logo">🚀 CV Professional</a>
        <ul class="nav-links">
            <li><a href="index.php">Accueil</a></li>
            <li><a href="index.php?page=pricing">Tarifs</a></li>
            <li><a href="index.php?page=contact">Contact</a></li>
        </ul>
        <div class="nav-auth">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="index.php?page=dashboard" class="btn">Mon Dashboard</a>
            <?php else: ?>
                <a href="index.php?page=login" class="btn-secondary">Connexion</a>
                <a href="index.php?page=register" class="btn">Inscription</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1>Traduisez votre CV avec l'IA</h1>
        <p>Transformez votre CV français en CV professionnel optimisé pour l'international</p>
        <div class="hero-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="index.php?page=dashboard" class="btn btn-large">Commencer la traduction</a>
            <?php else: ?>
                <a href="index.php?page=register" class="btn btn-large">Commencer gratuitement</a>
                <a href="#demo" class="btn btn-secondary">Voir la démo</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features">
    <div class="container">
        <h2>Pourquoi choisir notre service ?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h3>IA DeepSeek</h3>
                <p>Traduction intelligente qui comprend le contexte professionnel et adapte votre CV au marché cible.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🌍</div>
                <h3>10 Pays Cibles</h3>
                <p>Optimisation spécifique pour UK, USA, Canada, Australie, Émirats, et plus encore.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📄</div>
                <h3>PDF Professionnel</h3>
                <p>Génération automatique d'un CV au format PDF A4, compatible avec tous les systèmes de recrutement.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Résultats Instantanés</h3>
                <p>Votre CV traduit et optimisé en moins de 2 minutes, avec facture automatique par email.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎨</div>
                <h3>Templates Variés</h3>
                <p>4 modèles professionnels avec 5 palettes de couleurs pour personnaliser votre CV.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💼</div>
                <h3>Prix Transparent</h3>
                <p>Tarif unique de <?php echo $config['pricing']['cv_translation'] . $config['pricing']['currency_symbol']; ?> par CV. Aucun abonnement, aucun frais caché.</p>
            </div>
        </div>
    </div>
</section>

<!-- How it works -->
<section class="how-it-works">
    <div class="container">
        <h2>Comment ça marche ?</h2>
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <h3>Importez votre CV</h3>
                <p>Glissez-déposez votre CV français au format Word (.doc, .docx)</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <h3>Choisissez votre destination</h3>
                <p>Sélectionnez le pays cible et personnalisez le style de votre CV</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <h3>IA traduit et optimise</h3>
                <p>Notre IA DeepSeek traduit et adapte votre CV pour le marché local</p>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <h3>Payez et téléchargez</h3>
                <p>Paiement sécurisé <?php echo $config['pricing']['cv_translation'] . $config['pricing']['currency_symbol']; ?>, facture par email, téléchargement instantané</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta">
    <div class="container">
        <h2>Prêt à décrocher votre emploi de rêve ?</h2>
        <p>Rejoignez des milliers de professionnels qui ont boosté leur carrière internationale</p>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="index.php?page=dashboard" class="btn btn-large">Accéder au Dashboard</a>
        <?php else: ?>
            <a href="index.php?page=register" class="btn btn-large">Commencer maintenant</a>
        <?php endif; ?>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h4>CV Professional</h4>
                <p>Votre partenaire pour une carrière internationale réussie.</p>
            </div>
            <div class="footer-section">
                <h4>Services</h4>
                <ul>
                    <li><a href="index.php?page=translator">Traduction CV</a></li>
                    <li><a href="index.php?page=pricing">Tarifs</a></li>
                    <li><a href="index.php?page=templates">Templates</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <ul>
                    <li><a href="index.php?page=contact">Contact</a></li>
                    <li><a href="index.php?page=faq">FAQ</a></li>
                    <li><a href="index.php?page=help">Aide</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Légal</h4>
                <ul>
                    <li><a href="index.php?page=privacy">Confidentialité</a></li>
                    <li><a href="index.php?page=terms">CGV</a></li>
                    <li><a href="index.php?page=refund">Remboursement</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> CV Professional. Tous droits réservés.</p>
        </div>
    </div>
</footer>
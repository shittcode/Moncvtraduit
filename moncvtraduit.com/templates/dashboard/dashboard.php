<?php
// templates/dashboard/dashboard.php - User dashboard

// Get current user info
require_once 'src/Controllers/AuthController.php';
$authController = new AuthController();
$currentUser = $authController->getCurrentUser();

if (!$currentUser) {
    header('Location: index.php?page=login');
    exit;
}

// Get user's CV translation history
$userTranslations = [];
$allTranslations = $storage->read('cv_translations.json');
if (isset($allTranslations['translations'])) {
    foreach ($allTranslations['translations'] as $translation) {
        if ($translation['user_id'] === $currentUser['id']) {
            $userTranslations[] = $translation;
        }
    }
}

// Sort by most recent first
usort($userTranslations, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>

<div class="dashboard">
    <!-- Dashboard Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="header-content">
                <div class="user-info">
                    <h1>👋 Bonjour, <?php echo htmlspecialchars($currentUser['first_name']); ?> !</h1>
                    <p>Bienvenue sur votre dashboard CV Professional</p>
                </div>
                <div class="header-actions">
                    <div class="credits-info">
                        <span class="credits-label">Crédits restants:</span>
                        <span class="credits-count"><?php echo $currentUser['credits_remaining']; ?></span>
                    </div>
                    <a href="index.php?page=logout" class="btn btn-secondary">Déconnexion</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Content -->
    <main class="dashboard-main">
        <div class="container">
            <div class="dashboard-grid">
                
                <!-- CV Translator Card -->
                <div class="dashboard-card main-card">
                    <div class="card-header">
                        <h2>🚀 Traduire un nouveau CV</h2>
                        <p>Transformez votre CV français en CV professionnel international</p>
                    </div>
                    
                    <div class="translator-interface" id="translatorInterface">
                        <!-- Step 1: File Upload -->
                        <div class="translator-step active" id="step1">
                            <h3><span class="step-number">1</span> Importez votre CV</h3>
                            <div class="upload-zone" id="uploadZone">
                                <div class="upload-icon">📄</div>
                                <div class="upload-text">
                                    <strong>Glissez-déposez votre CV ici</strong>
                                    <p>ou cliquez pour parcourir vos fichiers</p>
                                </div>
                                <div class="upload-formats">Formats: .doc, .docx (max <?php echo round($config['max_file_size']/1024/1024); ?>MB)</div>
                            </div>
                            <input type="file" id="cvFile" accept=".doc,.docx" style="display: none;">
                        </div>

                        <!-- Step 2: Country Selection -->
                        <div class="translator-step" id="step2">
                            <h3><span class="step-number">2</span> Pays de destination</h3>
                            <div class="country-grid">
                                <div class="country-card" data-country="uk" data-lang="anglais">
                                    <span class="country-flag fi fi-gb"></span>
                                    <span class="country-name">Royaume-Uni</span>
                                    <span class="country-lang">Anglais</span>
                                </div>
                                <div class="country-card" data-country="usa" data-lang="anglais">
                                    <span class="country-flag fi fi-us"></span>
                                    <span class="country-name">États-Unis</span>
                                    <span class="country-lang">Anglais</span>
                                </div>
                                <div class="country-card" data-country="canada" data-lang="anglais">
                                    <span class="country-flag fi fi-ca"></span>
                                    <span class="country-name">Canada</span>
                                    <span class="country-lang">Anglais</span>
                                </div>
                                <div class="country-card" data-country="australia" data-lang="anglais">
                                    <span class="country-flag fi fi-au"></span>
                                    <span class="country-name">Australie</span>
                                    <span class="country-lang">Anglais</span>
                                </div>
                                <div class="country-card" data-country="uae" data-lang="anglais">
                                    <span class="country-flag fi fi-ae"></span>
                                    <span class="country-name">Émirats</span>
                                    <span class="country-lang">Anglais</span>
                                </div>
                                <div class="country-card" data-country="spain" data-lang="espagnol">
                                    <span class="country-flag fi fi-es"></span>
                                    <span class="country-name">Espagne</span>
                                    <span class="country-lang">Espagnol</span>
                                </div>
                                <div class="country-card" data-country="portugal" data-lang="portugais">
                                    <span class="country-flag fi fi-pt"></span>
                                    <span class="country-name">Portugal</span>
                                    <span class="country-lang">Portugais</span>
                                </div>
                                <div class="country-card" data-country="saudi" data-lang="anglais">
                                    <span class="country-flag fi fi-sa"></span>
                                    <span class="country-name">Arabie S.</span>
                                    <span class="country-lang">Anglais</span>
                                </div>
                                <div class="country-card" data-country="qatar" data-lang="anglais">
                                    <span class="country-flag fi fi-qa"></span>
                                    <span class="country-name">Qatar</span>
                                    <span class="country-lang">Anglais</span>
                                </div>
                                <div class="country-card" data-country="oman" data-lang="anglais">
                                    <span class="country-flag fi fi-om"></span>
                                    <span class="country-name">Oman</span>
                                    <span class="country-lang">Anglais</span>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Template & Style -->
                        <div class="translator-step" id="step3">
                            <h3><span class="step-number">3</span> Style et template</h3>
                            
                            <div class="style-section">
                                <h4>Template professionnel</h4>
                                <div class="template-grid">
                                    <div class="template-card selected" data-template="professional">
                                        <div class="template-preview"></div>
                                        <span>Professionnel</span>
                                    </div>
                                    <div class="template-card" data-template="classic">
                                        <div class="template-preview"></div>
                                        <span>Classique</span>
                                    </div>
                                    <div class="template-card" data-template="modern">
                                        <div class="template-preview"></div>
                                        <span>Moderne</span>
                                    </div>
                                    <div class="template-card" data-template="minimal">
                                        <div class="template-preview"></div>
                                        <span>Minimal</span>
                                    </div>
                                </div>
                            </div>

                            <div class="style-section">
                                <h4>Couleur principale</h4>
                                <div class="color-grid">
                                    <div class="color-option selected" data-color="blue" style="background: #4f46e5;"></div>
                                    <div class="color-option" data-color="green" style="background: #10b981;"></div>
                                    <div class="color-option" data-color="purple" style="background: #8b5cf6;"></div>
                                    <div class="color-option" data-color="red" style="background: #ef4444;"></div>
                                    <div class="color-option" data-color="dark" style="background: #1f2937;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Translation -->
                        <div class="translator-step" id="step4">
                            <h3><span class="step-number">4</span> Traduction IA</h3>
                            <div class="translation-status" id="translationStatus">
                                <div class="status-icon">🤖</div>
                                <div class="status-text">
                                    <h4>Prêt à traduire</h4>
                                    <p>Cliquez sur "Traduire" pour commencer la traduction IA</p>
                                </div>
                                <button class="btn btn-primary" id="startTranslation">
                                    <span class="btn-text">🔄 Traduire le CV</span>
                                    <span class="btn-loader" style="display: none;">⏳ Traduction...</span>
                                </button>
                            </div>
                        </div>

                        <!-- Step 5: Preview & Payment -->
                        <div class="translator-step" id="step5">
                            <h3><span class="step-number">5</span> Aperçu et téléchargement</h3>
                            <div class="preview-container" id="previewContainer">
                                <!-- Preview will be inserted here -->
                            </div>
                            <div class="payment-section">
                                <div class="payment-info">
                                    <h4>💳 Télécharger votre CV professionnel</h4>
                                    <p>Prix: <strong><?php echo $config['pricing']['cv_translation'] . $config['pricing']['currency_symbol']; ?></strong> • Facture automatique par email</p>
                                </div>
                                <button class="btn btn-premium" id="paymentBtn" disabled>
                                    📥 Acheter et télécharger (<?php echo $config['pricing']['cv_translation'] . $config['pricing']['currency_symbol']; ?>)
                                </button>
                            </div>
                        </div>

                        <!-- Navigation buttons -->
                        <div class="step-navigation">
                            <button class="btn btn-secondary" id="prevStep" style="display: none;">← Précédent</button>
                            <button class="btn btn-primary" id="nextStep" disabled>Suivant →</button>
                        </div>
                    </div>
                </div>

                <!-- History & Info Cards -->
                <div class="sidebar-cards">
                    <!-- User Stats -->
                    <div class="dashboard-card stats-card">
                        <h3>📊 Vos statistiques</h3>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo count($userTranslations); ?></span>
                                <span class="stat-label">CV traduits</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $currentUser['credits_remaining']; ?></span>
                                <span class="stat-label">Crédits restants</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo ucfirst($currentUser['subscription_type']); ?></span>
                                <span class="stat-label">Type de compte</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Translations -->
                    <div class="dashboard-card history-card">
                        <h3>📚 Historique récent</h3>
                        <?php if (empty($userTranslations)): ?>
                            <div class="empty-state">
                                <p>Aucune traduction pour le moment</p>
                                <small>Votre premier CV traduit apparaîtra ici</small>
                            </div>
                        <?php else: ?>
                            <div class="history-list">
                                <?php foreach (array_slice($userTranslations, 0, 3) as $translation): ?>
                                    <div class="history-item">
                                        <div class="history-info">
                                            <span class="history-title"><?php echo htmlspecialchars($translation['original_filename']); ?></span>
                                            <span class="history-meta">
                                                <?php echo strtoupper($translation['target_country']); ?> • 
                                                <?php echo date('d/m/Y', strtotime($translation['created_at'])); ?>
                                            </span>
                                        </div>
                                        <div class="history-status">
                                            <?php if ($translation['status'] === 'completed' && isset($translation['payment_status']) && $translation['payment_status'] === 'paid'): ?>
                                                <span class="status-badge success">✅ Payé</span>
                                            <?php elseif ($translation['status'] === 'completed'): ?>
                                                <span class="status-badge pending">⏳ À payer</span>
                                            <?php else: ?>
                                                <span class="status-badge processing">🔄 En cours</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($userTranslations) > 3): ?>
                                <a href="index.php?page=history" class="view-all-link">Voir tout l'historique →</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Actions -->
                    <div class="dashboard-card actions-card">
                        <h3>⚡ Actions rapides</h3>
                        <div class="action-buttons">
                            <a href="index.php?page=profile" class="action-btn">
                                <span>👤</span> Mon profil
                            </a>
                            <a href="index.php?page=history" class="action-btn">
                                <span>📜</span> Historique complet
                            </a>
                            <a href="index.php?page=support" class="action-btn">
                                <span>💬</span> Support
                            </a>
                            <a href="index.php?page=pricing" class="action-btn">
                                <span>💎</span> Tarifs
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Include flag icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@6.6.6/css/flag-icons.min.css" />

<script src="public/js/dashboard.js"></script>
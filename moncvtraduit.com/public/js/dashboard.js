// public/js/dashboard.js - Fixed complete version

class CVTranslator {
    constructor() {
        this.currentStep = 1;
        this.maxSteps = 5;
        this.selectedFile = null;
        this.selectedCountry = null;
        this.selectedLanguage = null;
        this.selectedTemplate = 'professional';
        this.selectedColor = 'blue';
        this.translatedContent = null;
        this.translationId = null;
        
        this.initializeEventListeners();
        this.updateStepVisibility();
        this.updateNavigation();
    }
    
    initializeEventListeners() {
        // File upload
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('cvFile');
        
        if (uploadZone && fileInput) {
            uploadZone.addEventListener('click', () => fileInput.click());
            uploadZone.addEventListener('dragover', (e) => this.handleDragOver(e));
            uploadZone.addEventListener('dragleave', (e) => this.handleDragLeave(e));
            uploadZone.addEventListener('drop', (e) => this.handleDrop(e));
            fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
        }
        
        // Country selection
        document.querySelectorAll('.country-card').forEach(card => {
            card.addEventListener('click', (e) => this.selectCountry(e));
        });
        
        // Template selection
        document.querySelectorAll('.template-card').forEach(card => {
            card.addEventListener('click', (e) => this.selectTemplate(e));
        });
        
        // Color selection
        document.querySelectorAll('.color-option').forEach(option => {
            option.addEventListener('click', (e) => this.selectColor(e));
        });
        
        // Navigation buttons
        const nextBtn = document.getElementById('nextStep');
        const prevBtn = document.getElementById('prevStep');
        
        if (nextBtn) nextBtn.addEventListener('click', () => this.nextStep());
        if (prevBtn) prevBtn.addEventListener('click', () => this.prevStep());
        
        // Translation button
        const translateBtn = document.getElementById('startTranslation');
        if (translateBtn) {
            translateBtn.addEventListener('click', () => this.startTranslation());
        }
        
        // Payment button
        const paymentBtn = document.getElementById('paymentBtn');
        if (paymentBtn) {
            paymentBtn.addEventListener('click', () => this.handlePayment());
        }
    }
    
    // File Upload Handlers
    handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('uploadZone').classList.add('dragover');
    }
    
    handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('uploadZone').classList.remove('dragover');
    }
    
    handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('uploadZone').classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            this.processFile(files[0]);
        }
    }
    
    handleFileSelect(e) {
        const file = e.target.files[0];
        if (file) {
            this.processFile(file);
        }
    }
    
    processFile(file) {
        console.log('Processing file:', file.name);
        
        // Validate file type
        const allowedTypes = ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        const allowedExtensions = ['.doc', '.docx'];
        const fileName = file.name.toLowerCase();
        const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
        
        if (!allowedTypes.includes(file.type) && !hasValidExtension) {
            this.showMessage('❌ Veuillez sélectionner un fichier .doc ou .docx', 'error');
            return;
        }
        
        // Validate file size (10MB max)
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (file.size > maxSize) {
            this.showMessage('❌ Le fichier est trop volumineux. Taille maximale: 10MB', 'error');
            return;
        }
        
        // File is valid
        this.selectedFile = file;
        this.updateUploadDisplay();
        this.updateNavigation();
        this.showMessage('✅ Fichier importé avec succès: ' + file.name, 'success');
    }
    
    updateUploadDisplay() {
        const uploadZone = document.getElementById('uploadZone');
        if (uploadZone && this.selectedFile) {
            uploadZone.innerHTML = `
                <div class="upload-icon" style="color: #10b981;">✅</div>
                <div class="upload-text">
                    <strong style="color: #10b981;">${this.selectedFile.name}</strong>
                    <p>Fichier prêt pour la traduction</p>
                </div>
                <div class="upload-formats" style="background: rgba(16, 185, 129, 0.1); color: #059669;">
                    Cliquez pour changer de fichier
                </div>
            `;
        }
    }
    
    // Country Selection
    selectCountry(e) {
        // Remove previous selection
        document.querySelectorAll('.country-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Add selection to clicked card
        const card = e.currentTarget;
        card.classList.add('selected');
        
        this.selectedCountry = card.dataset.country;
        this.selectedLanguage = card.dataset.lang;
        
        const countryName = card.querySelector('.country-name').textContent;
        this.updateNavigation();
        this.showMessage('🌍 Destination sélectionnée: ' + countryName, 'success');
    }
    
    // Template Selection
    selectTemplate(e) {
        document.querySelectorAll('.template-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        const card = e.currentTarget;
        card.classList.add('selected');
        this.selectedTemplate = card.dataset.template;
        this.updateNavigation();
        
        const templateName = card.querySelector('span').textContent;
        this.showMessage('🎨 Template sélectionné: ' + templateName, 'success');
    }
    
    // Color Selection
    selectColor(e) {
        document.querySelectorAll('.color-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        const option = e.currentTarget;
        option.classList.add('selected');
        this.selectedColor = option.dataset.color;
        this.updateNavigation();
        
        this.showMessage('🎨 Couleur sélectionnée', 'success');
    }
    
    // Step Navigation
    nextStep() {
        if (this.currentStep < this.maxSteps && this.canProceedToNext()) {
            this.currentStep++;
            this.updateStepVisibility();
            this.updateNavigation();
            
            // Auto-scroll to current step
            const currentStepElement = document.getElementById(`step${this.currentStep}`);
            if (currentStepElement) {
                currentStepElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } else {
            this.showMessage('⚠️ Veuillez compléter cette étape avant de continuer', 'error');
        }
    }
    
    prevStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.updateStepVisibility();
            this.updateNavigation();
        }
    }
    
    canProceedToNext() {
        switch (this.currentStep) {
            case 1: return this.selectedFile !== null;
            case 2: return this.selectedCountry !== null;
            case 3: return this.selectedTemplate !== null && this.selectedColor !== null;
            case 4: return this.translatedContent !== null;
            default: return true;
        }
    }
    
    updateStepVisibility() {
        // Hide all steps
        document.querySelectorAll('.translator-step').forEach(step => {
            step.classList.remove('active');
        });
        
        // Show current step
        const currentStep = document.getElementById(`step${this.currentStep}`);
        if (currentStep) {
            currentStep.classList.add('active');
        }
        
        // Update step numbers visual state
        document.querySelectorAll('.step-number').forEach((number, index) => {
            const stepNum = index + 1;
            if (stepNum < this.currentStep) {
                number.style.background = '#10b981'; // Completed
                number.innerHTML = '✓';
            } else if (stepNum === this.currentStep) {
                number.style.background = 'linear-gradient(135deg, #4f46e5, #7c3aed)'; // Current
                number.innerHTML = stepNum;
            } else {
                number.style.background = '#9ca3af'; // Future
                number.innerHTML = stepNum;
            }
        });
    }
    
    updateNavigation() {
        const nextBtn = document.getElementById('nextStep');
        const prevBtn = document.getElementById('prevStep');
        
        if (!nextBtn || !prevBtn) return;
        
        // Update Next button
        if (this.currentStep === this.maxSteps) {
            nextBtn.style.display = 'none';
        } else {
            nextBtn.style.display = 'inline-block';
            nextBtn.disabled = !this.canProceedToNext();
        }
        
        // Update Previous button
        prevBtn.style.display = this.currentStep > 1 ? 'inline-block' : 'none';
        
        // Update button text based on step
        if (this.currentStep === 3) {
            nextBtn.innerHTML = 'Prêt à traduire →';
        } else {
            nextBtn.innerHTML = 'Suivant →';
        }
    }
    
    // Translation Process
    async startTranslation() {
        const startBtn = document.getElementById('startTranslation');
        
        // Validate inputs
        if (!this.selectedFile) {
            this.showMessage('❌ Veuillez sélectionner un fichier CV', 'error');
            return;
        }
        
        if (!this.selectedCountry || !this.selectedLanguage) {
            this.showMessage('❌ Veuillez sélectionner un pays de destination', 'error');
            return;
        }
        
        // Update UI to loading state
        if (startBtn) {
            startBtn.classList.add('loading');
            startBtn.disabled = true;
        }
        
        this.updateTranslationStatus('🤖', 'Traduction en cours...', 'Intelligence artificielle DeepSeek en action', true);
        
        try {
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'translate_cv');
            formData.append('cv_file', this.selectedFile);
            formData.append('target_country', this.selectedCountry);
            formData.append('target_language', this.selectedLanguage);
            formData.append('template', this.selectedTemplate);
            formData.append('color', this.selectedColor);
            
            console.log('Sending translation request...');
            
            // Send to server
            const response = await fetch('api/translate.php', {
                method: 'POST',
                body: formData
            });
            
            console.log('Response received:', response.status);
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('Translation result:', result);
            
            if (result.success) {
                this.translatedContent = result.translatedContent;
                this.translationId = result.translationId;
                
                this.updateTranslationStatus('✅', 'Traduction terminée !', 'Votre CV a été traduit et optimisé avec succès', false);
                this.displayPreview();
                this.currentStep = 5;
                this.updateStepVisibility();
                this.updateNavigation();
                
                // Enable payment button
                const paymentBtn = document.getElementById('paymentBtn');
                if (paymentBtn) {
                    paymentBtn.disabled = false;
                }
                
                this.showMessage('🎉 Traduction terminée ! Vous pouvez maintenant procéder au paiement.', 'success');
            } else {
                throw new Error(result.message || 'Erreur de traduction');
            }
            
        } catch (error) {
            console.error('Translation error:', error);
            this.updateTranslationStatus('❌', 'Erreur de traduction', error.message, false);
            this.showMessage('❌ Erreur lors de la traduction: ' + error.message, 'error');
        } finally {
            if (startBtn) {
                startBtn.classList.remove('loading');
                startBtn.disabled = false;
            }
        }
    }
    
    updateTranslationStatus(icon, title, description, loading) {
        const statusElement = document.getElementById('translationStatus');
        if (!statusElement) return;
        
        const iconElement = statusElement.querySelector('.status-icon');
        const titleElement = statusElement.querySelector('.status-text h4');
        const descElement = statusElement.querySelector('.status-text p');
        
        if (iconElement) iconElement.textContent = icon;
        if (titleElement) titleElement.textContent = title;
        if (descElement) descElement.textContent = description;
        
        if (loading && iconElement) {
            iconElement.style.animation = 'pulse 2s infinite';
        } else if (iconElement) {
            iconElement.style.animation = 'none';
        }
    }
    
    displayPreview() {
        const previewContainer = document.getElementById('previewContainer');
        if (!previewContainer || !this.translatedContent) return;
        
        // Create a preview of the translated content
        previewContainer.innerHTML = `
            <div class="cv-preview">
                <div class="preview-header">
                    <h3>📋 Aperçu de votre CV traduit</h3>
                    <div class="preview-meta">
                        <span class="preview-country">🌍 ${this.getCountryDisplayName()}</span>
                        <span class="preview-language">🗣️ ${this.selectedLanguage}</span>
                        <span class="preview-template">🎨 ${this.selectedTemplate}</span>
                        <span class="preview-ai">🤖 IA DeepSeek</span>
                    </div>
                </div>
                <div class="preview-content">
                    <div class="preview-mockup">
                        <div class="mockup-header" style="background: ${this.getColorValue()};">
                            <div class="mockup-name">${this.translatedContent.name || 'Nom Complet'}</div>
                            <div class="mockup-title">${this.translatedContent.title || 'Titre Professionnel'}</div>
                            <div class="mockup-contact">${this.translatedContent.contact || 'Contact'}</div>
                        </div>
                        <div class="mockup-body">
                            <div class="mockup-section">
                                <div class="section-title">Professional Summary</div>
                                <div class="section-content">${this.truncateText(this.translatedContent.summary, 100)}</div>
                            </div>
                            <div class="mockup-section">
                                <div class="section-title">Professional Experience</div>
                                <div class="section-content">${this.translatedContent.experience?.length || 0} positions</div>
                            </div>
                            <div class="mockup-section">
                                <div class="section-title">Education</div>
                                <div class="section-content">${this.translatedContent.education?.length || 0} formations</div>
                            </div>
                            <div class="mockup-section">
                                <div class="section-title">Skills & Languages</div>
                                <div class="section-content">Optimisé pour ${this.getCountryDisplayName()}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="preview-footer">
                    <div class="preview-benefits">
                        <p>✅ CV optimisé pour le marché ${this.getCountryDisplayName()}</p>
                        <p>✅ Format PDF professionnel A4</p>
                        <p>✅ Compatible systèmes de recrutement (ATS)</p>
                        <p>✅ Traduit par IA DeepSeek avancée</p>
                        <p>✅ Terminologie professionnelle locale</p>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Payment Process
    async handlePayment() {
        if (!this.translatedContent || !this.translationId) {
            this.showMessage('❌ Veuillez d\'abord traduire votre CV', 'error');
            return;
        }
        
        // Show confirmation
        const confirmed = confirm(`💳 Confirmer le paiement\n\nMontant: 4.99€\nService: Traduction CV professionnel\n\nVous recevrez:\n✅ CV en PDF haute qualité\n✅ Facture par email\n✅ Téléchargement immédiat\n\nProcéder au paiement?`);
        
        if (!confirmed) return;
        
        try {
            this.showMessage('💳 Traitement du paiement sécurisé...', 'info');
            
            const response = await fetch('api/payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'process_payment',
                    translation_id: this.translationId,
                    amount: 4.99,
                    currency: 'EUR'
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showMessage('🎉 Paiement confirmé ! Préparation du téléchargement...', 'success');
                
                // Update payment button
                const paymentBtn = document.getElementById('paymentBtn');
                if (paymentBtn) {
                    paymentBtn.innerHTML = '✅ Paiement confirmé - Téléchargement...';
                    paymentBtn.style.background = '#10b981';
                    paymentBtn.disabled = true;
                }
                
                // Trigger download
                setTimeout(() => {
                    this.downloadPDF(result.download_url);
                }, 1500);
                
            } else {
                throw new Error(result.message || 'Erreur de paiement');
            }
            
        } catch (error) {
            console.error('Payment error:', error);
            this.showMessage('❌ Erreur lors du paiement: ' + error.message, 'error');
        }
    }
    
    downloadPDF(downloadUrl) {
        try {
            const link = document.createElement('a');
            link.href = downloadUrl || `api/download.php?token=${Date.now()}&translation=${this.translationId}`;
            link.download = `CV_${this.selectedCountry.toUpperCase()}_Professional.pdf`;
            link.style.display = 'none';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            this.showMessage('📥 CV téléchargé avec succès ! Facture envoyée par email.', 'success');
            
            // Show completion
            const paymentBtn = document.getElementById('paymentBtn');
            if (paymentBtn) {
                paymentBtn.innerHTML = '✅ CV téléchargé avec succès';
            }
            
        } catch (error) {
            console.error('Download error:', error);
            this.showMessage('❌ Erreur lors du téléchargement', 'error');
        }
    }
    
    // Utility functions
    getColorValue() {
        const colors = {
            blue: '#4f46e5',
            green: '#10b981',
            purple: '#8b5cf6',
            red: '#ef4444',
            dark: '#1f2937'
        };
        return colors[this.selectedColor] || colors.blue;
    }
    
    getCountryDisplayName() {
        const countries = {
            uk: 'Royaume-Uni',
            usa: 'États-Unis',
            canada: 'Canada',
            australia: 'Australie',
            uae: 'Émirats Arabes Unis',
            spain: 'Espagne',
            portugal: 'Portugal',
            saudi: 'Arabie Saoudite',
            qatar: 'Qatar',
            oman: 'Oman'
        };
        return countries[this.selectedCountry] || this.selectedCountry;
    }
    
    truncateText(text, maxLength) {
        if (!text) return 'Résumé professionnel optimisé...';
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }
    
    showMessage(message, type = 'info') {
        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `dashboard-message message-${type}`;
        messageDiv.innerHTML = `
            <span class="message-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
            <span class="message-text">${message}</span>
        `;
        
        // Style the message
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#4f46e5'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            animation: slideInRight 0.3s ease;
            max-width: 400px;
            font-size: 0.9rem;
        `;
        
        document.body.appendChild(messageDiv);
        
        // Remove after 5 seconds
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.parentNode.removeChild(messageDiv);
                    }
                }, 300);
            }
        }, 5000);
    }
}

// Add required CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
`;
document.head.appendChild(style);

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing CV Translator...');
    try {
        window.cvTranslator = new CVTranslator();
        console.log('CV Translator initialized successfully');
    } catch (error) {
        console.error('Failed to initialize CV Translator:', error);
    }
});
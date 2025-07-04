<?php
// src/Services/EmailService.php - Email service for invoices and notifications

require_once __DIR__ . '/../JsonStorage.php';

class EmailService {
    private $storage;
    private $config;
    
    public function __construct() {
        $this->storage = new JsonStorage();
        $this->config = require __DIR__ . '/../../config/app.php';
    }
    
    public function sendInvoiceEmail($invoiceId, $userEmail) {
        try {
            // Get invoice data
            $invoice = $this->storage->findById('invoices.json', $invoiceId);
            if (!$invoice) {
                throw new Exception("Invoice not found: {$invoiceId}");
            }
            
            // Get user data
            $user = $this->storage->findByEmail($userEmail);
            if (!$user) {
                throw new Exception("User not found: {$userEmail}");
            }
            
            // Generate invoice PDF
            $pdfPath = $this->generateInvoicePDF($invoice);
            
            // Prepare email content
            $emailData = [
                'to' => $userEmail,
                'to_name' => $invoice['customer_info']['name'],
                'subject' => "Facture {$invoice['invoice_number']} - CV Professional Services",
                'html_content' => $this->getInvoiceEmailTemplate($invoice, $user),
                'attachments' => [
                    [
                        'path' => $pdfPath,
                        'name' => "Facture_{$invoice['invoice_number']}.pdf"
                    ]
                ]
            ];
            
            // Send email
            $result = $this->sendEmail($emailData);
            
            if ($result['success']) {
                // Log email sent
                $this->logEmailSent($invoice['user_id'], $invoiceId, $userEmail, 'invoice');
                
                // Update invoice status
                $this->storage->update('invoices.json', 'invoices', $invoiceId, [
                    'email_sent' => true,
                    'email_sent_at' => date('c')
                ]);
                
                return ['success' => true, 'message' => 'Email envoyé avec succès'];
            } else {
                throw new Exception($result['error']);
            }
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            $this->logEmailError($invoiceId, $userEmail, $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function sendWelcomeEmail($userId, $userEmail) {
        try {
            $user = $this->storage->findById('users.json', $userId);
            if (!$user) {
                throw new Exception("User not found");
            }
            
            $emailData = [
                'to' => $userEmail,
                'to_name' => $user['first_name'] . ' ' . $user['last_name'],
                'subject' => "Bienvenue chez CV Professional Services! 🎉",
                'html_content' => $this->getWelcomeEmailTemplate($user)
            ];
            
            $result = $this->sendEmail($emailData);
            
            if ($result['success']) {
                $this->logEmailSent($userId, null, $userEmail, 'welcome');
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Welcome email failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function sendEmail($emailData) {
        // For demo purposes, we'll simulate email sending
        // In production, integrate with services like:
        // - SendGrid, Mailgun, Amazon SES, or SMTP
        
        try {
            // Simulate email sending delay
            sleep(1);
            
            // Log email attempt
            error_log("Simulating email send to: " . $emailData['to']);
            error_log("Subject: " . $emailData['subject']);
            
            // Simulate 95% success rate
            $success = (rand(1, 100) <= 95);
            
            if ($success) {
                // Save email content for debugging
                $this->saveEmailForDebug($emailData);
                
                return [
                    'success' => true,
                    'message_id' => 'msg_' . uniqid(),
                    'timestamp' => date('c')
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Temporary email service failure'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        /*
        TODO: Replace with real email service integration
        
        // Example with SendGrid:
        $sendgrid = new \SendGrid($this->config['email']['sendgrid_api_key']);
        
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom($this->config['email']['from_email'], $this->config['email']['from_name']);
        $email->setSubject($emailData['subject']);
        $email->addTo($emailData['to'], $emailData['to_name']);
        $email->addContent("text/html", $emailData['html_content']);
        
        if (isset($emailData['attachments'])) {
            foreach ($emailData['attachments'] as $attachment) {
                $email->addAttachment(
                    base64_encode(file_get_contents($attachment['path'])),
                    "application/pdf",
                    $attachment['name'],
                    "attachment"
                );
            }
        }
        
        $response = $sendgrid->send($email);
        
        if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
            return ['success' => true, 'message_id' => $response->headers()['X-Message-Id']];
        } else {
            return ['success' => false, 'error' => 'SendGrid error: ' . $response->statusCode()];
        }
        */
    }
    
    private function getInvoiceEmailTemplate($invoice, $user) {
        $downloadLink = $this->generateSecureDownloadLink($invoice['cv_translation_id']);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 30px; background: #f9f9f9; }
                .invoice-details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #4f46e5; }
                .download-section { background: linear-gradient(135deg, #10b981, #059669); padding: 25px; border-radius: 10px; text-align: center; margin: 25px 0; }
                .download-btn { background: white; color: #10b981; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; margin: 15px 0; font-weight: bold; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
                .footer { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; }
                .features-list { list-style: none; padding: 0; }
                .features-list li { padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
                .features-list li:before { content: '✅'; margin-right: 10px; }
                .highlight { background: #fef3c7; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #f59e0b; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Votre CV professionnel est prêt !</h1>
                    <p>Merci pour votre confiance, {$user['first_name']} !</p>
                </div>
                
                <div class='content'>
                    <h2>Bonjour {$user['first_name']},</h2>
                    
                    <p>Excellente nouvelle ! Votre CV a été traduit avec succès par notre intelligence artificielle DeepSeek et est maintenant prêt au téléchargement.</p>
                    
                    <div class='invoice-details'>
                        <h3>📄 Détails de votre commande</h3>
                        <p><strong>Numéro de facture :</strong> {$invoice['invoice_number']}</p>
                        <p><strong>Date :</strong> " . date('d/m/Y', strtotime($invoice['invoice_date'])) . "</p>
                        <p><strong>Montant :</strong> {$invoice['total_amount']}€</p>
                        <p><strong>Service :</strong> Traduction CV Professionnel</p>
                    </div>
                    
                    <div class='download-section'>
                        <h3 style='color: white; margin-bottom: 15px;'>🚀 Téléchargez votre CV</h3>
                        <p style='color: white; margin-bottom: 20px;'>Votre CV traduit et optimisé vous attend !</p>
                        <a href='{$downloadLink}' class='download-btn'>
                            📥 Télécharger mon CV professionnel
                        </a>
                        <p style='color: white; font-size: 12px; margin-top: 15px;'>
                            ⏰ Lien valide pendant 48 heures
                        </p>
                    </div>
                    
                    <div class='highlight'>
                        <h3>🎯 Votre CV inclut :</h3>
                        <ul class='features-list'>
                            <li>Traduction professionnelle par IA DeepSeek</li>
                            <li>Optimisation pour le marché cible</li>
                            <li>Format PDF A4 professionnel</li>
                            <li>Compatible ATS (systèmes de recrutement)</li>
                            <li>Terminologie spécialisée du secteur</li>
                        </ul>
                    </div>
                    
                    <div class='invoice-details'>
                        <h3>💬 Besoin d'aide ?</h3>
                        <p>Notre équipe support est disponible pour vous accompagner :</p>
                        <p>📧 <strong>Email :</strong> support@cvprofessional.com</p>
                        <p>⏰ <strong>Horaires :</strong> Lundi-Vendredi 9h-18h</p>
                        <p>📱 <strong>Réponse garantie :</strong> Sous 2h en semaine</p>
                    </div>
                    
                    <div style='background: #e0f2fe; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                        <h3 style='color: #0277bd;'>🌟 Satisfait de notre service ?</h3>
                        <p>Aidez d'autres professionnels en partageant votre expérience ! Recommandez CV Professional Services à vos contacts.</p>
                    </div>
                </div>
                
                <div class='footer'>
                    <h4>CV Professional Services</h4>
                    <p>Votre partenaire pour une carrière internationale réussie</p>
                    <p style='font-size: 12px; margin-top: 15px;'>
                        Cette facture est automatiquement jointe à cet email<br>
                        Conservez-la pour vos déclarations fiscales
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getWelcomeEmailTemplate($user) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 30px; background: #f9f9f9; }
                .welcome-box { background: white; padding: 25px; margin: 20px 0; border-radius: 8px; border: 2px solid #4f46e5; }
                .cta-button { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; margin: 20px 0; font-weight: bold; }
                .footer { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; }
                .benefits { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
                .benefit { background: white; padding: 15px; border-radius: 8px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Bienvenue chez CV Professional !</h1>
                    <p>Votre compte a été créé avec succès</p>
                </div>
                
                <div class='content'>
                    <div class='welcome-box'>
                        <h2>Bonjour {$user['first_name']} ! 👋</h2>
                        <p>Félicitations ! Vous venez de rejoindre des milliers de professionnels qui ont transformé leur carrière grâce à nos services de traduction CV.</p>
                        
                        <h3>🎁 Votre compte gratuit inclut :</h3>
                        <div class='benefits'>
                            <div class='benefit'>
                                <div style='font-size: 2rem; margin-bottom: 10px;'>🆓</div>
                                <strong>1 traduction gratuite</strong>
                            </div>
                            <div class='benefit'>
                                <div style='font-size: 2rem; margin-bottom: 10px;'>🌍</div>
                                <strong>10 pays de destination</strong>
                            </div>
                            <div class='benefit'>
                                <div style='font-size: 2rem; margin-bottom: 10px;'>🎨</div>
                                <strong>4 templates pro</strong>
                            </div>
                            <div class='benefit'>
                                <div style='font-size: 2rem; margin-bottom: 10px;'>🤖</div>
                                <strong>IA DeepSeek</strong>
                            </div>
                        </div>
                        
                        <div style='text-align: center; margin: 25px 0;'>
                            <a href='" . $this->config['app_url'] . "/index.php?page=dashboard' class='cta-button'>
                                🚀 Commencer ma première traduction
                            </a>
                        </div>
                    </div>
                    
                    <div style='background: #fef3c7; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b;'>
                        <h3>💡 Conseil d'expert</h3>
                        <p>Pour maximiser vos chances de décrocher un entretien, assurez-vous que votre CV français contient :</p>
                        <ul>
                            <li>Vos expériences détaillées avec des chiffres</li>
                            <li>Vos compétences techniques spécifiques</li>
                            <li>Vos formations et certifications</li>
                            <li>Vos langues et leur niveau</li>
                        </ul>
                    </div>
                    
                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                        <h3>📞 Support client</h3>
                        <p>Une question ? Notre équipe francophone est là pour vous aider :</p>
                        <p>📧 <strong>Email :</strong> support@cvprofessional.com</p>
                        <p>⏰ <strong>Horaires :</strong> Lun-Ven 9h-18h</p>
                        <p>🚀 <strong>Réponse moyenne :</strong> Sous 2h</p>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>Merci de faire confiance à CV Professional Services</p>
                    <p style='font-size: 12px; margin-top: 10px;'>
                        Vous recevez cet email car vous avez créé un compte sur notre plateforme.<br>
                        Pour vous désabonner, contactez-nous à support@cvprofessional.com
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function generateInvoicePDF($invoice) {
        // Create invoice PDF directory
        $invoiceDir = __DIR__ . '/../../storage/invoices/';
        if (!is_dir($invoiceDir)) {
            mkdir($invoiceDir, 0755, true);
        }
        
        $fileName = "Facture_{$invoice['invoice_number']}.pdf";
        $filePath = $invoiceDir . $fileName;
        
        // Generate PDF content (simple text version for demo)
        $pdfContent = $this->generateInvoicePDFContent($invoice);
        
        // In production, use proper PDF libraries like TCPDF, DOMPDF, or mPDF
        file_put_contents($filePath, $pdfContent);
        
        return $filePath;
    }
    
    private function generateInvoicePDFContent($invoice) {
        $content = "FACTURE PROFESSIONNELLE\n";
        $content .= "========================\n\n";
        $content .= "CV Professional Services\n";
        $content .= "123 Business Street\n";
        $content .= "75001 Paris, France\n";
        $content .= "SIRET: 12345678901234\n";
        $content .= "TVA: FR12345678901\n\n";
        $content .= "FACTURE N°: {$invoice['invoice_number']}\n";
        $content .= "DATE: " . date('d/m/Y', strtotime($invoice['invoice_date'])) . "\n";
        $content .= "ÉCHÉANCE: " . date('d/m/Y', strtotime($invoice['due_date'])) . "\n\n";
        $content .= "FACTURER À:\n";
        $content .= "----------\n";
        $content .= "{$invoice['customer_info']['name']}\n";
        $content .= "{$invoice['customer_info']['email']}\n\n";
        $content .= "DÉTAIL DES SERVICES:\n";
        $content .= "-------------------\n";
        
        foreach ($invoice['items'] as $item) {
            $content .= "• {$item['description']}\n";
            $content .= "  Pays cible: {$item['target_country']}\n";
            $content .= "  Quantité: {$item['quantity']}\n";
            $content .= "  Prix unitaire: {$item['unit_price']}€\n";
            $content .= "  Total: {$item['total']}€\n\n";
        }
        
        $content .= "RÉCAPITULATIF:\n";
        $content .= "-------------\n";
        $content .= "Sous-total HT: " . number_format($invoice['amount'] - $invoice['tax_amount'], 2) . "€\n";
        $content .= "TVA (20%): " . number_format($invoice['tax_amount'], 2) . "€\n";
        $content .= "TOTAL TTC: {$invoice['total_amount']}€\n\n";
        $content .= "STATUT: PAYÉE\n";
        $content .= "MODE DE PAIEMENT: Revolut\n\n";
        $content .= "Merci pour votre confiance !\n";
        $content .= "CV Professional Services\n";
        $content .= "support@cvprofessional.com\n";
        
        return $content;
    }
    
    private function generateSecureDownloadLink($translationId) {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (48 * 60 * 60); // 48 hours
        
        // Store download token
        $tokens = $this->storage->read('download_tokens.json');
        if (!isset($tokens['tokens'])) {
            $tokens['tokens'] = [];
        }
        
        $tokens['tokens'][] = [
            'token' => $token,
            'translation_id' => $translationId,
            'expires_at' => date('c', $expires),
            'created_at' => date('c'),
            'used' => false
        ];
        
        $this->storage->write('download_tokens.json', $tokens);
        
        return $this->config['app_url'] . "/api/download.php?token={$token}&translation={$translationId}";
    }
    
    private function logEmailSent($userId, $invoiceId, $recipient, $type) {
        $this->storage->insert('email_logs.json', 'email_logs', [
            'user_id' => $userId,
            'invoice_id' => $invoiceId,
            'email_type' => $type,
            'recipient' => $recipient,
            'status' => 'sent',
            'sent_at' => date('c')
        ]);
    }
    
    private function logEmailError($invoiceId, $recipient, $error) {
        $this->storage->insert('email_logs.json', 'email_logs', [
            'invoice_id' => $invoiceId,
            'email_type' => 'invoice',
            'recipient' => $recipient,
            'status' => 'failed',
            'error_message' => $error,
            'attempted_at' => date('c')
        ]);
    }
    
    private function saveEmailForDebug($emailData) {
        // Save email content to debug folder for development
        $debugDir = __DIR__ . '/../../storage/email_debug/';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }
        
        $fileName = 'email_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.html';
        $filePath = $debugDir . $fileName;
        
        $debugContent = "<!-- EMAIL DEBUG SAVE -->\n";
        $debugContent .= "<!-- TO: {$emailData['to']} -->\n";
        $debugContent .= "<!-- SUBJECT: {$emailData['subject']} -->\n";
        $debugContent .= "<!-- TIMESTAMP: " . date('Y-m-d H:i:s') . " -->\n\n";
        $debugContent .= $emailData['html_content'];
        
        file_put_contents($filePath, $debugContent);
        
        error_log("Email saved for debug: {$filePath}");
    }
}
?>
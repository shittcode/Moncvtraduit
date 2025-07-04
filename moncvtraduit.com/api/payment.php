<?php
// api/payment.php - Payment processing API endpoint

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Load dependencies
require_once '../config/app.php';
require_once '../src/JsonStorage.php';
require_once '../src/Controllers/AuthController.php';
require_once '../src/Services/EmailService.php';
$config = require_once '../config/app.php';
$storage = new JsonStorage();
$authController = new AuthController();

// Check if user is logged in
if (!$authController->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentification requise']);
    exit;
}

$currentUser = $authController->getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action non spécifiée']);
    exit;
}

try {
    switch ($input['action']) {
        case 'process_payment':
            $result = processPayment($input, $currentUser, $storage, $config);
            echo json_encode($result);
            break;
            
        case 'create_payment_intent':
            $result = createPaymentIntent($input, $currentUser, $storage, $config);
            echo json_encode($result);
            break;
            
        case 'verify_payment':
            $result = verifyPayment($input, $currentUser, $storage, $config);
            echo json_encode($result);
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    error_log("Payment API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function processPayment($input, $currentUser, $storage, $config) {
    // Validate required fields
    if (empty($input['translation_id']) || empty($input['amount']) || empty($input['currency'])) {
        throw new Exception('Données de paiement incomplètes');
    }
    
    $translationId = $input['translation_id'];
    $amount = floatval($input['amount']);
    $currency = $input['currency'];
    
    // Verify translation exists and belongs to user
    $translation = $storage->findById('cv_translations.json', $translationId);
    if (!$translation || $translation['user_id'] !== $currentUser['id']) {
        throw new Exception('Traduction non trouvée');
    }
    
    // Check if already paid
    if (isset($translation['payment_status']) && $translation['payment_status'] === 'paid') {
        throw new Exception('Cette traduction a déjà été payée');
    }
    
    // Verify amount
    if ($amount !== $config['pricing']['cv_translation']) {
        throw new Exception('Montant incorrect');
    }
    
    // Create payment record
    $paymentId = $storage->insert('payments.json', 'payments', [
        'user_id' => $currentUser['id'],
        'cv_translation_id' => $translationId,
        'amount' => $amount,
        'currency' => $currency,
        'payment_method' => 'revolut',
        'status' => 'pending'
    ]);
    
    // Simulate Revolut payment process
    $paymentResult = simulateRevolutPayment($paymentId, $amount, $currency, $config);
    
    if ($paymentResult['success']) {
        // Update payment status
        $storage->update('payments.json', 'payments', $paymentId, [
            'status' => 'completed',
            'revolut_payment_id' => $paymentResult['payment_id'],
            'completed_at' => date('c')
        ]);
        
        // Update translation status
        $storage->update('cv_translations.json', 'translations', $translationId, [
            'payment_status' => 'paid',
            'payment_id' => $paymentId
        ]);
        
        // Generate invoice
        $invoiceId = createInvoice($paymentId, $currentUser, $translation, $storage, $config);
        
        // Send invoice email automatically
        $emailService = new EmailService();
        $emailResult = $emailService->sendInvoiceEmail($invoiceId, $currentUser['email']);
        
        if (!$emailResult['success']) {
            error_log("Failed to send invoice email: " . $emailResult['error']);
            // Don't fail the payment, just log the email error
        }
        
        // Send invoice email (we'll implement this later)
        // sendInvoiceEmail($invoiceId, $currentUser['email']);
        
        // Generate PDF download
        $pdfPath = generatePDF($translation, $currentUser, $storage, $config);
        
        // Log successful payment
        $storage->insert('analytics.json', 'events', [
            'event_type' => 'payment_completed',
            'user_id' => $currentUser['id'],
            'data' => [
                'payment_id' => $paymentId,
                'translation_id' => $translationId,
                'amount' => $amount,
                'currency' => $currency,
                'invoice_id' => $invoiceId
            ]
        ]);
        
        return [
            'success' => true,
            'message' => 'Paiement traité avec succès',
            'payment_id' => $paymentId,
            'invoice_id' => $invoiceId,
            'download_url' => generateDownloadUrl($translationId),
            'pdf_path' => $pdfPath
        ];
    } else {
        // Update payment status to failed
        $storage->update('payments.json', 'payments', $paymentId, [
            'status' => 'failed',
            'error_message' => $paymentResult['error']
        ]);
        
        throw new Exception('Échec du paiement: ' . $paymentResult['error']);
    }
}

function simulateRevolutPayment($paymentId, $amount, $currency, $config) {
    // This is a simulation. In production, integrate with real Revolut API
    
    // Simulate processing delay
    sleep(1);
    
    // Simulate 95% success rate
    $success = (rand(1, 100) <= 95);
    
    if ($success) {
        return [
            'success' => true,
            'payment_id' => 'rev_' . uniqid() . '_' . time(),
            'status' => 'completed',
            'amount' => $amount,
            'currency' => $currency
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Paiement refusé par la banque'
        ];
    }
    
    /* 
    TODO: Replace with real Revolut API integration:
    
    $revolutConfig = $config['revolut'];
    
    $paymentData = [
        'amount' => $amount * 100, // Amount in cents
        'currency' => $currency,
        'description' => 'CV Professional Translation',
        'metadata' => [
            'payment_id' => $paymentId,
            'user_id' => $currentUser['id']
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.revolut.com/api/1.0/pay');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $revolutConfig['api_key']
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        return [
            'success' => true,
            'payment_id' => $result['id'],
            'status' => $result['status']
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Erreur de connexion Revolut'
        ];
    }
    */
}

function createInvoice($paymentId, $currentUser, $translation, $storage, $config) {
    $invoiceNumber = generateInvoiceNumber($storage);
    
    return $storage->insert('invoices.json', 'invoices', [
        'invoice_number' => $invoiceNumber,
        'user_id' => $currentUser['id'],
        'payment_id' => $paymentId,
        'cv_translation_id' => $translation['id'],
        'invoice_date' => date('Y-m-d'),
        'due_date' => date('Y-m-d'),
        'amount' => $config['pricing']['cv_translation'],
        'currency' => $config['pricing']['currency'],
        'tax_rate' => 20, // French VAT
        'tax_amount' => round($config['pricing']['cv_translation'] * 0.2 / 1.2, 2),
        'total_amount' => $config['pricing']['cv_translation'],
        'status' => 'paid',
        'email_sent' => false,
        'company_info' => [
            'name' => 'CV Professional Services',
            'address' => '123 Business Street, Paris, France',
            'email' => 'invoices@cvprofessional.com',
            'tax_number' => 'FR12345678901',
            'siret' => '12345678901234'
        ],
        'customer_info' => [
            'name' => $currentUser['first_name'] . ' ' . $currentUser['last_name'],
            'email' => $currentUser['email']
        ],
        'items' => [
            [
                'description' => 'Professional CV Translation (French to ' . ucfirst($translation['target_language']) . ')',
                'target_country' => strtoupper($translation['target_country']),
                'quantity' => 1,
                'unit_price' => $config['pricing']['cv_translation'],
                'total' => $config['pricing']['cv_translation']
            ]
        ]
    ]);
}

function generateInvoiceNumber($storage) {
    $year = date('Y');
    $invoices = $storage->read('invoices.json');
    
    $maxNumber = 0;
    if (isset($invoices['invoices'])) {
        foreach ($invoices['invoices'] as $invoice) {
            if (isset($invoice['invoice_number']) && strpos($invoice['invoice_number'], "INV-{$year}-") === 0) {
                $number = intval(substr($invoice['invoice_number'], -6));
                $maxNumber = max($maxNumber, $number);
            }
        }
    }
    
    $nextNumber = str_pad($maxNumber + 1, 6, '0', STR_PAD_LEFT);
    return "INV-{$year}-{$nextNumber}";
}

function generatePDF($translation, $currentUser, $storage, $config) {
    // Create PDF generation directory
    $pdfDir = '../storage/pdfs/';
    if (!is_dir($pdfDir)) {
        mkdir($pdfDir, 0755, true);
    }
    
    // Get parsed content
    $content = json_decode($translation['parsed_content'], true);
    
    // Simple PDF generation (in production, use proper PDF library)
    $pdfContent = generatePDFContent($content, $translation, $config);
    
    $fileName = 'CV_' . strtoupper($translation['target_country']) . '_' . date('Y-m-d') . '_' . uniqid() . '.pdf';
    $filePath = $pdfDir . $fileName;
    
    // For demo purposes, create a simple text file
    // In production, use libraries like TCPDF, DOMPDF, or mPDF
    file_put_contents($filePath, $pdfContent);
    
    // Update translation record with PDF path
    $storage->update('cv_translations.json', 'translations', $translation['id'], [
        'pdf_path' => $filePath,
        'pdf_filename' => $fileName
    ]);
    
    return $filePath;
}

function generatePDFContent($content, $translation, $config) {
    // Simple text-based PDF content (demo)
    $pdfContent = "CV PROFESSIONAL - TRANSLATED RESUME\n";
    $pdfContent .= "======================================\n\n";
    $pdfContent .= "Name: " . ($content['name'] ?? 'N/A') . "\n";
    $pdfContent .= "Title: " . ($content['title'] ?? 'N/A') . "\n";
    $pdfContent .= "Contact: " . ($content['contact'] ?? 'N/A') . "\n";
    $pdfContent .= "Target Country: " . strtoupper($translation['target_country']) . "\n";
    $pdfContent .= "Language: " . ucfirst($translation['target_language']) . "\n";
    $pdfContent .= "Template: " . ucfirst($translation['template_type']) . "\n";
    $pdfContent .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    $pdfContent .= "PROFESSIONAL SUMMARY\n";
    $pdfContent .= "===================\n";
    $pdfContent .= ($content['summary'] ?? 'N/A') . "\n\n";
    
    if (!empty($content['experience'])) {
        $pdfContent .= "PROFESSIONAL EXPERIENCE\n";
        $pdfContent .= "======================\n";
        foreach ($content['experience'] as $exp) {
            $pdfContent .= "• " . $exp . "\n";
        }
        $pdfContent .= "\n";
    }
    
    if (!empty($content['education'])) {
        $pdfContent .= "EDUCATION\n";
        $pdfContent .= "=========\n";
        foreach ($content['education'] as $edu) {
            $pdfContent .= "• " . $edu . "\n";
        }
        $pdfContent .= "\n";
    }
    
    if (!empty($content['skills'])) {
        $pdfContent .= "TECHNICAL SKILLS\n";
        $pdfContent .= "===============\n";
        foreach ($content['skills'] as $skill) {
            $pdfContent .= "• " . $skill . "\n";
        }
        $pdfContent .= "\n";
    }
    
    if (!empty($content['languages'])) {
        $pdfContent .= "LANGUAGES\n";
        $pdfContent .= "=========\n";
        foreach ($content['languages'] as $lang) {
            $pdfContent .= "• " . $lang . "\n";
        }
        $pdfContent .= "\n";
    }
    
    if (!empty($content['certifications'])) {
        $pdfContent .= "CERTIFICATIONS\n";
        $pdfContent .= "==============\n";
        foreach ($content['certifications'] as $cert) {
            $pdfContent .= "• " . $cert . "\n";
        }
        $pdfContent .= "\n";
    }
    
    $pdfContent .= "\n--- Generated by CV Professional Services ---\n";
    $pdfContent .= "Professional CV translation powered by DeepSeek AI\n";
    $pdfContent .= "Visit: https://cvprofessional.com\n";
    
    return $pdfContent;
}

function generateDownloadUrl($translationId) {
    // Generate secure download token
    $token = bin2hex(random_bytes(32));
    $expiresAt = time() + (24 * 60 * 60); // 24 hours
    
    // In production, store this token securely
    return "api/download.php?token={$token}&translation={$translationId}";
}

function createPaymentIntent($input, $currentUser, $storage, $config) {
    // For Revolut integration - create payment intent
    return [
        'success' => true,
        'message' => 'Payment intent created',
        'client_secret' => 'pi_' . uniqid(),
        'amount' => $config['pricing']['cv_translation'],
        'currency' => $config['pricing']['currency']
    ];
}

function verifyPayment($input, $currentUser, $storage, $config) {
    // Verify payment with Revolut
    return [
        'success' => true,
        'message' => 'Payment verified',
        'status' => 'completed'
    ];
}
?>
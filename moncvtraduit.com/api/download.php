<?php
// api/download.php - Secure file download endpoint

session_start();

require_once '../config/app.php';
require_once '../src/JsonStorage.php';
require_once '../src/Controllers/AuthController.php';

$config = require_once '../config/app.php';
$storage = new JsonStorage();
$authController = new AuthController();

// Check if user is logged in
if (!$authController->isLoggedIn()) {
    http_response_code(401);
    die('Authentification requise');
}

$currentUser = $authController->getCurrentUser();

// Get parameters
$token = $_GET['token'] ?? '';
$translationId = $_GET['translation'] ?? '';

if (empty($token) || empty($translationId)) {
    http_response_code(400);
    die('Paramètres manquants');
}

try {
    // Verify translation exists and belongs to user
    $translation = $storage->findById('cv_translations.json', $translationId);
    
    if (!$translation || $translation['user_id'] !== $currentUser['id']) {
        http_response_code(404);
        die('Fichier non trouvé');
    }
    
    // Check if payment is completed
    if (!isset($translation['payment_status']) || $translation['payment_status'] !== 'paid') {
        http_response_code(403);
        die('Paiement requis');
    }
    
    // Get PDF path
    $pdfPath = $translation['pdf_path'] ?? '';
    
    if (empty($pdfPath) || !file_exists($pdfPath)) {
        http_response_code(404);
        die('Fichier PDF non trouvé');
    }
    
    // Generate filename
    $fileName = $translation['pdf_filename'] ?? 'CV_Professional.pdf';
    
    // Set headers for file download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($pdfPath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Output file
    readfile($pdfPath);
    
    // Log download
    $storage->insert('analytics.json', 'events', [
        'event_type' => 'cv_downloaded',
        'user_id' => $currentUser['id'],
        'data' => [
            'translation_id' => $translationId,
            'filename' => $fileName,
            'download_token' => $token
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Download error: " . $e->getMessage());
    http_response_code(500);
    die('Erreur de téléchargement');
}
?>
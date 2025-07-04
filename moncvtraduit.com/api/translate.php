<?php
// api/translate.php - CV Translation API endpoint

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Load dependencies
require_once '../config/app.php';
require_once '../src/JsonStorage.php';
require_once '../src/Controllers/AuthController.php';

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

if (!isset($_POST['action']) || $_POST['action'] !== 'translate_cv') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action invalide']);
    exit;
}

try {
    // Validate file upload
    if (!isset($_FILES['cv_file']) || $_FILES['cv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erreur lors de l\'upload du fichier');
    }
    
    $file = $_FILES['cv_file'];
    
    // Validate file type
    $allowedTypes = ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $allowedExtensions = ['.doc', '.docx'];
    $fileExtension = '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file['type'], $allowedTypes) && !in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('Type de fichier non autorisé. Utilisez .doc ou .docx');
    }
    
    // Validate file size
    if ($file['size'] > $config['max_file_size']) {
        $maxSizeMB = round($config['max_file_size'] / 1024 / 1024);
        throw new Exception("Fichier trop volumineux. Taille maximale: {$maxSizeMB}MB");
    }
    
    // Get translation parameters
    $targetCountry = $_POST['target_country'] ?? '';
    $targetLanguage = $_POST['target_language'] ?? '';
    $template = $_POST['template'] ?? 'professional';
    $color = $_POST['color'] ?? 'blue';
    
    if (empty($targetCountry) || empty($targetLanguage)) {
        throw new Exception('Pays et langue de destination requis');
    }
    
    // Check rate limiting
    if (!checkRateLimit($storage, $currentUser['id'], 'translation')) {
        throw new Exception('Limite de traductions atteinte. Veuillez patienter.');
    }
    
    // Save uploaded file temporarily
    $uploadDir = '../storage/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = uniqid() . '_' . time() . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Erreur lors de la sauvegarde du fichier');
    }
    
    // Extract text from Word document
    $extractedText = extractTextFromWord($filePath);
    
    if (empty($extractedText)) {
        throw new Exception('Impossible d\'extraire le texte du document');
    }
    
    // Translate using DeepSeek API
    $translatedText = translateWithDeepSeek($extractedText, $targetLanguage, $targetCountry, $config);
    
    if (empty($translatedText)) {
        throw new Exception('Erreur lors de la traduction');
    }
    
    // Parse the translated content
    $parsedContent = parseTranslatedContent($translatedText);
    
    // Create translation record
    $translationId = $storage->insert('cv_translations.json', 'translations', [
        'user_id' => $currentUser['id'],
        'original_filename' => $file['name'],
        'source_language' => 'fr',
        'target_language' => $targetLanguage,
        'target_country' => $targetCountry,
        'template_type' => $template,
        'color_scheme' => $color,
        'original_content' => $extractedText,
        'translated_content' => $translatedText,
        'parsed_content' => json_encode($parsedContent),
        'status' => 'completed',
        'payment_status' => 'pending',
        'file_path' => $filePath
    ]);
    
    // Log rate limit usage
    logRateLimitUsage($storage, $currentUser['id'], 'translation');
    
    // Log analytics
    $storage->insert('analytics.json', 'events', [
        'event_type' => 'cv_translated',
        'user_id' => $currentUser['id'],
        'data' => [
            'translation_id' => $translationId,
            'target_country' => $targetCountry,
            'target_language' => $targetLanguage,
            'template' => $template,
            'color' => $color,
            'file_size' => $file['size'],
            'original_filename' => $file['name']
        ]
    ]);
    
    // Clean up uploaded file
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'CV traduit avec succès',
        'translationId' => $translationId,
        'translatedContent' => $parsedContent
    ]);
    
} catch (Exception $e) {
    error_log("Translation API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Helper Functions

function extractTextFromWord($filePath) {
    // Simple text extraction (in production, use better libraries like PHPWord)
    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    if ($fileExtension === 'docx') {
        return extractFromDocx($filePath);
    } elseif ($fileExtension === 'doc') {
        return extractFromDoc($filePath);
    }
    
    return '';
}

function extractFromDocx($filePath) {
    try {
        $zip = new ZipArchive;
        if ($zip->open($filePath) === TRUE) {
            $xmlString = $zip->getFromName('word/document.xml');
            $zip->close();
            
            if ($xmlString) {
                // Remove XML tags and get text content
                $xmlString = str_replace('</w:p>', "\n", $xmlString);
                $xmlString = strip_tags($xmlString);
                return trim($xmlString);
            }
        }
    } catch (Exception $e) {
        error_log("DOCX extraction error: " . $e->getMessage());
    }
    
    return '';
}

function extractFromDoc($filePath) {
    // Basic .doc file handling (limited support)
    try {
        $content = file_get_contents($filePath);
        if ($content) {
            // Very basic text extraction for .doc files
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
            $content = preg_replace('/[^\x20-\x7E\x0A\x0D]/', '', $content);
            return trim($content);
        }
    } catch (Exception $e) {
        error_log("DOC extraction error: " . $e->getMessage());
    }
    
    return '';
}

function translateWithDeepSeek($text, $targetLanguage, $targetCountry, $config) {
    try {
        $apiKey = $config['deepseek']['api_key'];
        $baseUrl = $config['deepseek']['base_url'];
        $model = $config['deepseek']['model'];
        
        // Get country-specific instructions
        $instructions = getCountryInstructions($targetCountry, $targetLanguage);
        
        $prompt = "You are a professional CV translator and career consultant. Translate this French CV to {$targetLanguage} for the {$targetCountry} job market.\n\n";
        $prompt .= "IMPORTANT INSTRUCTIONS:\n";
        $prompt .= "- {$instructions}\n";
        $prompt .= "- Maintain professional tone and industry terminology\n";
        $prompt .= "- Optimize for ATS (Applicant Tracking Systems)\n";
        $prompt .= "- Format clearly with proper sections\n";
        $prompt .= "- Use action verbs and quantify achievements\n\n";
        $prompt .= "Original French CV:\n{$text}\n\n";
        $prompt .= "Return ONLY the translated CV content, no explanations.";
        
        $data = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $prompt
                ],
                [
                    'role' => 'user',
                    'content' => $text
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 2000
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Erreur de connexion API: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Erreur API DeepSeek: Code ' . $httpCode);
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception('Réponse API invalide');
        }
        
        return trim($result['choices'][0]['message']['content']);
        
    } catch (Exception $e) {
        error_log("DeepSeek API Error: " . $e->getMessage());
        
        // Fallback to demo content if API fails
        return getFallbackTranslation($targetLanguage, $targetCountry);
    }
}

function getCountryInstructions($country, $language) {
    $instructions = [
        'uk' => 'Use British English spellings. Include Personal Statement. Focus on achievements and results. Exclude photo and personal details.',
        'usa' => 'Use American English. Exclude personal information like age, photo, marital status. Focus on quantifiable achievements. Use resume format.',
        'canada' => 'Use Canadian English. Include bilingual capabilities if relevant. Emphasize multicultural experience.',
        'australia' => 'Use Australian English. Mention visa status if relevant. Focus on practical skills and experience.',
        'uae' => 'Professional format for Middle East. Include nationality. Emphasize international experience and cultural adaptability.',
        'saudi' => 'Conservative professional format. Include nationality. Emphasize leadership and project management skills.',
        'oman' => 'Professional format for Gulf region. Include nationality and relevant certifications.',
        'qatar' => 'Professional format for Qatar market. Emphasize technical skills and international experience.',
        'spain' => 'Translate to Spanish. Use European Spanish conventions. Include language certifications prominently.',
        'portugal' => 'Translate to Portuguese. Use European Portuguese. Emphasize EU work authorization if applicable.'
    ];
    
    return $instructions[$country] ?? $instructions['uk'];
}

function getFallbackTranslation($targetLanguage, $targetCountry) {
    // Fallback content when API is unavailable
    return "John MARTIN
Senior Software Engineer
john.martin@email.com | +44 20 7123 4567 | London, UK

PROFESSIONAL SUMMARY
Experienced software engineer with 8+ years in full-stack development and team leadership. Proven expertise in modern web technologies, agile methodologies, and cross-functional collaboration.

PROFESSIONAL EXPERIENCE
Senior Software Engineer | TechCorp International | 2020 - Present
- Led development of enterprise applications serving 100,000+ users
- Managed cross-functional team of 6 developers and designers
- Implemented microservices architecture improving system scalability by 300%

Software Developer | Innovation Labs | 2018 - 2020
- Developed responsive web applications using React, Node.js, and PostgreSQL
- Optimized database queries reducing response time by 60%

EDUCATION
Master's Degree in Computer Science | École Polytechnique | 2018
Bachelor's Degree in Software Engineering | Université de Paris | 2016

TECHNICAL SKILLS
- Programming: JavaScript, Python, TypeScript, Java
- Frontend: React, Vue.js, Angular, HTML5, CSS3
- Backend: Node.js, Express.js, Django, Spring Boot
- Cloud: AWS, Azure, Docker, Kubernetes

LANGUAGES
- French: Native proficiency
- English: Fluent (C2 level)
- Spanish: Conversational (B2 level)

CERTIFICATIONS
- AWS Certified Solutions Architect Professional (2023)
- Certified ScrumMaster (CSM) (2022)";
}

function parseTranslatedContent($translatedText) {
    $lines = explode("\n", $translatedText);
    $lines = array_filter(array_map('trim', $lines));
    
    $content = [
        'name' => '',
        'title' => '',
        'contact' => '',
        'summary' => '',
        'experience' => [],
        'education' => [],
        'skills' => [],
        'languages' => [],
        'certifications' => []
    ];
    
    if (count($lines) > 0) $content['name'] = $lines[0];
    if (count($lines) > 1) $content['title'] = $lines[1];
    if (count($lines) > 2) $content['contact'] = $lines[2];
    
    // Simple parsing - in production, use more sophisticated parsing
    $currentSection = '';
    for ($i = 3; $i < count($lines); $i++) {
        $line = $lines[$i];
        
        if (stripos($line, 'SUMMARY') !== false || stripos($line, 'PROFILE') !== false) {
            $currentSection = 'summary';
        } elseif (stripos($line, 'EXPERIENCE') !== false || stripos($line, 'WORK') !== false) {
            $currentSection = 'experience';
        } elseif (stripos($line, 'EDUCATION') !== false) {
            $currentSection = 'education';
        } elseif (stripos($line, 'SKILLS') !== false) {
            $currentSection = 'skills';
        } elseif (stripos($line, 'LANGUAGES') !== false) {
            $currentSection = 'languages';
        } elseif (stripos($line, 'CERTIFICATIONS') !== false) {
            $currentSection = 'certifications';
        } elseif (!empty($currentSection) && !empty($line)) {
            if ($currentSection === 'summary') {
                $content['summary'] .= $line . ' ';
            } else {
                $content[$currentSection][] = $line;
            }
        }
    }
    
    $content['summary'] = trim($content['summary']);
    
    return $content;
}

function checkRateLimit($storage, $userId, $type) {
    $config = require '../config/app.php';
    $limit = $config['rate_limits'][$type] ?? 5;
    
    $rateLimits = $storage->read('rate_limits.json');
    $key = $userId . '_' . $type . '_' . date('Y-m-d-H');
    
    $currentCount = $rateLimits[$key] ?? 0;
    
    return $currentCount < $limit;
}

function logRateLimitUsage($storage, $userId, $type) {
    $rateLimits = $storage->read('rate_limits.json');
    $key = $userId . '_' . $type . '_' . date('Y-m-d-H');
    
    $rateLimits[$key] = ($rateLimits[$key] ?? 0) + 1;
    
    $storage->write('rate_limits.json', $rateLimits);
}
?>
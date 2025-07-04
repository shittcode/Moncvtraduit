<?php
// src/Controllers/AuthController.php - Handles user authentication

require_once __DIR__ . '/../JsonStorage.php';

class AuthController {
    private $storage;
    private $config;
    
    public function __construct() {
        $this->storage = new JsonStorage();
        $this->config = require __DIR__ . '/../../config/app.php';
    }
    
    public function register($email, $password, $firstName, $lastName) {
        // Validate input
        if (empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
            return ['success' => false, 'message' => 'Tous les champs sont obligatoires'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email invalide'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères'];
        }
        
        // Check if user already exists
        $existingUser = $this->storage->findByEmail($email);
        if ($existingUser) {
            return ['success' => false, 'message' => 'Un compte existe déjà avec cet email'];
        }
        
        // Create new user
        $userId = $this->storage->insert('users.json', 'users', [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'subscription_type' => 'free',
            'credits_remaining' => 1, // Free users get 1 free translation
            'email_verified' => false,
            'registration_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'last_login' => null
        ]);
        
        if ($userId) {
            // Log registration event
            $this->logEvent('user_registered', [
                'user_id' => $userId,
                'email' => $email,
                'registration_method' => 'direct'
            ]);
            
            return ['success' => true, 'message' => 'Compte créé avec succès !', 'user_id' => $userId];
        }
        
        if ($userId) {
            // Send welcome email
            require_once __DIR__ . '/../Services/EmailService.php';
            $emailService = new EmailService();
            $emailService->sendWelcomeEmail($userId, $email);
            
            // Log registration event
            $this->logEvent('user_registered', [
                'user_id' => $userId,
                'email' => $email,
                'registration_method' => 'direct'
            ]);
            
            return ['success' => true, 'message' => 'Compte créé avec succès !', 'user_id' => $userId];
        }
        
        return ['success' => false, 'message' => 'Erreur lors de la création du compte'];
    }
    
    public function login($email, $password) {
        // Validate input
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email et mot de passe requis'];
        }
        
        // Find user
        $user = $this->storage->findByEmail($email);
        if (!$user) {
            return ['success' => false, 'message' => 'Email ou mot de passe incorrect'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Email ou mot de passe incorrect'];
        }
        
        // Update last login
        $this->storage->update('users.json', 'users', $user['id'], [
            'last_login' => date('c'),
            'login_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        // Create session
        $sessionToken = $this->createSession($user['id']);
        
        // Log login event
        $this->logEvent('user_login', [
            'user_id' => $user['id'],
            'email' => $email,
            'login_method' => 'password'
        ]);
        
        return [
            'success' => true, 
            'message' => 'Connexion réussie !',
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'credits_remaining' => $user['credits_remaining']
            ],
            'session_token' => $sessionToken
        ];
    }
    
    public function logout() {
        if (isset($_SESSION['session_token'])) {
            // Remove session from storage
            $this->removeSession($_SESSION['session_token']);
        }
        
        // Clear PHP session
        session_destroy();
        
        return ['success' => true, 'message' => 'Déconnexion réussie'];
    }
    
    public function isLoggedIn() {
        if (!isset($_SESSION['session_token']) || !isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Verify session is still valid
        return $this->validateSession($_SESSION['session_token']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->storage->findById('users.json', $_SESSION['user_id']);
    }
    
    private function createSession($userId) {
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('c', time() + $this->config['session_lifetime']);
        
        $this->storage->insert('sessions.json', 'sessions', [
            'session_token' => $sessionToken,
            'user_id' => $userId,
            'expires_at' => $expiresAt,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Set PHP session variables
        $_SESSION['session_token'] = $sessionToken;
        $_SESSION['user_id'] = $userId;
        
        return $sessionToken;
    }
    
    private function validateSession($sessionToken) {
        $sessions = $this->storage->read('sessions.json');
        
        if (isset($sessions['sessions'])) {
            foreach ($sessions['sessions'] as $session) {
                if ($session['session_token'] === $sessionToken) {
                    // Check if session hasn't expired
                    if (strtotime($session['expires_at']) > time()) {
                        return true;
                    } else {
                        // Remove expired session
                        $this->removeSession($sessionToken);
                        return false;
                    }
                }
            }
        }
        
        return false;
    }
    
    private function removeSession($sessionToken) {
        $data = $this->storage->read('sessions.json');
        
        if (isset($data['sessions'])) {
            foreach ($data['sessions'] as $key => $session) {
                if ($session['session_token'] === $sessionToken) {
                    unset($data['sessions'][$key]);
                    $data['sessions'] = array_values($data['sessions']); // Reindex array
                    $this->storage->write('sessions.json', $data);
                    break;
                }
            }
        }
    }
    
    private function logEvent($eventType, $data) {
        $this->storage->insert('analytics.json', 'events', [
            'event_type' => $eventType,
            'data' => $data,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
}
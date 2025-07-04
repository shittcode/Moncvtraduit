<?php
// index.php - Main entry point (UPDATED again)

session_start();

// Load configuration
$config = require_once 'config/app.php';

// Load our JSON storage system
require_once 'src/JsonStorage.php';

// Initialize storage
$storage = new JsonStorage();

// Simple router
$page = $_GET['page'] ?? 'home';

// Check if user is logged in for protected pages
$protectedPages = ['dashboard', 'profile', 'history'];
if (in_array($page, $protectedPages)) {
    require_once 'src/Controllers/AuthController.php';
    $authController = new AuthController();
    if (!$authController->isLoggedIn()) {
        header('Location: index.php?page=login');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['app_name']; ?></title>
    <link rel="stylesheet" href="public/css/main.css">
    <?php if (in_array($page, ['login', 'register', 'forgot-password'])): ?>
        <link rel="stylesheet" href="public/css/auth.css">
    <?php endif; ?>
    <?php if ($page === 'dashboard'): ?>
        <link rel="stylesheet" href="public/css/dashboard.css">
    <?php endif; ?>
</head>
<body>
    <div class="app">
        <?php
        // Simple page routing
        switch($page) {
            case 'login':
                include 'templates/auth/login.php';
                break;
            case 'register':
                include 'templates/auth/register.php';
                break;
            case 'dashboard':
                include 'templates/dashboard/dashboard.php';
                break;
            case 'logout':
                require_once 'src/Controllers/AuthController.php';
                $authController = new AuthController();
                $authController->logout();
                header('Location: index.php');
                exit;
                break;
            default:
                include 'templates/pages/home.php';
        }
        ?>
    </div>
    
    <script src="public/js/main.js"></script>
</body>
</html>
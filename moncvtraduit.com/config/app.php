<?php
// config/app.php - Main application configuration

return [
    // App Settings
    'app_name' => 'CV Professional Translator',
    'app_url' => 'http://localhost/cv-translator', // Change this to your domain later
    'app_version' => '1.0.0',
    
    // DeepSeek AI Configuration
    'deepseek' => [
        'api_key' => 'sk-9f0b771233984ef3a1273a34b31c7163',
        'base_url' => 'https://api.deepseek.com',
        'model' => 'deepseek-chat'
    ],
    
    // Revolut Payment Configuration
    'revolut' => [
        'merchant_id' => 'YOUR_REVOLUT_MERCHANT_ID', // You'll add this later
        'api_key' => 'YOUR_REVOLUT_API_KEY',         // You'll add this later
        'environment' => 'sandbox' // Change to 'production' when going live
    ],
    
    // Pricing
    'pricing' => [
        'cv_translation' => 4.99,
        'currency' => 'EUR',
        'currency_symbol' => '€'
    ],
    
    // Email Configuration
    'email' => [
        'smtp_host' => 'smtp.gmail.com',     // Change to your email provider
        'smtp_port' => 587,
        'smtp_username' => '',               // Add your email
        'smtp_password' => '',               // Add your email password
        'from_email' => 'noreply@yoursite.com',
        'from_name' => 'CV Professional Services'
    ],
    
    // Security
    'session_lifetime' => 3600, // 1 hour
    'max_file_size' => 10485760, // 10MB in bytes
    'allowed_file_types' => ['doc', 'docx'],
    
    // Rate Limiting
    'rate_limits' => [
        'translation' => 5,     // 5 translations per hour
        'file_upload' => 10     // 10 file uploads per hour
    ]
];
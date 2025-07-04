<?php
// src/JsonStorage.php - Handles all JSON file operations

class JsonStorage {
    private $dataDir;
    
    public function __construct() {
        $this->dataDir = __DIR__ . '/../data/';
        $this->ensureDataDirectory();
    }
    
    private function ensureDataDirectory() {
        // Create data directory if it doesn't exist
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
        
        // Create initial JSON files
        $files = [
            'users.json' => ['users' => []],
            'cv_translations.json' => ['translations' => []],
            'payments.json' => ['payments' => []],
            'sessions.json' => ['sessions' => []],
            'invoices.json' => ['invoices' => []],
            'analytics.json' => ['events' => []]
        ];
        
        foreach ($files as $filename => $initialData) {
            $filePath = $this->dataDir . $filename;
            if (!file_exists($filePath)) {
                file_put_contents($filePath, json_encode($initialData, JSON_PRETTY_PRINT));
            }
        }
    }
    
    public function read($filename) {
        $filePath = $this->dataDir . $filename;
        if (!file_exists($filePath)) {
            return [];
        }
        
        $content = file_get_contents($filePath);
        return json_decode($content, true) ?: [];
    }
    
    public function write($filename, $data) {
        $filePath = $this->dataDir . $filename;
        
        // Use file locking for safe concurrent access
        $handle = fopen($filePath, 'c+');
        if (flock($handle, LOCK_EX)) {
            ftruncate($handle, 0);
            fwrite($handle, json_encode($data, JSON_PRETTY_PRINT));
            flock($handle, LOCK_UN);
        }
        fclose($handle);
        return true;
    }
    
    public function insert($filename, $section, $item) {
        $data = $this->read($filename);
        if (!isset($data[$section])) {
            $data[$section] = [];
        }
        
        // Generate unique ID
        $item['id'] = $this->generateId();
        $item['created_at'] = date('c');
        $data[$section][] = $item;
        
        $this->write($filename, $data);
        return $item['id'];
    }
    
    public function findById($filename, $id) {
        $data = $this->read($filename);
        foreach ($data as $items) {
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (isset($item['id']) && $item['id'] === $id) {
                        return $item;
                    }
                }
            }
        }
        return null;
    }
    
    public function findByEmail($email) {
        $data = $this->read('users.json');
        if (isset($data['users'])) {
            foreach ($data['users'] as $user) {
                if ($user['email'] === $email) {
                    return $user;
                }
            }
        }
        return null;
    }
    
    public function update($filename, $section, $id, $updates) {
        $data = $this->read($filename);
        if (!isset($data[$section])) {
            return false;
        }
        
        foreach ($data[$section] as &$item) {
            if ($item['id'] === $id) {
                $item = array_merge($item, $updates);
                $item['updated_at'] = date('c');
                $this->write($filename, $data);
                return true;
            }
        }
        return false;
    }
    
    private function generateId() {
        return uniqid() . '_' . time();
    }
}
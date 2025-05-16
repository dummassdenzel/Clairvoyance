<?php

/**
 * Environment class for managing environment variables
 */
class Environment
{
    private static $instance = null;
    private $variables = [];
    
    /**
     * Private constructor to enforce singleton pattern
     */
    private function __construct()
    {
        // Default environment variables
        $this->variables = [
            'JWT_KEY' => 'your-secret-key-change-this-in-production',
            'DB_HOST' => 'localhost',
            'DB_NAME' => 'clairvoyance',
            'DB_USER' => 'root',
            'DB_PASS' => '',
            'ALLOWED_ORIGINS' => 'http://localhost:5173,http://localhost:4200'
        ];
        
        // Load from .env file if exists
        $this->loadFromEnvFile();
    }
    
    /**
     * Get singleton instance
     * 
     * @return Environment
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Get value of environment variable
     * 
     * @param string $key Key to get
     * @param mixed $default Default value if key not found
     * @return mixed Value of the environment variable
     */
    public function get($key, $default = null)
    {
        return isset($this->variables[$key]) ? $this->variables[$key] : $default;
    }
    
    /**
     * Set value of environment variable
     * 
     * @param string $key Key to set
     * @param mixed $value Value to set
     */
    public function set($key, $value)
    {
        $this->variables[$key] = $value;
    }
    
    /**
     * Load environment variables from .env file
     */
    private function loadFromEnvFile()
    {
        $envFile = __DIR__ . '/../.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // Parse key=value pairs
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^"(.+)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.+)'$/", $value, $matches)) {
                    $value = $matches[1];
                }
                
                $this->variables[$key] = $value;
            }
        }
    }
}
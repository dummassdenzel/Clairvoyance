<?php

require_once __DIR__ . '/../config/Environment.php';

class Jwt
{
    private $key;

    public function __construct()
    {
        try {
            // Get JWT key from environment variables
            $this->key = Environment::getInstance()->get('JWT_KEY');
        } catch (Exception $e) {
            error_log("JWT Error: " . $e->getMessage());
            throw new Exception("Failed to initialize JWT");
        }
    }

    /**
     * Generate a JWT token
     * @param array $payload The data to encode
     * @return string The JWT token
     */
    public function encode(array $payload): string
    {
        return \Firebase\JWT\JWT::encode(
            $payload,
            $this->key,
            'HS256'
        );
    }

    /**
     * Decode a JWT token
     * @param string $token The JWT token to decode
     * @return object The decoded data
     */
    public function decode(string $token): object
    {
        try {
            return \Firebase\JWT\JWT::decode(
                $token,
                new \Firebase\JWT\Key($this->key, 'HS256')
            );
        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }
}

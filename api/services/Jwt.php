<?php

require_once __DIR__ . '/../config/Environment.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;

class JwtService
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
        return FirebaseJWT::encode(
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
            return FirebaseJWT::decode(
                $token,
                new Key($this->key, 'HS256')
            );
        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }
}

<?php

require_once __DIR__ . '/../services/Jwt.php';
require_once __DIR__ . '/../utils/Response.php';

class AuthMiddleware
{
    private $jwt;

    public function __construct()
    {
        $this->jwt = new JwtService();
    }

    /**
     * Authenticate the request and return the decoded token payload
     * 
     * @return object|null The decoded token payload or null if authentication fails
     */
    public function authenticate()
    {
        $headers = getallheaders();

        // Check if Authorization header exists
        if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
            Response::unauthorized('No token provided');
            return null;
        }

        // Get token from Authorization header
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
        $token = str_replace('Bearer ', '', $authHeader);

        // Validate token
        try {
            return $this->jwt->decode($token);
        } catch (Exception $e) {
            Response::unauthorized($e->getMessage());
            return null;
        }
    }
    
    /**
     * Verify if the authenticated user has the required role
     * 
     * @param object $user The authenticated user object
     * @param string|array $roles Required role(s)
     * @return bool True if the user has the required role, false otherwise
     */
    public function verifyRole($user, $roles)
    {
        if (!isset($user->role)) {
            return false;
        }
        
        if (is_array($roles)) {
            return in_array($user->role, $roles);
        }
        
        return $user->role === $roles;
    }
} 
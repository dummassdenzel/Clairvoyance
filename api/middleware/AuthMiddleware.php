<?php

require_once __DIR__ . '/../utils/Response.php';

class AuthMiddleware
{
    public function __construct()
    {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Authenticate the request by checking the session for a user object.
     * 
     * @return object|null The user object from the session or null if not authenticated.
     */
    public function authenticate()
    {
        if (!isset($_SESSION['user'])) {
            Response::unauthorized('You are not logged in.');
            return null; // Important to exit after sending response
        }

        return (object) $_SESSION['user'];
    }
    
    /**
     * Verify if the authenticated user has the required role.
     * 
     * @param object $user The authenticated user object.
     * @param string|array $roles Required role(s).
     * @return bool True if the user has the required role, false otherwise.
     */
    public function verifyRole($user, $roles)
    {
        if (!isset($user->role)) {
            return false;
        }
        
        $userRoles = is_array($user->role) ? $user->role : [$user->role];

        if (is_array($roles)) {
            // Check for any intersection between user's roles and required roles
            return !empty(array_intersect($userRoles, $roles));
        }
        
        // Check if the single required role is in the user's roles
        return in_array($roles, $userRoles);
    }
}
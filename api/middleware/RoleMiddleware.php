<?php

require_once __DIR__ . '/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';

class RoleMiddleware
{
    private $auth;
    
    public function __construct()
    {
        $this->auth = new AuthMiddleware();
    }
    
    /**
     * Check if the user has the required role(s)
     * 
     * @param string|array $roles Required role(s)
     * @return object The authenticated user if they have the required role
     */
    public function requireRole($roles)
    {
        // First authenticate the user
        $user = $this->auth->authenticate();
        if (!$user) {
            return null; // Authentication failed, response already sent
        }
        
        // Then check if the user has the required role
        if (!$this->auth->verifyRole($user, $roles)) {
            Response::error('You do not have permission to access this resource', null, 403);
            return null;
        }
        
        return $user;
    }
    
    /**
     * Check if the user is an admin
     * 
     * @return object The authenticated user if they are an admin
     */
    public function requireAdmin()
    {
        return $this->requireRole('admin');
    }
    
    /**
     * Check if the user is logged in
     * 
     * @return void
     */
    public function requireLogin() {
        if (!isset($_SESSION['user'])) {
            Response::unauthorized('You must be logged in to perform this action.');
            exit();
        }
    }

    /**
     * Check if the user is an editor or admin
     * 
     * @return object The authenticated user if they are an editor or admin
     */
    public function requireEditor()
    {
        return $this->requireRole(['admin', 'editor']);
    }
    
    /**
     * Check if the user is a viewer, editor, or admin
     * 
     * @return object The authenticated user if they are a viewer, editor, or admin
     */
    public function requireViewer()
    {
        return $this->requireRole(['admin', 'editor', 'viewer']);
    }
}
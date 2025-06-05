<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class UserController
{
    private $userModel;
    
    public function __construct()
    {
        $this->userModel = new User();
    }
    
    /**
     * Get all users
     */
    public function getAll()
    {
        try{
            $users = $this->userModel->getAll();
            Response::success('Users retrieved successfully', $users);
        }catch(Exception $e){
            error_log("Error in UserController::getAll: " . $e->getMessage());
            Response::error('Failed to retrieve users: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a specific user by ID
     * 
     * @param int $id User ID
     */
    public function getOne($id)
    {
        if (!Validator::isNumeric($id)) {
            Response::error('Invalid user ID');
            return;
        }
        
        try{
            $user = $this->userModel->findById($id);
            
            if (!$user) {
                Response::notFound('User not found');
                return;
            }
            
            // Remove sensitive data
            unset($user['password_hash']);
            
            Response::success('User retrieved successfully', $user);
        } catch (Exception $e) {
            error_log("Error in UserController::getOne: " . $e->getMessage());
            Response::error('Failed to retrieve user: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new user
     * 
     * @param array $data User data
     */
    public function create($data)
    {
        // Validate required fields
        $validation = Validator::validateRequired($data, ['username', 'email', 'password', 'role']);
        if (!$validation['isValid']) {
            Response::error($validation['message']);
            return;
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email format');
            return;
        }
        
        // Check if username already exists
        if ($this->userModel->findByUsername($data['username'])) {
            Response::error('Username already exists');
            return;
        }
        
        // Check if email already exists
        if ($this->userModel->findByEmail($data['email'])) {
            Response::error('Email already exists');
            return;
        }
        
        // Validate role
        if (!in_array($data['role'], ['admin', 'editor', 'viewer'])) {
            Response::error('Invalid role. Must be "admin", "editor", or "viewer"');
            return;
        }
        
        // Hash password
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);
        
        // Create user
        $result = $this->userModel->create($data);
        
        if (!$result) {
            Response::error('Failed to create user');
            return;
        }
        
        // Remove sensitive data
        unset($result['password_hash']);
        
        Response::success('User created successfully', $result, 201);
    }
    
    /**
     * Update an existing user
     * 
     * @param int $id User ID
     * @param array $data Updated user data
     */
    public function update($id, $data)
    {
        if (!Validator::isNumeric($id)) {
            Response::error('Invalid user ID');
            return;
        }
        
        // Check if user exists
        $user = $this->userModel->findById($id);
        if (!$user) {
            Response::notFound('User not found');
            return;
        }
        
        // Validate email if provided
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email format');
            return;
        }
        
        // Check if username already exists (if changing username)
        if (isset($data['username']) && $data['username'] !== $user['username']) {
            if ($this->userModel->findByUsername($data['username'])) {
                Response::error('Username already exists');
                return;
            }
        }
        
        // Check if email already exists (if changing email)
        if (isset($data['email']) && $data['email'] !== $user['email']) {
            if ($this->userModel->findByEmail($data['email'])) {
                Response::error('Email already exists');
                return;
            }
        }
        
        // Validate role if provided
        if (isset($data['role']) && !in_array($data['role'], ['admin', 'editor', 'viewer'])) {
            Response::error('Invalid role. Must be "admin", "editor", or "viewer"');
            return;
        }
        
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }
        
        // Update user
        $result = $this->userModel->update($id, $data);
        
        if (!$result) {
            Response::error('Failed to update user');
            return;
        }
        
        Response::success('User updated successfully');
    }
    
    /**
     * Delete a user
     * 
     * @param int $id User ID
     */
    public function delete($id)
    {
        if (!Validator::isNumeric($id)) {
            Response::error('Invalid user ID');
            return;
        }
        
        // Check if user exists
        $user = $this->userModel->findById($id);
        if (!$user) {
            Response::notFound('User not found');
            return;
        }
        
        // Delete user
        $result = $this->userModel->delete($id);
        
        if (!$result) {
            Response::error('Failed to delete user');
            return;
        }
        
        Response::success('User deleted successfully');
    }
}

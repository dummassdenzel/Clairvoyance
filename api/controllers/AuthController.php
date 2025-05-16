<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/Jwt.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class AuthController
{
    private $userModel;
    private $jwt;
    private $auth;
    
    public function __construct()
    {
        $this->userModel = new User();
        $this->jwt = new JwtService();
        $this->auth = new AuthMiddleware();
    }
    
    /**
     * Register a new user
     * 
     * @param array $data User registration data
     */
    public function register($data)
    {
        // Validate required fields
        $validation = Validator::validateRequired($data, ['username', 'email', 'password']);
        if (!$validation['isValid']) {
            Response::error($validation['message']);
            return;
        }
        
        // Validate email format
        if (!Validator::isValidEmail($data['email'])) {
            Response::error('Invalid email format');
            return;
        }
        
        // Sanitize data
        $data = Validator::sanitize($data);
        
        // Check if username or email already exists
        if ($this->userModel->findByUsername($data['username'])) {
            Response::error('Username already exists');
            return;
        }
        
        if ($this->userModel->findByEmail($data['email'])) {
            Response::error('Email already exists');
            return;
        }
        
        // Hash password
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);
        
        // Set default role to 'viewer' if not provided
        if (!isset($data['role']) || !in_array($data['role'], ['admin', 'viewer'])) {
            $data['role'] = 'viewer';
        }
        
        // Create user
        $user = $this->userModel->create($data);
        if (!$user) {
            Response::error('Failed to create user');
            return;
        }
        
        // Remove password_hash from response
        unset($user['password_hash']);
        
        Response::success('User registered successfully', $user, 201);
    }
    
    /**
     * Login a user
     * 
     * @param array $data User login data
     */
    public function login($data)
    {
        // Validate required fields
        $validation = Validator::validateRequired($data, ['username', 'password']);
        if (!$validation['isValid']) {
            Response::error($validation['message']);
            return;
        }
        
        // Get user by username
        $user = $this->userModel->findByUsername($data['username']);
        if (!$user) {
            Response::error('Invalid username or password', null, 401);
            return;
        }
        
        // Verify password
        if (!password_verify($data['password'], $user['password_hash'])) {
            Response::error('Invalid username or password', null, 401);
            return;
        }
        
        // Generate JWT token
        $payload = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'exp' => time() + (60 * 60 * 24) // Token expires in 24 hours
        ];
        
        $token = $this->jwt->encode($payload);
        
        // Remove password_hash from response
        unset($user['password_hash']);
        
        Response::success('Login successful', [
            'user' => $user,
            'token' => $token
        ]);
    }
    
    /**
     * Verify JWT token
     */
    public function verifyToken()
    {
        $user = $this->auth->authenticate();
        if ($user) {
            Response::success('Token is valid', [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role
                ]
            ]);
        }
    }
} 
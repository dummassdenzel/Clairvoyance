<?php

class AuthMiddleware
{
    private $jwt;

    public function __construct()
    {
        $this->jwt = new Jwt();
    }

    public function authenticateRequest()
    {
        $headers = getallheaders();

        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['message' => 'No token provided']);
            exit();
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);

        try {
            $decoded = $this->jwt->decode($token);
            return $decoded;
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(['message' => $e->getMessage()]);
            exit();
        }
    }
}
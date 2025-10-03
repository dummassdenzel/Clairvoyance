<?php

namespace Core;

abstract class BaseController
{
    protected Container $container;

    public function __construct()
    {
        $this->container = Application::getInstance()->getContainer();
    }

    protected function getService(string $serviceClass)
    {
        return $this->container->resolve($serviceClass);
    }

    protected function getModel(string $modelClass)
    {
        return $this->container->resolve($modelClass);
    }

    protected function getCurrentUser(): ?array
    {
        if (!isset($_SESSION['user'])) {
            return null;
        }
        return $_SESSION['user'];
    }

    protected function requireAuth(): void
    {
        if (!isset($_SESSION['user'])) {
            $this->jsonResponse(['error' => 'Authentication required'], 401);
            exit;
        }
    }

    protected function requireRole(string $role): void
    {
        $this->requireAuth();
        
        if ($_SESSION['user']['role'] !== $role && $_SESSION['user']['role'] !== 'admin') {
            $this->jsonResponse(['error' => 'Insufficient permissions'], 403);
            exit;
        }
    }

    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function getRequestData(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    protected function validateRequired(array $data, array $required): bool
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        return true;
    }
}

<?php

namespace Controllers;

use Core\BaseController;
use Services\DashboardService;
use Services\ShareTokenService;
use Services\AuthService;

class DashboardController extends BaseController
{
    private DashboardService $dashboardService;
    private ShareTokenService $shareTokenService;
    private AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->dashboardService = $this->getService(\Services\DashboardService::class);
        $this->shareTokenService = $this->getService(\Services\ShareTokenService::class);
        $this->authService = $this->getService(\Services\AuthService::class);
    }

    public function create(): void
    {
        try {
            $this->authService->requireRole('editor');
            
            $data = $this->getRequestData();
            
            if (!$this->validateRequired($data, ['name', 'layout'])) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Missing required fields: name, layout'
                ], 400);
                return;
            }

            $currentUser = $this->getCurrentUser();
            
            $result = $this->dashboardService->create($currentUser, $data);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Dashboard created successfully',
                'data' => ['id' => $result['id']]
            ], 201);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function get(int $id): void
    {
        try {
            $this->authService->requireAuth();
            
            if (!$id) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Missing dashboard ID'
                ], 400);
                return;
            }

            $currentUser = $this->getCurrentUser();
            
            $dashboard = $this->dashboardService->get($currentUser, $id);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Dashboard retrieved successfully',
                'data' => ['dashboard' => $dashboard]
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 403);
        }
    }

    public function listAll(): void
    {
        try {
            $this->authService->requireAuth();
            
            $currentUser = $this->getCurrentUser();
            
            $dashboards = $this->dashboardService->list($currentUser);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Dashboards retrieved successfully',
                'data' => ['dashboards' => $dashboards]
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function update(int $id): void
    {
        try {
            $this->authService->requireRole('editor');
            
            if (!$id) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Missing dashboard ID'
                ], 400);
                return;
            }

            $data = $this->getRequestData();
            
            if (empty($data)) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'No update data provided'
                ], 400);
                return;
            }

            $currentUser = $this->getCurrentUser();
            
            $this->dashboardService->update($currentUser, $id, $data);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Dashboard updated successfully'
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function delete(int $id): void
    {
        try {
            $this->authService->requireRole('editor');
            
            if (!$id) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Missing dashboard ID'
                ], 400);
                return;
            }

            $currentUser = $this->getCurrentUser();
            
            $this->dashboardService->delete($currentUser, $id);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Dashboard deleted successfully'
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function assignViewer(int $dashboardId): void
    {
        try {
            $this->authService->requireRole('editor');
            
            $data = $this->getRequestData();
            
            if (!$this->validateRequired($data, ['user_id'])) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Missing required field: user_id'
                ], 400);
                return;
            }

            $currentUser = $this->getCurrentUser();
            
            $this->dashboardService->addViewer($currentUser, $dashboardId, (int)$data['user_id']);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Viewer assigned successfully'
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function generateShareLink(int $dashboardId): void
    {
        try {
            $this->authService->requireRole('editor');
            
            $currentUser = $this->getCurrentUser();
            
            $result = $this->shareTokenService->generate($currentUser, $dashboardId);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Share token created successfully',
                'data' => ['token' => $result['token']]
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 403);
        }
    }

    public function redeemShareLink(string $token): void
    {
        try {
            $this->authService->requireAuth();
            
            $currentUser = $this->getCurrentUser();
            
            $dashboardId = $this->shareTokenService->redeem($currentUser, $token);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Share token redeemed successfully',
                'data' => ['dashboard_id' => $dashboardId]
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function getReportData(int $id): void
    {
        try {
            $this->authService->requireAuth();
            
            // Note: This method needs to be implemented in DashboardService
            // For now, we'll return a placeholder response
            $this->jsonResponse([
                'success' => true,
                'message' => 'Report data retrieved successfully',
                'data' => ['report_data' => []]
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 403);
        }
    }

    public function removeViewer(int $dashboardId, int $userId): void
    {
        try {
            $this->authService->requireRole('editor');
            
            $currentUser = $this->getCurrentUser();
            
            $this->dashboardService->removeViewer($currentUser, $dashboardId, $userId);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Viewer removed successfully'
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }
}
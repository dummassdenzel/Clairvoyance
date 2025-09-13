<?php

namespace Controllers;

use Core\BaseController;
use Services\KpiService;
use Services\KpiEntryService;
use Services\AuthService;

class KpiController extends BaseController
{
    private KpiService $kpiService;
    private KpiEntryService $kpiEntryService;
    private AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->kpiService = $this->getService(\Services\KpiService::class);
        $this->kpiEntryService = $this->getService(\Services\KpiEntryService::class);
        $this->authService = $this->getService(\Services\AuthService::class);
    }

    public function getOne(int $kpiId): void
    {
        try {
            $this->authService->requireAuth();
            
            if (!$kpiId) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Missing KPI ID'
                ], 400);
                return;
            }

            $currentUser = $this->getCurrentUser();
            
            $kpi = $this->kpiService->get($currentUser, $kpiId);

            $this->jsonResponse([
                'success' => true,
                'message' => 'KPI retrieved successfully',
                'data' => ['kpi' => $kpi]  // Fix: Wrap in 'kpi' property
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 404);
        }
    }

    public function update(int $kpiId): void
    {
        try {
            $this->authService->requireRole('editor');
            
            if (!$kpiId) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Missing KPI ID'
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

            // Provide defaults for optional fields
            $data['direction'] = $data['direction'] ?? 'higher_is_better';
            $data['format_prefix'] = $data['format_prefix'] ?? null;
            $data['format_suffix'] = $data['format_suffix'] ?? null;

            $currentUser = $this->getCurrentUser();
            
            $this->kpiService->update($currentUser, $kpiId, $data);

            $this->jsonResponse([
                'success' => true,
                'message' => 'KPI updated successfully'
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function create(): void
    {
        try {
            $this->authService->requireRole('editor');
            
            $data = $this->getRequestData();
            
            if (!$this->validateRequired($data, ['name', 'target', 'rag_red', 'rag_amber'])) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Missing required fields: name, target, rag_red, rag_amber'
                ], 400);
                return;
            }

            // Provide defaults for optional fields
            $data['direction'] = $data['direction'] ?? 'higher_is_better';
            $data['format_prefix'] = $data['format_prefix'] ?? null;
            $data['format_suffix'] = $data['format_suffix'] ?? null;

            $currentUser = $this->getCurrentUser();
            
            $result = $this->kpiService->create($currentUser, $data);

            $this->jsonResponse([
                'success' => true,
                'message' => 'KPI created successfully',
                'data' => ['id' => $result['id']]
            ], 201);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function delete(int $kpiId): void
    {
        try {
            $this->authService->requireRole('editor');
            
            if (!$kpiId) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Missing KPI ID'
                ], 400);
                return;
            }

            $currentUser = $this->getCurrentUser();
            
            $this->kpiService->delete($currentUser, $kpiId);

            $this->jsonResponse([
                'success' => true,
                'message' => 'KPI deleted successfully'
            ], 204);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    public function listAll(): void
    {
        try {
            $this->authService->requireAuth();
            
            $currentUser = $this->getCurrentUser();
            
            $kpis = $this->kpiService->list($currentUser);

            $this->jsonResponse([
                'success' => true,
                'message' => 'KPIs retrieved successfully',
                'data' => $kpis
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function getAggregate(int $kpiId): void
    {
        try {
            $this->authService->requireAuth();
            
            $aggregationType = $_GET['type'] ?? null;
            $validTypes = ['sum', 'average', 'latest'];

            if (!$aggregationType || !in_array($aggregationType, $validTypes)) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Invalid or missing aggregation type'
                ], 400);
                return;
            }

            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;

            $currentUser = $this->getCurrentUser();
            
            $result = $this->kpiEntryService->aggregate($currentUser, $kpiId, $aggregationType, $startDate, $endDate);

            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Aggregate value retrieved successfully',
                    'data' => $result
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Could not retrieve aggregate value'
                ], 500);
            }

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function listEntries(int $kpiId): void
    {
        try {
            $this->authService->requireAuth();
            
            // Get optional query parameters for date filtering
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;

            $currentUser = $this->getCurrentUser();
            
            $result = $this->kpiEntryService->query($currentUser, $kpiId, $startDate, $endDate);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Entries retrieved successfully',
                'data' => ['entries' => $result]  // Fix: Wrap in 'entries' property
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }
}
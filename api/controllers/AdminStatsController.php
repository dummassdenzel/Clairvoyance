<?php

namespace Controllers;

use Core\BaseController;
use Services\AuthService;

class AdminStatsController extends BaseController {
    private AuthService $authService;

    public function __construct() {
        parent::__construct();
        $this->authService = $this->getService(AuthService::class);
    }

    public function getSystemStats(): void
    {
        try {
            $this->authService->requireRole('admin');
            
            $db = $this->getService(\PDO::class);
            
            // Get system statistics
            $stats = [
                'users' => $this->getUserStats($db),
                'dashboards' => $this->getDashboardStats($db),
                'kpis' => $this->getKpiStats($db),
                'recent_activity' => $this->getRecentActivity($db)
            ];

            $this->jsonResponse([
                'success' => true,
                'message' => 'System statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    private function getUserStats(\PDO $db): array
    {
        try {
            // Total users
            $stmt = $db->query('SELECT COUNT(*) as total FROM users');
            $totalUsers = $stmt->fetch()['total'];

            // Users by role
            $stmt = $db->query('SELECT role, COUNT(*) as count FROM users GROUP BY role');
            $usersByRole = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

            // New users this week
            $stmt = $db->query('SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
            $newUsersThisWeek = $stmt->fetch()['count'];

            // New users this month
            $stmt = $db->query('SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
            $newUsersThisMonth = $stmt->fetch()['count'];

            return [
                'total' => (int)$totalUsers,
                'by_role' => [
                    'admin' => (int)($usersByRole['admin'] ?? 0),
                    'editor' => (int)($usersByRole['editor'] ?? 0)
                ],
                'new_this_week' => (int)$newUsersThisWeek,
                'new_this_month' => (int)$newUsersThisMonth
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'by_role' => ['admin' => 0, 'editor' => 0],
                'new_this_week' => 0,
                'new_this_month' => 0
            ];
        }
    }

    private function getDashboardStats(\PDO $db): array
    {
        try {
            // Total dashboards
            $stmt = $db->query('SELECT COUNT(*) as total FROM dashboards');
            $totalDashboards = $stmt->fetch()['total'];

            // Dashboards created this week
            $stmt = $db->query('SELECT COUNT(*) as count FROM dashboards WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
            $newThisWeek = $stmt->fetch()['count'];

            // Dashboards created this month
            $stmt = $db->query('SELECT COUNT(*) as count FROM dashboards WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
            $newThisMonth = $stmt->fetch()['count'];

            // Most active dashboard owners
            $stmt = $db->query('
                SELECT u.email, COUNT(d.id) as dashboard_count 
                FROM users u 
                LEFT JOIN dashboards d ON u.id = d.user_id 
                GROUP BY u.id, u.email 
                ORDER BY dashboard_count DESC 
                LIMIT 5
            ');
            $topOwners = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return [
                'total' => (int)$totalDashboards,
                'new_this_week' => (int)$newThisWeek,
                'new_this_month' => (int)$newThisMonth,
                'top_owners' => $topOwners
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'new_this_week' => 0,
                'new_this_month' => 0,
                'top_owners' => []
            ];
        }
    }

    private function getKpiStats(\PDO $db): array
    {
        try {
            // Total KPIs
            $stmt = $db->query('SELECT COUNT(*) as total FROM kpis');
            $totalKpis = $stmt->fetch()['total'];

            // KPIs created this week
            $stmt = $db->query('SELECT COUNT(*) as count FROM kpis WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
            $newThisWeek = $stmt->fetch()['count'];

            // KPIs created this month
            $stmt = $db->query('SELECT COUNT(*) as count FROM kpis WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
            $newThisMonth = $stmt->fetch()['count'];

            // Total KPI entries
            $stmt = $db->query('SELECT COUNT(*) as total FROM kpi_entries');
            $totalEntries = $stmt->fetch()['total'];

            // Entries this week
            $stmt = $db->query('SELECT COUNT(*) as count FROM kpi_entries WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
            $entriesThisWeek = $stmt->fetch()['count'];

            return [
                'total' => (int)$totalKpis,
                'new_this_week' => (int)$newThisWeek,
                'new_this_month' => (int)$newThisMonth,
                'total_entries' => (int)$totalEntries,
                'entries_this_week' => (int)$entriesThisWeek
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'new_this_week' => 0,
                'new_this_month' => 0,
                'total_entries' => 0,
                'entries_this_week' => 0
            ];
        }
    }

    private function getRecentActivity(\PDO $db): array
    {
        try {
            // Recent user registrations
            $stmt = $db->query('
                SELECT 
                    "user_registered" as type, 
                    CONCAT("New user ", email, " registered as ", role) as description, 
                    created_at as timestamp,
                    email,
                    role
                FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY created_at DESC 
                LIMIT 10
            ');
            $userActivity = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Recent dashboard creations
            $stmt = $db->query('
                SELECT 
                    "dashboard_created" as type, 
                    CONCAT("Dashboard \"", name, "\" created by ", u.email) as description, 
                    d.created_at as timestamp,
                    u.email,
                    d.name
                FROM dashboards d
                JOIN users u ON d.user_id = u.id
                WHERE d.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY d.created_at DESC 
                LIMIT 10
            ');
            $dashboardActivity = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Recent KPI creations
            $stmt = $db->query('
                SELECT 
                    "kpi_created" as type, 
                    CONCAT("KPI \"", name, "\" created by ", u.email) as description, 
                    k.created_at as timestamp,
                    u.email,
                    k.name
                FROM kpis k
                JOIN users u ON k.user_id = u.id
                WHERE k.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY k.created_at DESC 
                LIMIT 10
            ');
            $kpiActivity = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Recent KPI entries
            $stmt = $db->query('
                SELECT 
                    "kpi_entry_added" as type, 
                    CONCAT("KPI entry added to \"", k.name, "\" by ", u.email) as description, 
                    ke.created_at as timestamp,
                    u.email,
                    k.name
                FROM kpi_entries ke
                JOIN kpis k ON ke.kpi_id = k.id
                JOIN users u ON k.user_id = u.id
                WHERE ke.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY ke.created_at DESC 
                LIMIT 10
            ');
            $kpiEntryActivity = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Combine and sort by timestamp
            $allActivity = array_merge($userActivity, $dashboardActivity, $kpiActivity, $kpiEntryActivity);
            usort($allActivity, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });

            return array_slice($allActivity, 0, 15);
        } catch (\Exception $e) {
            return [];
        }
    }
}

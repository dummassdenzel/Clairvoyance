<?php
class Dashboard {
    private $db;
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = (new Connection())->connect();
    }
    public function create($name, $layout, $user_id) {
        try {
            $stmt = $this->db->prepare('INSERT INTO dashboards (name, layout, user_id) VALUES (?, ?, ?)');
            $stmt->execute([$name, $layout, $user_id]);
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function getById($id, $user_id, $role) {
        try {
            $stmt = $this->db->prepare('SELECT * FROM dashboards WHERE id = ?');
            $stmt->execute([$id]);
            $dashboard = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$dashboard) {
                return ['success' => false, 'error' => 'Dashboard not found'];
            }
            // Fetch assigned viewers
            $stmt = $this->db->prepare('SELECT u.id, u.email FROM dashboard_access da JOIN users u ON da.user_id = u.id WHERE da.dashboard_id = ?');
            $stmt->execute([$id]);
            $dashboard['viewers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($role === 'editor' && $dashboard['user_id'] == $user_id) {
                return ['success' => true, 'dashboard' => $dashboard];
            }
            // Check dashboard_access for viewers
            $stmt = $this->db->prepare('SELECT * FROM dashboard_access WHERE dashboard_id = ? AND user_id = ?');
            $stmt->execute([$id, $user_id]);
            if ($stmt->fetch()) {
                return ['success' => true, 'dashboard' => $dashboard];
            }
            return ['success' => false, 'error' => 'Access denied'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function assignViewer($dashboard_id, $user_id) {
        try {
            // Check if already assigned
            $stmt = $this->db->prepare('SELECT * FROM dashboard_access WHERE dashboard_id = ? AND user_id = ?');
            $stmt->execute([$dashboard_id, $user_id]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Viewer already assigned to this dashboard'];
            }
            $stmt = $this->db->prepare('INSERT INTO dashboard_access (dashboard_id, user_id) VALUES (?, ?)');
            $stmt->execute([$dashboard_id, $user_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function listAll($user_id, $role) {
        try {
            if ($role === 'editor') {
                $stmt = $this->db->prepare('SELECT * FROM dashboards WHERE user_id = ?');
                $stmt->execute([$user_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->db->prepare('SELECT d.* FROM dashboards d JOIN dashboard_access da ON d.id = da.dashboard_id WHERE da.user_id = ?');
                $stmt->execute([$user_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            return [];
        }
    }
    public function removeViewer($dashboard_id, $user_id) {
        try {
            $stmt = $this->db->prepare('DELETE FROM dashboard_access WHERE dashboard_id = ? AND user_id = ?');
            $stmt->execute([$dashboard_id, $user_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function update($id, $data, $user_id) {
        try {
            $stmt = $this->db->prepare('SELECT user_id FROM dashboards WHERE id = ?');
            $stmt->execute([$id]);
            $dashboard = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dashboard) {
                return ['success' => false, 'error' => 'Dashboard not found'];
            }
            if ($dashboard['user_id'] != $user_id) {
                return ['success' => false, 'error' => 'Access denied'];
            }

            $fields = [];
            $params = [];
            foreach ($data as $key => $value) {
                if (in_array($key, ['name', 'layout'])) {
                    $fields[] = "$key = ?";
                    $params[] = is_array($value) ? json_encode($value) : $value;
                }
            }

            if (empty($fields)) {
                return ['success' => false, 'error' => 'No valid fields to update'];
            }

            $params[] = $id;
            $sql = 'UPDATE dashboards SET ' . implode(', ', $fields) . ' WHERE id = ?';

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createShareToken($dashboard_id, $user_id) {
        try {
            // First, verify the user owns the dashboard
            $stmt = $this->db->prepare('SELECT user_id FROM dashboards WHERE id = ?');
            $stmt->execute([$dashboard_id]);
            $dashboard = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dashboard || $dashboard['user_id'] != $user_id) {
                return ['success' => false, 'error' => 'Access denied or dashboard not found'];
            }

            // Generate a secure token and set expiration for 7 days
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));

            $stmt = $this->db->prepare('INSERT INTO dashboard_share_tokens (dashboard_id, token, expires_at) VALUES (?, ?, ?)');
            $stmt->execute([$dashboard_id, $token, $expires_at]);

            return ['success' => true, 'token' => $token];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function redeemToken($token, $user_id) {
        try {
            // Find the token and check if it's valid and not expired
            $stmt = $this->db->prepare('SELECT * FROM dashboard_share_tokens WHERE token = ? AND expires_at > NOW()');
            $stmt->execute([$token]);
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tokenData) {
                return ['success' => false, 'error' => 'Invalid or expired token'];
            }

            $dashboard_id = $tokenData['dashboard_id'];
            $result = $this->assignViewer($dashboard_id, $user_id);

            // If access was granted or already existed, delete the token
            if ($result['success'] || strpos($result['error'], 'already assigned') !== false) {
                $stmt = $this->db->prepare('DELETE FROM dashboard_share_tokens WHERE id = ?');
                $stmt->execute([$tokenData['id']]);
                
                // Return success even if already assigned
                return ['success' => true, 'dashboard_id' => $dashboard_id, 'message' => 'Access granted successfully.'];
            }

            return $result; // Propagate other errors from assignViewer
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getReportData($id, $user_id, $role, $startDate = null, $endDate = null) {
        require_once __DIR__ . '/Kpi.php';
        require_once __DIR__ . '/KpiEntry.php';

        try {
            // First, get the dashboard and verify access rights.
            $dashboardResult = $this->getById($id, $user_id, $role);
            if (!$dashboardResult['success']) {
                return $dashboardResult; // Return original error if access is denied or not found.
            }
            $dashboard = $dashboardResult['dashboard'];

            $layout = json_decode($dashboard['layout'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['success' => false, 'error' => 'Invalid dashboard layout JSON.'];
            }

            $kpiModel = new Kpi();
            $kpiEntryModel = new KpiEntry();
            $widgetsData = [];

            foreach ($layout as $widget) {
                if (isset($widget['kpi_id'])) {
                    $kpi_id = $widget['kpi_id'];
                    $kpiDetails = $kpiModel->getById($kpi_id, $user_id);

                    if ($kpiDetails) {
                        $widget['kpi_details'] = $kpiDetails;
                        
                        // Use widget-specific date range, falling back to the report's global date range.
                        $widgetStartDate = !empty($widget['startDate']) ? $widget['startDate'] : $startDate;
                        $widgetEndDate = !empty($widget['endDate']) ? $widget['endDate'] : $endDate;

                        if (isset($widget['aggregation'])) {
                            $widget['data'] = $kpiEntryModel->getAggregateValue($kpi_id, $widget['aggregation'], $widgetStartDate, $widgetEndDate);
                        } else {
                            $widget['data'] = $kpiEntryModel->listByKpiId($kpi_id, $widgetStartDate, $widgetEndDate);
                        }
                    }
                }
                $widgetsData[] = $widget;
            }

            $dashboard['widgets'] = $widgetsData;
            unset($dashboard['layout']); // Remove original layout string to avoid redundancy.

            return ['success' => true, 'report_data' => $dashboard];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function delete($id, $user_id) {
        try {
            $stmt = $this->db->prepare('SELECT user_id FROM dashboards WHERE id = ?');
            $stmt->execute([$id]);
            $dashboard = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dashboard) {
                return ['success' => false, 'error' => 'Dashboard not found'];
            }
            if ($dashboard['user_id'] != $user_id) {
                return ['success' => false, 'error' => 'Access denied'];
            }

            $stmt = $this->db->prepare('DELETE FROM dashboard_access WHERE dashboard_id = ?');
            $stmt->execute([$id]);

            $stmt = $this->db->prepare('DELETE FROM dashboards WHERE id = ?');
            $stmt->execute([$id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
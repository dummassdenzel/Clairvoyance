<?php

namespace Services;

use Models\Kpi;
use PDO;

class KpiService
{
	private Kpi $kpis;
	private PDO $db;

	public function __construct(Kpi $kpis, PDO $db)
	{
		$this->kpis = $kpis;
		$this->db = $db;
	}

	public function create(array $currentUser, array $data): array
	{
		if (!in_array($currentUser['role'], ['admin','editor'])) {
			throw new \Exception('Insufficient permissions', 403);
		}

		$required = ['name','direction','target','rag_red','rag_amber','format_prefix','format_suffix'];
		foreach ($required as $f) {
			if (!isset($data[$f])) throw new \Exception("Missing field: {$f}", 400);
		}

		if (!$this->kpis->validateName($data['name'])) throw new \Exception('Invalid KPI name', 400);
		if (!$this->kpis->validateDirection($data['direction'])) throw new \Exception('Invalid direction', 400);
		if (!$this->kpis->validateTarget((float)$data['target'])) throw new \Exception('Invalid target', 400);
		if (!$this->kpis->validateRagThresholds((float)$data['rag_red'], (float)$data['rag_amber'])) throw new \Exception('Invalid RAG thresholds', 400);
		if (!$this->kpis->validateFormatPrefix($data['format_prefix'])) throw new \Exception('Invalid format prefix', 400);
		if (!$this->kpis->validateFormatSuffix($data['format_suffix'])) throw new \Exception('Invalid format suffix', 400);

		return $this->kpis->create($data, (int)$currentUser['id']);
	}

	public function get(array $currentUser, int $kpiId): array
	{
		$kpi = $this->kpis->findById($kpiId);
		if (!$kpi) throw new \Exception('KPI not found', 404);

		// Admin can access any KPI
		if ($currentUser['role'] === 'admin') {
			return $kpi;
		}

		// KPI owner can access their own KPIs
		if ((int)$kpi['user_id'] === (int)$currentUser['id']) {
			return $kpi;
		}

		// Check if user has access to any dashboard that contains this KPI
		if ($this->hasKpiAccessThroughDashboard($currentUser['id'], $kpiId)) {
			return $kpi;
		}

		throw new \Exception('Access denied', 403);
	}

	public function list(array $currentUser): array
	{
		if ($currentUser['role'] === 'admin') {
			return $this->kpis->findAll();
		}
		return $this->kpis->findByUserId((int)$currentUser['id']);
	}

	public function update(array $currentUser, int $kpiId, array $data): bool
	{
		$kpi = $this->kpis->findById($kpiId);
		if (!$kpi) throw new \Exception('KPI not found', 404);
		if ($currentUser['role'] !== 'admin' && (int)$kpi['user_id'] !== (int)$currentUser['id']) {
			throw new \Exception('Access denied', 403);
		}

		$allowed = ['name','direction','target','rag_red','rag_amber','format_prefix','format_suffix'];
		foreach ($data as $k => $v) {
			if (!in_array($k, $allowed)) unset($data[$k]);
		}

		if (isset($data['name']) && !$this->kpis->validateName($data['name'])) throw new \Exception('Invalid KPI name', 400);
		if (isset($data['direction']) && !$this->kpis->validateDirection($data['direction'])) throw new \Exception('Invalid direction', 400);
		if (isset($data['target']) && !$this->kpis->validateTarget((float)$data['target'])) throw new \Exception('Invalid target', 400);
		if ((isset($data['rag_red']) || isset($data['rag_amber'])) &&
			!$this->kpis->validateRagThresholds(
				isset($data['rag_red']) ? (float)$data['rag_red'] : (float)$kpi['rag_red'],
				isset($data['rag_amber']) ? (float)$data['rag_amber'] : (float)$kpi['rag_amber']
			)) {
			throw new \Exception('Invalid RAG thresholds', 400);
		}
		if (isset($data['format_prefix']) && !$this->kpis->validateFormatPrefix($data['format_prefix'])) throw new \Exception('Invalid format prefix', 400);
		if (isset($data['format_suffix']) && !$this->kpis->validateFormatSuffix($data['format_suffix'])) throw new \Exception('Invalid format suffix', 400);

		return $this->kpis->update($kpiId, $data);
	}

	public function delete(array $currentUser, int $kpiId): bool
	{
		$kpi = $this->kpis->findById($kpiId);
		if (!$kpi) throw new \Exception('KPI not found', 404);
		if ($currentUser['role'] !== 'admin' && (int)$kpi['user_id'] !== (int)$currentUser['id']) {
			throw new \Exception('Access denied', 403);
		}
		return $this->kpis->delete($kpiId);
	}

	/**
	 * Check if user has access to a KPI through dashboard permissions
	 */
	private function hasKpiAccessThroughDashboard(int $userId, int $kpiId): bool
	{
		try {
			// Get all dashboards the user has access to
			$stmt = $this->db->prepare('
				SELECT DISTINCT d.id, d.layout
				FROM dashboards d 
				LEFT JOIN dashboard_access da ON d.id = da.dashboard_id AND da.user_id = ?
				WHERE d.user_id = ? OR da.user_id = ?
			');
			$stmt->execute([$userId, $userId, $userId]);
			$dashboards = $stmt->fetchAll(PDO::FETCH_ASSOC);

			// Check if any dashboard layout contains the KPI
			foreach ($dashboards as $dashboard) {
				$layout = json_decode($dashboard['layout'], true);
				if (is_array($layout)) {
					foreach ($layout as $widget) {
						if (isset($widget['kpi_id']) && (int)$widget['kpi_id'] === $kpiId) {
							return true;
						}
					}
				}
			}

			return false;
		} catch (\Exception $e) {
			return false;
		}
	}
}
<?php

namespace Services;

use Models\Kpi;
use Models\KpiEntry;
use PDO;

class KpiEntryService
{
	private Kpi $kpis;
	private KpiEntry $entries;
	private PDO $db;

	public function __construct(Kpi $kpis, KpiEntry $entries, PDO $db)
	{
		$this->kpis = $kpis;
		$this->entries = $entries;
		$this->db = $db;
	}

	private function ensureOwnership(array $currentUser, int $kpiId): void
	{
		$kpi = $this->kpis->findById($kpiId);
		if (!$kpi) throw new \Exception('KPI not found', 404);

		// Admin can access any KPI
		if ($currentUser['role'] === 'admin') {
			return;
		}

		// KPI owner can access their own KPIs
		if ((int)$kpi['user_id'] === (int)$currentUser['id']) {
			return;
		}

		// Check if user has access to any dashboard that contains this KPI
		if ($this->hasKpiAccessThroughDashboard($currentUser['id'], $kpiId)) {
			return;
		}

		throw new \Exception('Access denied', 403);
	}

	public function add(array $currentUser, int $kpiId, string $date, $value): array
	{
		$this->ensureOwnership($currentUser, $kpiId);

		if (!$this->entries->validateDate($date)) throw new \Exception('Invalid date (YYYY-MM-DD)', 400);
		if (!$this->entries->validateValue($value)) throw new \Exception('Invalid value', 400);
		// Removed: Allow multiple entries for the same date
		// if ($this->entries->hasEntryForDate($kpiId, $date)) throw new \Exception('Entry for date already exists', 409);

		return $this->entries->create($kpiId, $date, (float)$value);
	}

	public function bulkInsert(array $currentUser, int $kpiId, array $rows): array
	{
		$this->ensureOwnership($currentUser, $kpiId);

		$normalized = [];
		foreach ($rows as $row) {
			$errors = $this->entries->validateEntry($row);
			if (!empty($errors)) {
				return ['success' => false, 'error' => implode('; ', $errors), 'code' => 400];
			}
			// Removed: Allow multiple entries for the same date
			// if ($this->entries->hasEntryForDate($kpiId, $row['date'])) {
			// 	return ['success' => false, 'error' => "Entry exists for {$row['date']}", 'code' => 409];
			// }
			$normalized[] = ['date' => $row['date'], 'value' => (float)$row['value']];
		}
		return $this->entries->bulkInsert($kpiId, $normalized);
	}

	public function query(array $currentUser, int $kpiId, ?string $startDate = null, ?string $endDate = null): array
	{
		$this->ensureOwnership($currentUser, $kpiId);
		return $this->entries->findByKpiId($kpiId, $startDate, $endDate);
	}

	public function aggregate(array $currentUser, int $kpiId, string $type, ?string $startDate = null, ?string $endDate = null): ?array
	{
		$this->ensureOwnership($currentUser, $kpiId);
		return $this->entries->getAggregateValue($kpiId, $type, $startDate, $endDate);
	}

	public function update(array $currentUser, int $entryId, array $data): bool
	{
		// First, get the entry to find the KPI ID for ownership check
		$entry = $this->entries->findById($entryId);
		if (!$entry) {
			throw new \Exception('KPI entry not found', 404);
		}
		
		// Check ownership through the KPI
		$this->ensureOwnership($currentUser, (int)$entry['kpi_id']);
		
		// Validate the data
		if (isset($data['date']) && !$this->entries->validateDate($data['date'])) {
			throw new \Exception('Invalid date (YYYY-MM-DD)', 400);
		}
		if (isset($data['value']) && !$this->entries->validateValue($data['value'])) {
			throw new \Exception('Invalid value', 400);
		}
		
		// Removed: Allow multiple entries for the same date
		// Check if the new date conflicts with another entry (if date is being changed)
		// if (isset($data['date']) && $data['date'] !== $entry['date']) {
		// 	if ($this->entries->hasEntryForDate((int)$entry['kpi_id'], $data['date'])) {
		// 		throw new \Exception('Entry for date already exists', 409);
		// 	}
		// }
		
		return $this->entries->update($entryId, $data);
	}

	public function delete(array $currentUser, int $entryId): bool
	{
		// First, get the entry to find the KPI ID for ownership check
		$entry = $this->entries->findById($entryId);
		if (!$entry) {
			throw new \Exception('KPI entry not found', 404);
		}
		
		// Check ownership through the KPI
		$this->ensureOwnership($currentUser, (int)$entry['kpi_id']);
		
		return $this->entries->delete($entryId);
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
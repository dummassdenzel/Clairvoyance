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
		if ($currentUser['role'] !== 'admin' && (int)$kpi['user_id'] !== (int)$currentUser['id']) {
			throw new \Exception('Access denied', 403);
		}
	}

	public function add(array $currentUser, int $kpiId, string $date, $value): array
	{
		$this->ensureOwnership($currentUser, $kpiId);

		if (!$this->entries->validateDate($date)) throw new \Exception('Invalid date (YYYY-MM-DD)', 400);
		if (!$this->entries->validateValue($value)) throw new \Exception('Invalid value', 400);
		if ($this->entries->hasEntryForDate($kpiId, $date)) throw new \Exception('Entry for date already exists', 409);

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
			if ($this->entries->hasEntryForDate($kpiId, $row['date'])) {
				return ['success' => false, 'error' => "Entry exists for {$row['date']}", 'code' => 409];
			}
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

	public function delete(array $currentUser, int $entryId): bool
	{
		// Optionally: lookup entry -> kpi_id -> ensureOwnership()
		return $this->entries->delete($entryId);
	}
}
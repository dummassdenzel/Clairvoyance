<?php

namespace Services;

use Models\Dashboard;
use Models\ShareToken;
use Models\User;
use PDO;

class DashboardService
{
	private Dashboard $dashboards;
	private ShareToken $tokens;
	private User $users;
	private PDO $db;

	public function __construct(Dashboard $dashboards, ShareToken $tokens, User $users, PDO $db)
	{
		$this->dashboards = $dashboards;
		$this->tokens = $tokens;
		$this->users = $users;
		$this->db = $db;
	}

	public function create(array $currentUser, array $data): array
	{
		if (!in_array($currentUser['role'], ['admin','editor'])) {
			throw new \Exception('Insufficient permissions', 403);
		}

		if (empty($data['name']) || empty($data['layout'])) {
			throw new \Exception('Missing required fields: name, layout', 400);
		}
		$layout = is_array($data['layout']) ? json_encode($data['layout']) : $data['layout'];
		if (!$this->dashboards->validateLayout($layout)) {
			throw new \Exception('Invalid layout JSON', 400);
		}
		if (!$this->dashboards->validateName($data['name'])) {
			throw new \Exception('Invalid dashboard name', 400);
		}

		return $this->dashboards->create(
			$data['name'],
			$data['description'] ?? '',
			$layout,
			(int)$currentUser['id']
		);
	}

	public function get(array $currentUser, int $dashboardId): array
	{
		$dashboard = $this->dashboards->findById($dashboardId);
		if (!$dashboard) {
			throw new \Exception('Dashboard not found', 404);
		}
		if ($currentUser['role'] !== 'admin'
			&& (int)$dashboard['user_id'] !== (int)$currentUser['id']
			&& !$this->dashboards->hasViewerAccess($dashboardId, (int)$currentUser['id'])) {
			throw new \Exception('Access denied', 403);
		}
		$dashboard['viewers'] = $this->dashboards->getViewers($dashboardId);
		return $dashboard;
	}

	public function list(array $currentUser): array
	{
		if ($currentUser['role'] === 'admin') {
			return $this->dashboards->findAll();
		}
		$owned = $this->dashboards->findByUserId((int)$currentUser['id']);
		$shared = $this->dashboards->getDashboardsByViewer((int)$currentUser['id']);
		return array_values(array_merge($owned, $shared));
	}

	public function update(array $currentUser, int $dashboardId, array $data): bool
	{
		$dashboard = $this->dashboards->findById($dashboardId);
		if (!$dashboard) {
			throw new \Exception('Dashboard not found', 404);
		}
		if ($currentUser['role'] !== 'admin' && (int)$dashboard['user_id'] !== (int)$currentUser['id']) {
			throw new \Exception('Access denied', 403);
		}
		if (isset($data['layout'])) {
			$layout = is_array($data['layout']) ? json_encode($data['layout']) : $data['layout'];
			if (!$this->dashboards->validateLayout($layout)) {
				throw new \Exception('Invalid layout JSON', 400);
			}
			$data['layout'] = $layout;
		}
		if (isset($data['name']) && !$this->dashboards->validateName($data['name'])) {
			throw new \Exception('Invalid dashboard name', 400);
		}
		return $this->dashboards->update($dashboardId, $data);
	}

	public function delete(array $currentUser, int $dashboardId): bool
	{
		$dashboard = $this->dashboards->findById($dashboardId);
		if (!$dashboard) {
			throw new \Exception('Dashboard not found', 404);
		}
		if ($currentUser['role'] !== 'admin' && (int)$dashboard['user_id'] !== (int)$currentUser['id']) {
			throw new \Exception('Access denied', 403);
		}
		return $this->dashboards->delete($dashboardId);
	}

	public function addViewer(array $currentUser, int $dashboardId, int $viewerUserId): bool
	{
		$dashboard = $this->dashboards->findById($dashboardId);
		if (!$dashboard) throw new \Exception('Dashboard not found', 404);
		if ($currentUser['role'] !== 'admin' && (int)$dashboard['user_id'] !== (int)$currentUser['id']) {
			throw new \Exception('Access denied', 403);
		}
		return $this->dashboards->addViewer($dashboardId, $viewerUserId);
	}

	public function removeViewer(array $currentUser, int $dashboardId, int $viewerUserId): bool
	{
		$dashboard = $this->dashboards->findById($dashboardId);
		if (!$dashboard) throw new \Exception('Dashboard not found', 404);
		if ($currentUser['role'] !== 'admin' && (int)$dashboard['user_id'] !== (int)$currentUser['id']) {
			throw new \Exception('Access denied', 403);
		}
		return $this->dashboards->removeViewer($dashboardId, $viewerUserId);
	}
}
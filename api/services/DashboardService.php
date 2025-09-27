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
			&& !$this->dashboards->hasUserAccess($dashboardId, (int)$currentUser['id'])) {
			throw new \Exception('Access denied', 403);
		}
		
		// Determine user's permission level for this dashboard
		$permissionLevel = 'viewer'; // Default
		if ($currentUser['role'] === 'admin') {
			$permissionLevel = 'admin';
		} elseif ((int)$dashboard['user_id'] === (int)$currentUser['id']) {
			$permissionLevel = 'owner';
		} else {
			// Check dashboard_access table for specific permission level
			$userPermission = $this->dashboards->getUserPermissionLevel($dashboardId, (int)$currentUser['id']);
			if ($userPermission) {
				$permissionLevel = $userPermission;
			}
		}
		
		$dashboard['viewers'] = $this->dashboards->getDashboardUsers($dashboardId);
		$dashboard['user_permission_level'] = $permissionLevel;
		return $dashboard;
	}

	public function list(array $currentUser): array
	{
		// Get all dashboards the user has access to (owned or shared)
		// This avoids duplicates by using a single query
		// Admins still need explicit access to dashboards for security
		return $this->dashboards->getDashboardsByUser((int)$currentUser['id']);
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

	// New permission-based methods
	public function addUserAccess(array $currentUser, int $dashboardId, int $userId, string $permissionLevel = 'viewer'): bool
	{
		$dashboard = $this->dashboards->findById($dashboardId);
		if (!$dashboard) throw new \Exception('Dashboard not found', 404);
		
		// Check if user has permission to manage dashboard access
		if ($currentUser['role'] !== 'admin' && (int)$dashboard['user_id'] !== (int)$currentUser['id']) {
			throw new \Exception('Access denied', 403);
		}
		
		// Validate permission level
		if (!in_array($permissionLevel, ['owner', 'editor', 'viewer'])) {
			throw new \Exception('Invalid permission level', 400);
		}
		
		return $this->dashboards->addUserAccess($dashboardId, $userId, $permissionLevel);
	}

	public function removeUserAccess(array $currentUser, int $dashboardId, int $userId): bool
	{
		$dashboard = $this->dashboards->findById($dashboardId);
		if (!$dashboard) throw new \Exception('Dashboard not found', 404);
		
		// Check if user has permission to manage dashboard access
		if ($currentUser['role'] !== 'admin' && (int)$dashboard['user_id'] !== (int)$currentUser['id']) {
			throw new \Exception('Access denied', 403);
		}
		
		return $this->dashboards->removeUserAccess($dashboardId, $userId);
	}

	public function getDashboardUsers(array $currentUser, int $dashboardId): array
	{
		$dashboard = $this->dashboards->findById($dashboardId);
		if (!$dashboard) throw new \Exception('Dashboard not found', 404);
		
		// Check if user has any access to this dashboard
		if ($currentUser['role'] !== 'admin') {
			$hasAccess = $this->dashboards->hasUserAccess($dashboardId, (int)$currentUser['id']);
			if (!$hasAccess) {
				throw new \Exception('Access denied', 403);
			}
		}
		
		return $this->dashboards->getDashboardUsers($dashboardId);
	}
}
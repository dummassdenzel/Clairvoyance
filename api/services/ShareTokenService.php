<?php

namespace Services;

use Models\ShareToken;
use Models\Dashboard;

class ShareTokenService
{
	private ShareToken $tokens;
	private Dashboard $dashboards;

	public function __construct(ShareToken $tokens, Dashboard $dashboards)
	{
		$this->tokens = $tokens;
		$this->dashboards = $dashboards;
	}

	public function generate(array $currentUser, int $dashboardId, int $days = 7): array
	{
		$dashboard = $this->dashboards->findById($dashboardId);
		if (!$dashboard) throw new \Exception('Dashboard not found', 404);
		if ($currentUser['role'] !== 'admin' && (int)$dashboard['user_id'] !== (int)$currentUser['id']) {
			throw new \Exception('Access denied', 403);
		}
		$token = $this->tokens->generateToken();
		$expires = $this->tokens->generateExpirationDate($days);
		return $this->tokens->create($dashboardId, $token, $expires);
	}

	public function redeem(array $currentUser, string $token): int
	{
		if (!isset($currentUser['id'])) throw new \Exception('Authentication required', 401);
		$data = $this->tokens->findByToken($token);
		if (!$data || $this->tokens->isExpired($token)) {
			throw new \Exception('Invalid or expired token', 400);
		}
		$this->dashboards->addViewer((int)$data['dashboard_id'], (int)$currentUser['id']);
		$this->tokens->delete((int)$data['id']);
		return (int)$data['dashboard_id'];
	}
}
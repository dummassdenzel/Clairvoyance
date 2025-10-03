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
		
		// Only dashboard owners can generate share links
		if ((int)$dashboard['user_id'] !== (int)$currentUser['id']) {
			throw new \Exception('Only dashboard owners can generate share links', 403);
		}
		
		$token = $this->tokens->generateToken();
		$expires = $this->tokens->generateExpirationDate($days);
		
		$result = $this->tokens->create($dashboardId, $token, $expires);
		
		if ($result['success']) {
			// Return the token data instead of just the create result
			return [
				'success' => true,
				'token' => [
					'id' => $result['id'],
					'dashboard_id' => $dashboardId,
					'token' => $token,
					'expires_at' => $expires,
					'created_at' => date('Y-m-d H:i:s')
				]
			];
		} else {
			throw new \Exception($result['error'] ?? 'Failed to create share token', 500);
		}
	}

	public function redeem(array $currentUser, string $token): int
	{
		if (!isset($currentUser['id'])) throw new \Exception('Authentication required', 401);
		$data = $this->tokens->findByToken($token);
		if (!$data || $this->tokens->isExpired($token)) {
			throw new \Exception('Invalid or expired token', 400);
		}
		// Add user access with viewer permission (default for shared links)
		$this->dashboards->addUserAccess((int)$data['dashboard_id'], (int)$currentUser['id'], 'viewer');
		$this->tokens->delete((int)$data['id']);
		return (int)$data['dashboard_id'];
	}
}
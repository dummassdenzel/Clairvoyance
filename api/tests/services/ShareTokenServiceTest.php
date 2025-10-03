<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../TestCase.php';

class ShareTokenServiceTest extends TestCase
{
	public function testGenerateAndRedeem()
	{
		echo "🔗 ShareTokenService: generate/redeem...\n";
		$dashSvc = $this->container->resolve(\Services\DashboardService::class);
		$tokenSvc = $this->container->resolve(\Services\ShareTokenService::class);

		$ownerId = $this->createTestUser('svc_token_owner@example.com', 'editor');
		$viewerId = $this->createTestUser('svc_token_viewer@example.com', 'viewer');
		$owner = ['id' => $ownerId, 'role' => 'editor'];
		$viewer = ['id' => $viewerId, 'role' => 'viewer'];

		$dash = $dashSvc->create($owner, ['name'=>'TokenDash','description'=>'','layout'=>[['id'=>1]]]);
		$dashId = (int)$dash['id'];

		$tres = $tokenSvc->generate($owner, $dashId, 7);
		if (empty($tres['success'])) throw new \Exception('Generate failed');

		// ShareToken model returns ['success'=>true,'id'=>..], we must fetch token by dashboard if needed.
		$tokensModel = $this->container->resolve(\Models\ShareToken::class);
		$tokens = $tokensModel->findByDashboardId($dashId);
		if (count($tokens) < 1) throw new \Exception('No token found');
		$token = $tokens[0]['token'];

		$redeemedDashId = $tokenSvc->redeem($viewer, $token);
		if ($redeemedDashId !== $dashId) throw new \Exception('Redeem mismatch');

		// Viewer can now access
		$got = $dashSvc->get($viewer, $dashId);
		if ((int)$got['id'] !== $dashId) throw new \Exception('Viewer cannot access after redeem');
		echo "✅ ok\n\n";
	}

	public function runAllTests()
	{
		try {
			$this->testGenerateAndRedeem();
		} finally {
			$this->cleanupTestData();
		}
	}
}

$test = new ShareTokenServiceTest();
$test->runAllTests();
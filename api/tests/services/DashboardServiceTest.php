<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../TestCase.php';

class DashboardServiceTest extends TestCase
{
	public function testCreateAndGetAndList()
	{
		echo "📊 DashboardService: create/get/list...\n";
		$svc = $this->container->resolve(\Services\DashboardService::class);

		$editorId = $this->createTestUser('svc_dash_editor@example.com', 'editor');
		$editor = ['id' => $editorId, 'role' => 'editor'];

		$res = $svc->create($editor, [
			'name' => 'Svc Dashboard',
			'description' => 'From service',
			'layout' => [['id'=>1,'type'=>'widget','title'=>'W1']]
		]);
		if (empty($res['success'])) throw new \Exception('Create failed');

		$dashboard = $svc->get($editor, (int)$res['id']);
		if ((int)$dashboard['user_id'] !== (int)$editorId) throw new \Exception('Owner mismatch');

		$list = $svc->list($editor);
		if (count($list) < 1) throw new \Exception('List should contain at least 1');

		echo "✅ ok\n\n";
	}

	public function testViewerAccessAndRemove()
	{
		echo "👥 DashboardService: add/remove viewer...\n";
		$svc = $this->container->resolve(\Services\DashboardService::class);

		$ownerId = $this->createTestUser('svc_dash_owner@example.com', 'editor');
		$viewerId = $this->createTestUser('svc_dash_viewer@example.com', 'viewer');
		$owner = ['id' => $ownerId, 'role' => 'editor'];
		$viewer = ['id' => $viewerId, 'role' => 'viewer'];

		$res = $svc->create($owner, ['name'=>'Shared','description'=>'','layout'=>[['id'=>1]]]);
		$dashId = (int)$res['id'];

		$svc->addViewer($owner, $dashId, $viewerId);
		$dash = $svc->get($viewer, $dashId); // should not throw
		if ((int)$dash['id'] !== $dashId) throw new \Exception('Viewer cannot get dashboard');

		$svc->removeViewer($owner, $dashId, $viewerId);
		try {
			$svc->get($viewer, $dashId);
			throw new \Exception('Viewer should have lost access');
		} catch (\Exception $e) {
			if ($e->getCode() !== 403) throw $e;
		}
		echo "✅ ok\n\n";
	}

	public function runAllTests()
	{
		try {
			$this->testCreateAndGetAndList();
			$this->testViewerAccessAndRemove();
		} finally {
			$this->cleanupTestData();
		}
	}
}

$test = new DashboardServiceTest();
$test->runAllTests();
<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../TestCase.php';

class KpiServiceTest extends TestCase
{
	public function testCreateGetUpdateDelete()
	{
		echo "📈 KpiService: CRUD...\n";
		$svc = $this->container->resolve(\Services\KpiService::class);

		$userId = $this->createTestUser('svc_kpi_owner@example.com', 'editor');
		$user = ['id' => $userId, 'role' => 'editor'];

		$data = [
			'name'=>'Svc KPI','direction'=>'higher_is_better','target'=>100,'rag_red'=>50,'rag_amber'=>75,'format_prefix'=>'$','format_suffix'=>''
		];
		$create = $svc->create($user, $data);
		if (empty($create['success'])) throw new \Exception('Create failed');
		$kpiId = (int)$create['id'];

		$get = $svc->get($user, $kpiId);
		if ((int)$get['user_id'] !== (int)$userId) throw new \Exception('Owner mismatch');

		$ok = $svc->update($user, $kpiId, ['target'=>200,'format_prefix'=>'€']);
		if (!$ok) throw new \Exception('Update failed');

		$get2 = $svc->get($user, $kpiId);
		if ((float)$get2['target'] != 200.0 || $get2['format_prefix'] !== '€') throw new \Exception('Update not applied');

		$del = $svc->delete($user, $kpiId);
		if (!$del) throw new \Exception('Delete failed');

		echo "✅ ok\n\n";
	}

	public function runAllTests()
	{
		try {
			$this->testCreateGetUpdateDelete();
		} finally {
			$this->cleanupTestData();
		}
	}
}

$test = new KpiServiceTest();
$test->runAllTests();
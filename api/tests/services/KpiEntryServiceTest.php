<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../TestCase.php';

class KpiEntryServiceTest extends TestCase
{
	private function createOwnerAndKpi(): array
	{
		$email = 'svc_entry_owner_'.uniqid().'@example.com';
		$userId = $this->createTestUser($email, 'editor');
		$user = ['id'=>$userId,'role'=>'editor'];

		$kpiModel = $this->container->resolve(\Models\Kpi::class);
		$kpiRes = $kpiModel->create([
			'name'=>'Svc Entry KPI','direction'=>'higher_is_better','target'=>100,'rag_red'=>50,'rag_amber'=>75,'format_prefix'=>'','format_suffix'=>''
		], $userId);
		return [$user, (int)$kpiRes['id']];
	}

	public function testAddAndAggregateAndQuery()
	{
		echo "🧮 KpiEntryService: add/aggregate/query...\n";
		$svc = $this->container->resolve(\Services\KpiEntryService::class);
		[$user,$kpiId] = $this->createOwnerAndKpi();

		$svc->add($user, $kpiId, '2024-01-01', 100);
		$svc->add($user, $kpiId, '2024-01-02', 150);
		$svc->add($user, $kpiId, '2024-01-03', 200);

		$sum = $svc->aggregate($user, $kpiId, 'sum');
		if ((float)$sum['value'] != 450.0) throw new \Exception('Sum incorrect');

		$avg = $svc->aggregate($user, $kpiId, 'average');
		if (round((float)$avg['value'],2) != 150.00) throw new \Exception('Avg incorrect');

		$q = $svc->query($user, $kpiId, '2024-01-02', '2024-01-03');
		if (count($q) != 2) throw new \Exception('Query range incorrect');

		echo "✅ ok\n\n";
	}

	public function testBulkInsertGuards()
	{
		echo "📦 KpiEntryService: bulk guards...\n";
		$svc = $this->container->resolve(\Services\KpiEntryService::class);
		[$user,$kpiId] = $this->createOwnerAndKpi();

		$bad = $svc->bulkInsert($user, $kpiId, [['date'=>'bad','value'=>-1]]);
		if (!empty($bad['success'])) throw new \Exception('Expected failure on invalid rows');

		$ok = $svc->bulkInsert($user, $kpiId, [
			['date'=>'2024-02-01','value'=>100],
			['date'=>'2024-02-02','value'=>150]
		]);
		if (empty($ok['inserted']) || $ok['inserted'] < 2) throw new \Exception('Bulk insert failed');

		echo "✅ ok\n\n";
	}

	public function runAllTests()
	{
		try {
			$this->cleanupTestData(); // ensure clean start
			$this->testAddAndAggregateAndQuery();
			$this->testBulkInsertGuards();
		} finally {
			$this->cleanupTestData();
		}
	}
}

$test = new KpiEntryServiceTest();
$test->runAllTests();
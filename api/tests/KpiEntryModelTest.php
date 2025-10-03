<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

/**
 * KPI Entry Model Test
 * 
 * Tests KPI Entry model functionality.
 */

class KpiEntryModelTest extends TestCase
{
    public function testKpiEntryCreation()
    {
        echo "📈 Testing KPI Entry Creation...\n";
        
        $kpiEntryModel = $this->container->resolve(\Models\KpiEntry::class);
        $userId = $this->createTestUser('test_kpi_entry@example.com');
        $kpiId = $this->createTestKpi($userId, 'Test KPI');
        
        $result = $kpiEntryModel->create($kpiId, '2024-01-15', 85.5);
        $this->assertTrue($result['success'], "KPI entry creation should succeed");
        $this->assertArrayHasKey('id', $result, "Result should contain entry ID");
        
        $entryId = $result['id'];
        $entry = $kpiEntryModel->findById($entryId);
        $this->assertNotNull($entry, "Created entry should be findable");
        $this->assertEquals($kpiId, $entry['kpi_id'], "KPI ID should match");
        $this->assertEquals('2024-01-15', $entry['date'], "Date should match");
        $this->assertEquals(85.5, $entry['value'], "Value should match");
        
        echo "✅ KPI entry creation test passed\n\n";
    }
    
    public function testKpiEntryValidation()
    {
        echo "✅ Testing KPI Entry Validation...\n";
        
        $kpiEntryModel = $this->container->resolve(\Models\KpiEntry::class);
        
        // Test date validation
        $this->assertTrue($kpiEntryModel->validateDate('2024-01-15'), "Valid date should pass validation");
        $this->assertTrue($kpiEntryModel->validateDate('2024-12-31'), "Valid date should pass validation");
        $this->assertFalse($kpiEntryModel->validateDate('2024-13-01'), "Invalid date should fail validation");
        $this->assertFalse($kpiEntryModel->validateDate('invalid-date'), "Invalid date format should fail validation");
        
        // Test value validation
        $this->assertTrue($kpiEntryModel->validateValue(100.0), "Valid value should pass validation");
        $this->assertTrue($kpiEntryModel->validateValue(0), "Zero value should pass validation");
        $this->assertFalse($kpiEntryModel->validateValue(-10), "Negative value should fail validation");
        $this->assertFalse($kpiEntryModel->validateValue('invalid'), "Non-numeric value should fail validation");
        
        // Test entry validation
        $validEntry = ['date' => '2024-01-15', 'value' => 85.5];
        $errors = $kpiEntryModel->validateEntry($validEntry);
        $this->assertTrue(empty($errors), "Valid entry should have no errors");
        
        $invalidEntry = ['date' => 'invalid-date', 'value' => -10];
        $errors = $kpiEntryModel->validateEntry($invalidEntry);
        $this->assertTrue(count($errors) > 0, "Invalid entry should have errors");
        
        echo "✅ KPI entry validation test passed\n\n";
    }
    
    public function testDataAggregation()
    {
        echo "📊 Testing Data Aggregation...\n";
        
        $kpiEntryModel = $this->container->resolve(\Models\KpiEntry::class);
        $userId = $this->createTestUser('test_aggregation@example.com');
        $kpiId = $this->createTestKpi($userId, 'Aggregation Test KPI');
        
        // Create test entries
        $entries = [
            ['date' => '2024-01-01', 'value' => 100.0],
            ['date' => '2024-01-02', 'value' => 150.0],
            ['date' => '2024-01-03', 'value' => 200.0],
            ['date' => '2024-01-04', 'value' => 50.0]
        ];
        
        foreach ($entries as $entry) {
            $kpiEntryModel->create($kpiId, $entry['date'], $entry['value']);
        }
        
        // Test sum aggregation
        $sumResult = $kpiEntryModel->getAggregateValue($kpiId, 'sum');
        $this->assertNotNull($sumResult, "Sum aggregation should return result");
        $this->assertEquals(500.0, $sumResult['value'], "Sum should be 500.0");
        
        // Test average aggregation
        $avgResult = $kpiEntryModel->getAggregateValue($kpiId, 'average');
        $this->assertNotNull($avgResult, "Average aggregation should return result");
        $this->assertEquals(125.0, $avgResult['value'], "Average should be 125.0");
        
        // Test latest aggregation
        $latestResult = $kpiEntryModel->getAggregateValue($kpiId, 'latest');
        $this->assertNotNull($latestResult, "Latest aggregation should return result");
        $this->assertEquals(50.0, $latestResult['value'], "Latest value should be 50.0");
        
        // Test min aggregation
        $minResult = $kpiEntryModel->getAggregateValue($kpiId, 'min');
        $this->assertNotNull($minResult, "Min aggregation should return result");
        $this->assertEquals(50.0, $minResult['value'], "Min value should be 50.0");
        
        // Test max aggregation
        $maxResult = $kpiEntryModel->getAggregateValue($kpiId, 'max');
        $this->assertNotNull($maxResult, "Max aggregation should return result");
        $this->assertEquals(200.0, $maxResult['value'], "Max value should be 200.0");
        
        // Test count aggregation
        $countResult = $kpiEntryModel->getAggregateValue($kpiId, 'count');
        $this->assertNotNull($countResult, "Count aggregation should return result");
        $this->assertEquals(4, $countResult['value'], "Count should be 4");
        
        echo "✅ Data aggregation test passed\n\n";
    }
    
    public function testDateRangeQueries()
    {
        echo "📅 Testing Date Range Queries...\n";
        
        $kpiEntryModel = $this->container->resolve(\Models\KpiEntry::class);
        $userId = $this->createTestUser('test_date_range@example.com');
        $kpiId = $this->createTestKpi($userId, 'Date Range Test KPI');
        
        // Create entries across different dates
        $entries = [
            ['date' => '2024-01-01', 'value' => 100.0],
            ['date' => '2024-01-15', 'value' => 150.0],
            ['date' => '2024-02-01', 'value' => 200.0],
            ['date' => '2024-02-15', 'value' => 250.0],
            ['date' => '2024-03-01', 'value' => 300.0]
        ];
        
        foreach ($entries as $entry) {
            $kpiEntryModel->create($kpiId, $entry['date'], $entry['value']);
        }
        
        // Test date range query
        $rangeResults = $kpiEntryModel->findByDateRange($kpiId, '2024-01-01', '2024-01-31');
        $this->assertTrue(count($rangeResults) >= 2, "Should find entries in January range");
        
        // Test with start date only
        $startResults = $kpiEntryModel->findByKpiId($kpiId, '2024-02-01');
        $this->assertTrue(count($startResults) >= 3, "Should find entries from February onwards");
        
        // Test with end date only
        $endResults = $kpiEntryModel->findByKpiId($kpiId, null, '2024-01-31');
        $this->assertTrue(count($endResults) >= 2, "Should find entries up to January");
        
        echo "✅ Date range queries test passed\n\n";
    }
    
    public function testBulkOperations()
    {
        echo "📦 Testing Bulk Operations...\n";
        
        $kpiEntryModel = $this->container->resolve(\Models\KpiEntry::class);
        $userId = $this->createTestUser('test_bulk@example.com');
        $kpiId = $this->createTestKpi($userId, 'Bulk Test KPI');
        
        // Test bulk insert
        $bulkEntries = [
            ['date' => '2024-01-01', 'value' => 100.0],
            ['date' => '2024-01-02', 'value' => 150.0],
            ['date' => '2024-01-03', 'value' => 200.0]
        ];
        
        $bulkResult = $kpiEntryModel->bulkInsert($kpiId, $bulkEntries);
        $this->assertArrayHasKey('inserted', $bulkResult, "Bulk result should contain inserted count");
        $this->assertArrayHasKey('failed', $bulkResult, "Bulk result should contain failed count");
        $this->assertEquals(3, $bulkResult['inserted'], "Should insert 3 entries");
        $this->assertEquals(0, $bulkResult['failed'], "Should have no failures");
        
        // Verify entries were created
        $allEntries = $kpiEntryModel->findByKpiId($kpiId);
        $this->assertTrue(count($allEntries) >= 3, "Should have at least 3 entries");
        
        echo "✅ Bulk operations test passed\n\n";
    }
    
    public function testUtilityMethods()
    {
        echo "🔧 Testing Utility Methods...\n";
        
        $kpiEntryModel = $this->container->resolve(\Models\KpiEntry::class);
        $userId = $this->createTestUser('test_utility@example.com');
        $kpiId = $this->createTestKpi($userId, 'Utility Test KPI');
        
        // Create test entries
        $kpiEntryModel->create($kpiId, '2024-01-01', 100.0);
        $kpiEntryModel->create($kpiId, '2024-01-15', 150.0);
        $kpiEntryModel->create($kpiId, '2024-02-01', 200.0);
        
        // Test latest value
        $latestValue = $kpiEntryModel->getLatestValue($kpiId);
        $this->assertEquals(200.0, $latestValue, "Latest value should be 200.0");
        
        // Test earliest value
        $earliestValue = $kpiEntryModel->getEarliestValue($kpiId);
        $this->assertEquals(100.0, $earliestValue, "Earliest value should be 100.0");
        
        // Test value by date
        $valueByDate = $kpiEntryModel->getValueByDate($kpiId, '2024-01-15');
        $this->assertEquals(150.0, $valueByDate, "Value for 2024-01-15 should be 150.0");
        
        // Test has entry for date
        $hasEntry = $kpiEntryModel->hasEntryForDate($kpiId, '2024-01-15');
        $this->assertTrue($hasEntry, "Should have entry for 2024-01-15");
        
        $noEntry = $kpiEntryModel->hasEntryForDate($kpiId, '2024-01-10');
        $this->assertFalse($noEntry, "Should not have entry for 2024-01-10");
        
        echo "✅ Utility methods test passed\n\n";
    }
    
    public function testStatistics()
    {
        echo "📈 Testing Statistics...\n";
        
        $kpiEntryModel = $this->container->resolve(\Models\KpiEntry::class);
        $userId = $this->createTestUser('test_stats@example.com');
        $kpiId = $this->createTestKpi($userId, 'Stats Test KPI');
        
        // Create test entries
        $entries = [
            ['date' => '2024-01-01', 'value' => 100.0],
            ['date' => '2024-01-02', 'value' => 150.0],
            ['date' => '2024-01-03', 'value' => 200.0]
        ];
        
        foreach ($entries as $entry) {
            $kpiEntryModel->create($kpiId, $entry['date'], $entry['value']);
        }
        
        // Test statistics
        $stats = $kpiEntryModel->getStats($kpiId);
        $this->assertArrayHasKey('total_entries', $stats, "Stats should contain total_entries");
        $this->assertArrayHasKey('min_value', $stats, "Stats should contain min_value");
        $this->assertArrayHasKey('max_value', $stats, "Stats should contain max_value");
        $this->assertArrayHasKey('avg_value', $stats, "Stats should contain avg_value");
        $this->assertEquals(3, $stats['total_entries'], "Should have 3 total entries");
        $this->assertEquals(100.0, $stats['min_value'], "Min value should be 100.0");
        $this->assertEquals(200.0, $stats['max_value'], "Max value should be 200.0");
        
        // Test date range
        $dateRange = $kpiEntryModel->getDateRange($kpiId);
        $this->assertNotNull($dateRange, "Date range should not be null");
        $this->assertEquals('2024-01-01', $dateRange['start_date'], "Start date should be 2024-01-01");
        $this->assertEquals('2024-01-03', $dateRange['end_date'], "End date should be 2024-01-03");
        
        echo "✅ Statistics test passed\n\n";
    }
    
    public function testMissingDates()
    {
        echo "📅 Testing Missing Dates...\n";
        
        $kpiEntryModel = $this->container->resolve(\Models\KpiEntry::class);
        $userId = $this->createTestUser('test_missing@example.com');
        $kpiId = $this->createTestKpi($userId, 'Missing Dates Test KPI');
        
        // Create entries with gaps
        $kpiEntryModel->create($kpiId, '2024-01-01', 100.0);
        $kpiEntryModel->create($kpiId, '2024-01-03', 150.0); // Skip 2024-01-02
        $kpiEntryModel->create($kpiId, '2024-01-05', 200.0); // Skip 2024-01-04
        
        // Test missing dates
        $missingDates = $kpiEntryModel->getMissingDates($kpiId, '2024-01-01', '2024-01-05');
        $this->assertTrue(in_array('2024-01-02', $missingDates), "Should identify 2024-01-02 as missing");
        $this->assertTrue(in_array('2024-01-04', $missingDates), "Should identify 2024-01-04 as missing");
        $this->assertFalse(in_array('2024-01-01', $missingDates), "Should not identify 2024-01-01 as missing");
        
        echo "✅ Missing dates test passed\n\n";
    }
    
    public function runAllTests()
    {
        echo "🚀 Starting KPI Entry Model Tests\n";
        echo "==================================\n\n";
        
        try {
            $this->testKpiEntryCreation();
            $this->testKpiEntryValidation();
            $this->testDataAggregation();
            $this->testDateRangeQueries();
            $this->testBulkOperations();
            $this->testUtilityMethods();
            $this->testStatistics();
            $this->testMissingDates();
            
            echo "🎉 All KPI Entry Model Tests Passed!\n";
            echo "==================================\n";
            
        } catch (\Exception $e) {
            echo "❌ Test Failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            exit(1);
        } finally {
            $this->cleanupTestData();
        }
    }
}

// Run the tests
$test = new KpiEntryModelTest();
$test->runAllTests();

<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

/**
 * KPI Model Test
 * 
 * Tests KPI model functionality.
 */

class KpiModelTest extends TestCase
{
    public function testKpiCreation()
    {
        echo "📊 Testing KPI Creation...\n";
        
        $kpiModel = $this->container->resolve(\Models\Kpi::class);
        $userId = $this->createTestUser('test_kpi@example.com');
        
        $data = [
            'name' => 'Test KPI',
            'direction' => 'higher_is_better',
            'target' => 100.0,
            'rag_red' => 50.0,
            'rag_amber' => 75.0,
            'format_prefix' => '$',
            'format_suffix' => ''
        ];
        
        $result = $kpiModel->create($data, $userId);
        $this->assertTrue($result['success'], "KPI creation should succeed");
        $this->assertArrayHasKey('id', $result, "Result should contain KPI ID");
        
        $kpiId = $result['id'];
        $kpi = $kpiModel->findById($kpiId);
        $this->assertNotNull($kpi, "Created KPI should be findable");
        $this->assertEquals('Test KPI', $kpi['name'], "Name should match");
        $this->assertEquals($userId, $kpi['user_id'], "User ID should match");
        $this->assertEquals('higher_is_better', $kpi['direction'], "Direction should match");
        
        echo "✅ KPI creation test passed\n\n";
    }
    
    public function testKpiValidation()
    {
        echo "✅ Testing KPI Validation...\n";
        
        $kpiModel = $this->container->resolve(\Models\Kpi::class);
        
        // Test name validation
        $this->assertTrue($kpiModel->validateName('Valid KPI Name'), "Valid name should pass validation");
        $this->assertFalse($kpiModel->validateName(''), "Empty name should fail validation");
        $this->assertFalse($kpiModel->validateName(str_repeat('a', 256)), "Too long name should fail validation");
        
        // Test direction validation
        $this->assertTrue($kpiModel->validateDirection('higher_is_better'), "Valid direction should pass validation");
        $this->assertTrue($kpiModel->validateDirection('lower_is_better'), "Valid direction should pass validation");
        $this->assertFalse($kpiModel->validateDirection('invalid_direction'), "Invalid direction should fail validation");
        
        // Test target validation
        $this->assertTrue($kpiModel->validateTarget(100.0), "Valid target should pass validation");
        $this->assertTrue($kpiModel->validateTarget(0), "Zero target should pass validation");
        $this->assertFalse($kpiModel->validateTarget(-10), "Negative target should fail validation");
        
        // Test RAG thresholds validation
        $this->assertTrue($kpiModel->validateRagThresholds(50.0, 75.0), "Valid RAG thresholds should pass validation");
        $this->assertFalse($kpiModel->validateRagThresholds(50.0, 50.0), "Same RAG thresholds should fail validation");
        $this->assertFalse($kpiModel->validateRagThresholds(-10, 75.0), "Negative RAG threshold should fail validation");
        
        // Test format validation
        $this->assertTrue($kpiModel->validateFormatPrefix('$'), "Valid prefix should pass validation");
        $this->assertTrue($kpiModel->validateFormatSuffix('%'), "Valid suffix should pass validation");
        $this->assertFalse($kpiModel->validateFormatPrefix(str_repeat('a', 11)), "Too long prefix should fail validation");
        
        echo "✅ KPI validation test passed\n\n";
    }
    
    public function testRagStatusCalculation()
    {
        echo "🎯 Testing RAG Status Calculation...\n";
        
        $kpiModel = $this->container->resolve(\Models\Kpi::class);
        
        // Test higher_is_better direction
        $status1 = $kpiModel->calculateRagStatus(80.0, 'higher_is_better', 50.0, 75.0);
        $this->assertEquals('green', $status1, "Value above amber should be green");
        
        $status2 = $kpiModel->calculateRagStatus(60.0, 'higher_is_better', 50.0, 75.0);
        $this->assertEquals('amber', $status2, "Value between red and amber should be amber");
        
        $status3 = $kpiModel->calculateRagStatus(40.0, 'higher_is_better', 50.0, 75.0);
        $this->assertEquals('red', $status3, "Value below red should be red");
        
        // Test lower_is_better direction
        $status4 = $kpiModel->calculateRagStatus(40.0, 'lower_is_better', 50.0, 75.0);
        $this->assertEquals('green', $status4, "Value below amber should be green");
        
        $status5 = $kpiModel->calculateRagStatus(60.0, 'lower_is_better', 50.0, 75.0);
        $this->assertEquals('amber', $status5, "Value between amber and red should be amber");
        
        $status6 = $kpiModel->calculateRagStatus(80.0, 'lower_is_better', 50.0, 75.0);
        $this->assertEquals('red', $status6, "Value above red should be red");
        
        echo "✅ RAG status calculation test passed\n\n";
    }
    
    public function testValueFormatting()
    {
        echo "💰 Testing Value Formatting...\n";
        
        $kpiModel = $this->container->resolve(\Models\Kpi::class);
        
        // Test formatting with prefix and suffix
        $formatted = $kpiModel->formatValue(123.45, '$', '');
        $this->assertEquals('$123.45', $formatted, "Should format with prefix");
        
        $formatted2 = $kpiModel->formatValue(85.5, '', '%');
        $this->assertEquals('85.50%', $formatted2, "Should format with suffix");
        
        $formatted3 = $kpiModel->formatValue(1000.0, '$', ' USD');
        $this->assertEquals('$1,000.00 USD', $formatted3, "Should format with both prefix and suffix");
        
        // Test formatting without prefix/suffix
        $formatted4 = $kpiModel->formatValue(50.0, '', '');
        $this->assertEquals('50.00', $formatted4, "Should format without prefix/suffix");
        
        echo "✅ Value formatting test passed\n\n";
    }
    
    public function testKpiUpdate()
    {
        echo "🔄 Testing KPI Update...\n";
        
        $kpiModel = $this->container->resolve(\Models\Kpi::class);
        $userId = $this->createTestUser('test_kpi_update@example.com');
        
        $kpiId = $this->createTestKpi($userId, 'Original KPI');
        
        // Test update
        $updateData = [
            'name' => 'Updated KPI',
            'target' => 200.0,
            'rag_red' => 100.0,
            'rag_amber' => 150.0,
            'format_prefix' => '€',
            'format_suffix' => ' EUR'
        ];
        
        $updateResult = $kpiModel->update($kpiId, $updateData);
        $this->assertTrue($updateResult, "KPI update should succeed");
        
        // Verify update
        $updatedKpi = $kpiModel->findById($kpiId);
        $this->assertEquals('Updated KPI', $updatedKpi['name'], "Name should be updated");
        $this->assertEquals(200.0, $updatedKpi['target'], "Target should be updated");
        $this->assertEquals('€', $updatedKpi['format_prefix'], "Prefix should be updated");
        
        echo "✅ KPI update test passed\n\n";
    }
    
    public function testKpiDeletion()
    {
        echo "🗑️  Testing KPI Deletion...\n";
        
        $kpiModel = $this->container->resolve(\Models\Kpi::class);
        $userId = $this->createTestUser('test_kpi_delete@example.com');
        
        $kpiId = $this->createTestKpi($userId, 'Delete Test KPI');
        
        // Verify KPI exists
        $kpi = $kpiModel->findById($kpiId);
        $this->assertNotNull($kpi, "KPI should exist before deletion");
        
        // Delete KPI
        $deleteResult = $kpiModel->delete($kpiId);
        $this->assertTrue($deleteResult, "KPI deletion should succeed");
        
        // Verify deletion
        $deletedKpi = $kpiModel->findById($kpiId);
        $this->assertTrue($deletedKpi === null, "KPI should not exist after deletion");
        
        echo "✅ KPI deletion test passed\n\n";
    }
    
    public function testKpiStatistics()
    {
        echo "📈 Testing KPI Statistics...\n";
        
        $kpiModel = $this->container->resolve(\Models\Kpi::class);
        $userId = $this->createTestUser('test_kpi_stats@example.com');
        
        // Create KPIs with different directions
        $this->createTestKpi($userId, 'Higher KPI');
        
        $data = [
            'name' => 'Lower KPI',
            'direction' => 'lower_is_better',
            'target' => 50.0,
            'rag_red' => 80.0,
            'rag_amber' => 60.0,
            'format_prefix' => '',
            'format_suffix' => '%'
        ];
        $kpiModel->create($data, $userId);
        
        // Test statistics
        $stats = $kpiModel->getStats($userId);
        $this->assertArrayHasKey('total_kpis', $stats, "Stats should contain total_kpis");
        $this->assertArrayHasKey('higher_is_better_count', $stats, "Stats should contain higher_is_better_count");
        $this->assertArrayHasKey('lower_is_better_count', $stats, "Stats should contain lower_is_better_count");
        $this->assertTrue($stats['total_kpis'] >= 2, "Should have at least 2 KPIs");
        $this->assertTrue($stats['higher_is_better_count'] >= 1, "Should have at least 1 higher_is_better KPI");
        $this->assertTrue($stats['lower_is_better_count'] >= 1, "Should have at least 1 lower_is_better KPI");
        
        echo "✅ KPI statistics test passed\n\n";
    }
    
    public function testKpiSearch()
    {
        echo "🔍 Testing KPI Search...\n";
        
        $kpiModel = $this->container->resolve(\Models\Kpi::class);
        $userId = $this->createTestUser('test_kpi_search@example.com');
        
        // Create KPIs with searchable names
        $this->createTestKpi($userId, 'Sales Performance');
        $this->createTestKpi($userId, 'Customer Satisfaction');
        $this->createTestKpi($userId, 'Sales Revenue');
        
        // Test search by name
        $results = $kpiModel->searchByName('Sales', $userId);
        $this->assertTrue(count($results) >= 2, "Should find at least 2 KPIs with 'Sales' in name");
        
        // Test search by direction
        $higherResults = $kpiModel->getKpisByDirection('higher_is_better', $userId);
        $this->assertTrue(count($higherResults) >= 3, "Should find all KPIs with higher_is_better direction");
        
        echo "✅ KPI search test passed\n\n";
    }
    
    public function runAllTests()
    {
        echo "🚀 Starting KPI Model Tests\n";
        echo "===========================\n\n";
        
        try {
            $this->testKpiCreation();
            $this->testKpiValidation();
            $this->testRagStatusCalculation();
            $this->testValueFormatting();
            $this->testKpiUpdate();
            $this->testKpiDeletion();
            $this->testKpiStatistics();
            $this->testKpiSearch();
            
            echo "🎉 All KPI Model Tests Passed!\n";
            echo "===========================\n";
            
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
$test = new KpiModelTest();
$test->runAllTests();

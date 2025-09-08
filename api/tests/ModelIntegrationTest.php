<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

/**
 * Model Integration Test
 * 
 * Tests that all models work together correctly with dependency injection.
 */

class ModelIntegrationTest extends TestCase
{
    public function testDependencyInjection()
    {
        echo "🔗 Testing Dependency Injection...\n";
        
        // Test that all models can be resolved
        $userModel = $this->container->resolve(\Models\User::class);
        $this->assertNotNull($userModel, "User model should be resolvable");
        
        $dashboardModel = $this->container->resolve(\Models\Dashboard::class);
        $this->assertNotNull($dashboardModel, "Dashboard model should be resolvable");
        
        $kpiModel = $this->container->resolve(\Models\Kpi::class);
        $this->assertNotNull($kpiModel, "KPI model should be resolvable");
        
        $kpiEntryModel = $this->container->resolve(\Models\KpiEntry::class);
        $this->assertNotNull($kpiEntryModel, "KPI Entry model should be resolvable");
        
        $shareTokenModel = $this->container->resolve(\Models\ShareToken::class);
        $this->assertNotNull($shareTokenModel, "Share Token model should be resolvable");
        
        echo "✅ All models resolved successfully\n\n";
    }
    
    public function testDatabaseConnection()
    {
        echo "🗄️  Testing Database Connection...\n";
        
        // Test database connection
        $stmt = $this->db->prepare("SELECT 1 as test");
        $stmt->execute();
        $result = $stmt->fetch();
        
        $this->assertEquals(1, $result['test'], "Database connection should work");
        
        echo "✅ Database connection successful\n\n";
    }
    
    public function testModelRelationships()
    {
        echo "🔗 Testing Model Relationships...\n";
        
        try {
            // Create test data
            $userId = $this->createTestUser('test_relationship@example.com');
            $dashboardId = $this->createTestDashboard($userId);
            $kpiId = $this->createTestKpi($userId);
            
            // Test relationships
            $userModel = $this->container->resolve(\Models\User::class);
            $dashboardModel = $this->container->resolve(\Models\Dashboard::class);
            $kpiModel = $this->container->resolve(\Models\Kpi::class);
            
            // Test user can access their dashboard
            $dashboard = $dashboardModel->findById($dashboardId);
            $this->assertNotNull($dashboard, "Dashboard should exist");
            $this->assertEquals($userId, $dashboard['user_id'], "Dashboard should belong to user");
            
            // Test user can access their KPI
            $kpi = $kpiModel->findById($kpiId);
            $this->assertNotNull($kpi, "KPI should exist");
            $this->assertEquals($userId, $kpi['user_id'], "KPI should belong to user");
            
            echo "✅ Model relationships working correctly\n\n";
            
        } finally {
            $this->cleanupTestData();
        }
    }
    
    public function testTransactionHandling()
    {
        echo "💾 Testing Transaction Handling...\n";
        
        try {
            $userId = $this->createTestUser('test_transaction@example.com');
            
            // Test transaction rollback with direct SQL
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare('INSERT INTO dashboards (name, description, layout, user_id) VALUES (?, ?, ?, ?)');
            $stmt->execute(['Test Dashboard 1', 'Description', '[]', $userId]);
            $id1 = $this->db->lastInsertId();
            
            $stmt->execute(['Test Dashboard 2', 'Description', '[]', $userId]);
            $id2 = $this->db->lastInsertId();
            
            $this->db->rollBack();
            
            // Verify rollback worked
            $stmt = $this->db->prepare('SELECT id FROM dashboards WHERE id IN (?, ?)');
            $stmt->execute([$id1, $id2]);
            $results = $stmt->fetchAll();
            
            $this->assertTrue(count($results) === 0, "Dashboards should be rolled back");
            
            echo "✅ Transaction handling working correctly\n\n";
            
        } finally {
            $this->cleanupTestData();
        }
    }
    
    public function runAllTests()
    {
        echo "🚀 Starting Model Integration Tests\n";
        echo "=====================================\n\n";
        
        try {
            $this->testDependencyInjection();
            $this->testDatabaseConnection();
            $this->testModelRelationships();
            $this->testTransactionHandling();
            
            echo "🎉 All Model Integration Tests Passed!\n";
            echo "=====================================\n";
            
        } catch (\Exception $e) {
            echo "❌ Test Failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            exit(1);
        }
    }
}

// Run the tests
$test = new ModelIntegrationTest();
$test->runAllTests();
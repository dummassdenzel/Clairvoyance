<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

/**
 * Dashboard Model Test
 * 
 * Tests Dashboard model functionality.
 */

class DashboardModelTest extends TestCase
{
    public function testDashboardCreation()
    {
        echo "📊 Testing Dashboard Creation...\n";
        
        $dashboardModel = $this->container->resolve(\Models\Dashboard::class);
        $userId = $this->createTestUser('test_dashboard@example.com');
        
        $layout = json_encode([
            ['id' => 1, 'type' => 'widget', 'title' => 'Test Widget', 'x' => 0, 'y' => 0, 'w' => 4, 'h' => 3]
        ]);
        
        $result = $dashboardModel->create('Test Dashboard', 'Test Description', $layout, $userId);
        $this->assertTrue($result['success'], "Dashboard creation should succeed");
        $this->assertArrayHasKey('id', $result, "Result should contain dashboard ID");
        
        $dashboardId = $result['id'];
        $dashboard = $dashboardModel->findById($dashboardId);
        $this->assertNotNull($dashboard, "Created dashboard should be findable");
        $this->assertEquals('Test Dashboard', $dashboard['name'], "Name should match");
        $this->assertEquals($userId, $dashboard['user_id'], "User ID should match");
        
        echo "✅ Dashboard creation test passed\n\n";
    }
    
    public function testDashboardValidation()
    {
        echo "✅ Testing Dashboard Validation...\n";
        
        $dashboardModel = $this->container->resolve(\Models\Dashboard::class);
        
        // Test layout validation
        $validLayout = json_encode([['id' => 1, 'type' => 'widget']]);
        $this->assertTrue($dashboardModel->validateLayout($validLayout), "Valid layout should pass validation");
        
        $invalidLayout = 'invalid json';
        $this->assertFalse($dashboardModel->validateLayout($invalidLayout), "Invalid layout should fail validation");
        
        // Test name validation
        $this->assertTrue($dashboardModel->validateName('Valid Dashboard Name'), "Valid name should pass validation");
        $this->assertFalse($dashboardModel->validateName(''), "Empty name should fail validation");
        $this->assertFalse($dashboardModel->validateName(str_repeat('a', 256)), "Too long name should fail validation");
        
        // Test description validation
        $this->assertTrue($dashboardModel->validateDescription('Valid description'), "Valid description should pass validation");
        $this->assertTrue($dashboardModel->validateDescription(''), "Empty description should pass validation");
        
        echo "✅ Dashboard validation test passed\n\n";
    }
    
    public function testDashboardAccessManagement()
    {
        echo "👥 Testing Dashboard Access Management...\n";
        
        $dashboardModel = $this->container->resolve(\Models\Dashboard::class);
        $ownerId = $this->createTestUser('test_owner@example.com');
        $viewerId = $this->createTestUser('test_viewer@example.com');
        
        $dashboardId = $this->createTestDashboard($ownerId, 'Access Test Dashboard');
        
        // Test adding viewer
        $addResult = $dashboardModel->addViewer($dashboardId, $viewerId);
        $this->assertTrue($addResult, "Adding viewer should succeed");
        
        // Test viewer access
        $hasAccess = $dashboardModel->hasViewerAccess($dashboardId, $viewerId);
        $this->assertTrue($hasAccess, "Viewer should have access");
        
        // Test getting viewers
        $viewers = $dashboardModel->getViewers($dashboardId);
        $this->assertTrue(count($viewers) > 0, "Should have viewers");
        $this->assertEquals($viewerId, $viewers[0]['id'], "Viewer ID should match");
        
        // Test removing viewer
        $removeResult = $dashboardModel->removeViewer($dashboardId, $viewerId);
        $this->assertTrue($removeResult, "Removing viewer should succeed");
        
        // Test access after removal
        $hasAccessAfter = $dashboardModel->hasViewerAccess($dashboardId, $viewerId);
        $this->assertFalse($hasAccessAfter, "Viewer should not have access after removal");
        
        echo "✅ Dashboard access management test passed\n\n";
    }
    
    public function testDashboardUpdate()
    {
        echo "🔄 Testing Dashboard Update...\n";
        
        $dashboardModel = $this->container->resolve(\Models\Dashboard::class);
        $userId = $this->createTestUser('test_update@example.com');
        
        $dashboardId = $this->createTestDashboard($userId, 'Original Name');
        
        // Test update
        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'layout' => json_encode([['id' => 2, 'type' => 'updated_widget']])
        ];
        
        $updateResult = $dashboardModel->update($dashboardId, $updateData);
        $this->assertTrue($updateResult, "Dashboard update should succeed");
        
        // Verify update
        $updatedDashboard = $dashboardModel->findById($dashboardId);
        $this->assertEquals('Updated Name', $updatedDashboard['name'], "Name should be updated");
        $this->assertEquals('Updated Description', $updatedDashboard['description'], "Description should be updated");
        
        echo "✅ Dashboard update test passed\n\n";
    }
    
    public function testDashboardDeletion()
    {
        echo "🗑️  Testing Dashboard Deletion...\n";
        
        $dashboardModel = $this->container->resolve(\Models\Dashboard::class);
        $userId = $this->createTestUser('test_delete@example.com');
        
        $dashboardId = $this->createTestDashboard($userId, 'Delete Test Dashboard');
        
        // Verify dashboard exists
        $dashboard = $dashboardModel->findById($dashboardId);
        $this->assertNotNull($dashboard, "Dashboard should exist before deletion");
        
        // Delete dashboard
        $deleteResult = $dashboardModel->delete($dashboardId);
        $this->assertTrue($deleteResult, "Dashboard deletion should succeed");
        
        // Verify deletion
        $deletedDashboard = $dashboardModel->findById($dashboardId);
        $this->assertTrue($deletedDashboard === null, "Dashboard should not exist after deletion");
        
        echo "✅ Dashboard deletion test passed\n\n";
    }
    
    public function testDashboardStatistics()
    {
        echo "📈 Testing Dashboard Statistics...\n";
        
        $dashboardModel = $this->container->resolve(\Models\Dashboard::class);
        $userId = $this->createTestUser('test_stats@example.com');
        
        // Create multiple dashboards
        $this->createTestDashboard($userId, 'Dashboard 1');
        $this->createTestDashboard($userId, 'Dashboard 2');
        
        // Test statistics
        $stats = $dashboardModel->getStats($userId);
        $this->assertArrayHasKey('total_dashboards', $stats, "Stats should contain total_dashboards");
        $this->assertArrayHasKey('recent_dashboards', $stats, "Stats should contain recent_dashboards");
        $this->assertTrue($stats['total_dashboards'] >= 2, "Should have at least 2 dashboards");
        
        echo "✅ Dashboard statistics test passed\n\n";
    }
    
    public function runAllTests()
    {
        echo "🚀 Starting Dashboard Model Tests\n";
        echo "==================================\n\n";
        
        try {
            $this->testDashboardCreation();
            $this->testDashboardValidation();
            $this->testDashboardAccessManagement();
            $this->testDashboardUpdate();
            $this->testDashboardDeletion();
            $this->testDashboardStatistics();
            
            echo "🎉 All Dashboard Model Tests Passed!\n";
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
$test = new DashboardModelTest();
$test->runAllTests();

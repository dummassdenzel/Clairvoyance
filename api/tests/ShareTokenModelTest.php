<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

/**
 * Share Token Model Test
 * 
 * Tests Share Token model functionality.
 */

class ShareTokenModelTest extends TestCase
{
    public function testShareTokenCreation()
    {
        echo "🔗 Testing Share Token Creation...\n";
        
        $shareTokenModel = $this->container->resolve(\Models\ShareToken::class);
        $userId = $this->createTestUser('test_share_token@example.com');
        $dashboardId = $this->createTestDashboard($userId, 'Share Test Dashboard');
        
        $token = $shareTokenModel->generateToken();
        $expiresAt = $shareTokenModel->generateExpirationDate(7);
        
        $result = $shareTokenModel->create($dashboardId, $token, $expiresAt);
        $this->assertTrue($result['success'], "Share token creation should succeed");
        $this->assertArrayHasKey('id', $result, "Result should contain token ID");
        
        $tokenId = $result['id'];
        $tokenData = $shareTokenModel->findByToken($token);
        $this->assertNotNull($tokenData, "Created token should be findable");
        $this->assertEquals($dashboardId, $tokenData['dashboard_id'], "Dashboard ID should match");
        $this->assertEquals($token, $tokenData['token'], "Token should match");
        
        echo "✅ Share token creation test passed\n\n";
    }
    
    public function testTokenValidation()
    {
        echo "✅ Testing Token Validation...\n";
        
        $shareTokenModel = $this->container->resolve(\Models\ShareToken::class);
        $userId = $this->createTestUser('test_token_validation@example.com');
        $dashboardId = $this->createTestDashboard($userId, 'Validation Test Dashboard');
        
        // Create valid token
        $validToken = $shareTokenModel->generateToken();
        $futureExpiry = $shareTokenModel->generateExpirationDate(7);
        $shareTokenModel->create($dashboardId, $validToken, $futureExpiry);
        
        // Test valid token
        $isValid = $shareTokenModel->isValid($validToken);
        $this->assertTrue($isValid, "Valid token should pass validation");
        
        // Test invalid token
        $invalidToken = 'invalid_token_12345';
        $isInvalid = $shareTokenModel->isValid($invalidToken);
        $this->assertFalse($isInvalid, "Invalid token should fail validation");
        
        // Create expired token
        $expiredToken = $shareTokenModel->generateToken();
        $pastExpiry = date('Y-m-d H:i:s', strtotime('-1 day'));
        $shareTokenModel->create($dashboardId, $expiredToken, $pastExpiry);
        
        // Test expired token
        $isExpired = $shareTokenModel->isExpired($expiredToken);
        $this->assertTrue($isExpired, "Expired token should be marked as expired");
        
        $isValidExpired = $shareTokenModel->isValid($expiredToken);
        $this->assertFalse($isValidExpired, "Expired token should fail validation");
        
        echo "✅ Token validation test passed\n\n";
    }
    
    public function testTokenGeneration()
    {
        echo "🎲 Testing Token Generation...\n";
        
        $shareTokenModel = $this->container->resolve(\Models\ShareToken::class);
        
        // Test token generation
        $token1 = $shareTokenModel->generateToken();
        $token2 = $shareTokenModel->generateToken();
        
        $this->assertTrue(strlen($token1) > 0, "Generated token should not be empty");
        $this->assertTrue(strlen($token2) > 0, "Generated token should not be empty");
        $this->assertTrue($token1 !== $token2, "Generated tokens should be unique");
        
        // Test expiration date generation
        $expiry7Days = $shareTokenModel->generateExpirationDate(7);
        $expiry30Days = $shareTokenModel->generateExpirationDate(30);
        
        $this->assertTrue(strlen($expiry7Days) > 0, "Generated expiry date should not be empty");
        $this->assertTrue(strlen($expiry30Days) > 0, "Generated expiry date should not be empty");
        
        // Verify dates are in the future
        $now = new DateTime();
        $expiry7 = new DateTime($expiry7Days);
        $expiry30 = new DateTime($expiry30Days);
        
        $this->assertTrue($expiry7 > $now, "7-day expiry should be in the future");
        $this->assertTrue($expiry30 > $now, "30-day expiry should be in the future");
        $this->assertTrue($expiry30 > $expiry7, "30-day expiry should be later than 7-day expiry");
        
        echo "✅ Token generation test passed\n\n";
    }
    
    public function testTokenDeletion()
    {
        echo "🗑️  Testing Token Deletion...\n";
        
        $shareTokenModel = $this->container->resolve(\Models\ShareToken::class);
        $userId = $this->createTestUser('test_token_delete@example.com');
        $dashboardId = $this->createTestDashboard($userId, 'Delete Test Dashboard');
        
        // Create token
        $token = $shareTokenModel->generateToken();
        $expiresAt = $shareTokenModel->generateExpirationDate(7);
        $result = $shareTokenModel->create($dashboardId, $token, $expiresAt);
        $tokenId = $result['id'];
        
        // Verify token exists
        $tokenData = $shareTokenModel->findByToken($token);
        $this->assertNotNull($tokenData, "Token should exist before deletion");
        
        // Delete by ID
        $deleteResult = $shareTokenModel->delete($tokenId);
        $this->assertTrue($deleteResult, "Token deletion by ID should succeed");
        
        // Verify deletion
        $deletedToken = $shareTokenModel->findByToken($token);
        $this->assertTrue($deletedToken === null, "Token should not exist after deletion");
        
        // Create another token for deletion by token
        $token2 = $shareTokenModel->generateToken();
        $shareTokenModel->create($dashboardId, $token2, $expiresAt);
        
        // Delete by token
        $deleteByTokenResult = $shareTokenModel->deleteByToken($token2);
        $this->assertTrue($deleteByTokenResult, "Token deletion by token should succeed");
        
        // Verify deletion
        $deletedToken2 = $shareTokenModel->findByToken($token2);
        $this->assertTrue($deletedToken2 === null, "Token should not exist after deletion by token");
        
        echo "✅ Token deletion test passed\n\n";
    }
    
    public function testExpiredTokenCleanup()
    {
        echo "🧹 Testing Expired Token Cleanup...\n";
        
        $shareTokenModel = $this->container->resolve(\Models\ShareToken::class);
        $userId = $this->createTestUser('test_cleanup@example.com');
        $dashboardId = $this->createTestDashboard($userId, 'Cleanup Test Dashboard');
        
        // Create valid token
        $validToken = $shareTokenModel->generateToken();
        $futureExpiry = $shareTokenModel->generateExpirationDate(7);
        $shareTokenModel->create($dashboardId, $validToken, $futureExpiry);
        
        // Create expired token
        $expiredToken = $shareTokenModel->generateToken();
        $pastExpiry = date('Y-m-d H:i:s', strtotime('-1 day'));
        $shareTokenModel->create($dashboardId, $expiredToken, $pastExpiry);
        
        // Verify both tokens exist
        $validTokenData = $shareTokenModel->findByToken($validToken);
        $expiredTokenData = $shareTokenModel->findByToken($expiredToken);
        $this->assertNotNull($validTokenData, "Valid token should exist before cleanup");
        $this->assertNotNull($expiredTokenData, "Expired token should exist before cleanup");
        
        // Clean up expired tokens
        $deletedCount = $shareTokenModel->deleteExpired();
        $this->assertTrue($deletedCount >= 1, "Should delete at least 1 expired token");
        
        // Verify expired token is deleted but valid token remains
        $validTokenAfter = $shareTokenModel->findByToken($validToken);
        $expiredTokenAfter = $shareTokenModel->findByToken($expiredToken);
        $this->assertNotNull($validTokenAfter, "Valid token should still exist after cleanup");
        $this->assertTrue($expiredTokenAfter === null, "Expired token should be deleted after cleanup");
        
        echo "✅ Expired token cleanup test passed\n\n";
    }
    
    public function testDashboardTokenQueries()
    {
        echo "📊 Testing Dashboard Token Queries...\n";
        
        $shareTokenModel = $this->container->resolve(\Models\ShareToken::class);
        $userId = $this->createTestUser('test_dashboard_tokens@example.com');
        $dashboardId = $this->createTestDashboard($userId, 'Dashboard Tokens Test');
        
        // Create multiple tokens for the same dashboard
        $token1 = $shareTokenModel->generateToken();
        $token2 = $shareTokenModel->generateToken();
        $token3 = $shareTokenModel->generateToken();
        
        $expiresAt = $shareTokenModel->generateExpirationDate(7);
        
        $shareTokenModel->create($dashboardId, $token1, $expiresAt);
        $shareTokenModel->create($dashboardId, $token2, $expiresAt);
        $shareTokenModel->create($dashboardId, $token3, $expiresAt);
        
        // Test finding tokens by dashboard ID
        $dashboardTokens = $shareTokenModel->findByDashboardId($dashboardId);
        $this->assertTrue(count($dashboardTokens) >= 3, "Should find all tokens for dashboard");
        
        // Verify all tokens belong to the correct dashboard
        foreach ($dashboardTokens as $tokenData) {
            $this->assertEquals($dashboardId, $tokenData['dashboard_id'], "Token should belong to correct dashboard");
        }
        
        echo "✅ Dashboard token queries test passed\n\n";
    }
    
    public function testTokenStatistics()
    {
        echo "📈 Testing Token Statistics...\n";
        
        $shareTokenModel = $this->container->resolve(\Models\ShareToken::class);
        $userId = $this->createTestUser('test_token_stats@example.com');
        $dashboardId = $this->createTestDashboard($userId, 'Token Stats Test Dashboard');
        
        // Create active tokens
        $activeToken1 = $shareTokenModel->generateToken();
        $activeToken2 = $shareTokenModel->generateToken();
        $futureExpiry = $shareTokenModel->generateExpirationDate(7);
        
        $shareTokenModel->create($dashboardId, $activeToken1, $futureExpiry);
        $shareTokenModel->create($dashboardId, $activeToken2, $futureExpiry);
        
        // Create expired token
        $expiredToken = $shareTokenModel->generateToken();
        $pastExpiry = date('Y-m-d H:i:s', strtotime('-1 day'));
        $shareTokenModel->create($dashboardId, $expiredToken, $pastExpiry);
        
        // Test statistics
        $stats = $shareTokenModel->getStats($dashboardId);
        $this->assertArrayHasKey('total_tokens', $stats, "Stats should contain total_tokens");
        $this->assertArrayHasKey('active_tokens', $stats, "Stats should contain active_tokens");
        $this->assertArrayHasKey('expired_tokens', $stats, "Stats should contain expired_tokens");
        $this->assertTrue($stats['total_tokens'] >= 3, "Should have at least 3 total tokens");
        $this->assertTrue($stats['active_tokens'] >= 2, "Should have at least 2 active tokens");
        $this->assertTrue($stats['expired_tokens'] >= 1, "Should have at least 1 expired token");
        
        echo "✅ Token statistics test passed\n\n";
    }
    
    public function testTokenUniqueness()
    {
        echo "🔒 Testing Token Uniqueness...\n";
        
        $shareTokenModel = $this->container->resolve(\Models\ShareToken::class);
        $userId = $this->createTestUser('test_uniqueness@example.com');
        $dashboardId = $this->createTestDashboard($userId, 'Uniqueness Test Dashboard');
        
        // Generate multiple tokens and verify they're unique
        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            $token = $shareTokenModel->generateToken();
            $tokens[] = $token;
        }
        
        // Check uniqueness
        $uniqueTokens = array_unique($tokens);
        $this->assertEquals(count($tokens), count($uniqueTokens), "All generated tokens should be unique");
        
        // Test that we can't create duplicate tokens
        $token = $shareTokenModel->generateToken();
        $expiresAt = $shareTokenModel->generateExpirationDate(7);
        
        // First creation should succeed
        $result1 = $shareTokenModel->create($dashboardId, $token, $expiresAt);
        $this->assertTrue($result1['success'], "First token creation should succeed");
        
        // Second creation with same token should fail (due to unique constraint)
        $result2 = $shareTokenModel->create($dashboardId, $token, $expiresAt);
        $this->assertFalse($result2['success'], "Duplicate token creation should fail");
        
        echo "✅ Token uniqueness test passed\n\n";
    }
    
    public function runAllTests()
    {
        echo "🚀 Starting Share Token Model Tests\n";
        echo "===================================\n\n";
        
        try {
            $this->testShareTokenCreation();
            $this->testTokenValidation();
            $this->testTokenGeneration();
            $this->testTokenDeletion();
            $this->testExpiredTokenCleanup();
            $this->testDashboardTokenQueries();
            $this->testTokenStatistics();
            $this->testTokenUniqueness();
            
            echo "🎉 All Share Token Model Tests Passed!\n";
            echo "===================================\n";
            
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
$test = new ShareTokenModelTest();
$test->runAllTests();

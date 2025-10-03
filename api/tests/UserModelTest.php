<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

/**
 * User Model Test
 * 
 * Tests User model functionality.
 */

class UserModelTest extends TestCase
{
    public function testUserCreation()
    {
        echo "👤 Testing User Creation...\n";
        
        $userModel = $this->container->resolve(\Models\User::class);
        
        $result = $userModel->create('test_create@example.com', 'password123', 'editor');
        $this->assertTrue($result['success'], "User creation should succeed");
        $this->assertArrayHasKey('id', $result, "Result should contain user ID");
        
        $userId = $result['id'];
        $user = $userModel->findById($userId);
        $this->assertNotNull($user, "Created user should be findable");
        $this->assertEquals('test_create@example.com', $user['email'], "Email should match");
        $this->assertEquals('editor', $user['role'], "Role should match");
        
        echo "✅ User creation test passed\n\n";
    }
    
    public function testUserAuthentication()
    {
        echo "🔐 Testing User Authentication...\n";
        
        $userModel = $this->container->resolve(\Models\User::class);
        
        // Create user
        $result = $userModel->create('test_auth@example.com', 'password123', 'editor');
        $this->assertTrue($result['success'], "User creation should succeed");
        
        // Test valid authentication
        $authResult = $userModel->authenticate('test_auth@example.com', 'password123');
        $this->assertTrue($authResult['success'], "Valid authentication should succeed");
        $this->assertArrayHasKey('user', $authResult, "Auth result should contain user data");
        
        // Test invalid authentication
        $invalidAuth = $userModel->authenticate('test_auth@example.com', 'wrongpassword');
        $this->assertFalse($invalidAuth['success'], "Invalid authentication should fail");
        
        echo "✅ User authentication test passed\n\n";
    }
    
    public function testUserValidation()
    {
        echo "✅ Testing User Validation...\n";
        
        $userModel = $this->container->resolve(\Models\User::class);
        
        // Test email validation
        $this->assertTrue($userModel->validateEmail('valid@example.com'), "Valid email should pass validation");
        $this->assertFalse($userModel->validateEmail('invalid-email'), "Invalid email should fail validation");
        
        // Test role validation
        $this->assertTrue($userModel->validateRole('admin'), "Valid role should pass validation");
        $this->assertTrue($userModel->validateRole('editor'), "Valid role should pass validation");
        $this->assertTrue($userModel->validateRole('viewer'), "Valid role should pass validation");
        $this->assertFalse($userModel->validateRole('invalid_role'), "Invalid role should fail validation");
        
        echo "✅ User validation test passed\n\n";
    }
    
    public function testUserUpdate()
    {
        echo "🔄 Testing User Update...\n";
        
        $userModel = $this->container->resolve(\Models\User::class);
        
        // Create user
        $result = $userModel->create('test_update@example.com', 'password123', 'editor');
        $userId = $result['id'];
        
        // Test role update
        $updateResult = $userModel->updateRole($userId, 'admin');
        $this->assertTrue($updateResult, "Role update should succeed");
        
        // Verify update
        $updatedUser = $userModel->findById($userId);
        $this->assertEquals('admin', $updatedUser['role'], "Role should be updated");
        
        echo "✅ User update test passed\n\n";
    }
    
    public function testUserDeletion()
    {
        echo "🗑️  Testing User Deletion...\n";
        
        $userModel = $this->container->resolve(\Models\User::class);
        
        // Create user
        $result = $userModel->create('test_delete@example.com', 'password123', 'editor');
        $userId = $result['id'];
        
        // Verify user exists
        $user = $userModel->findById($userId);
        $this->assertNotNull($user, "User should exist before deletion");
        
        // Delete user
        $deleteResult = $userModel->delete($userId);
        $this->assertTrue($deleteResult, "User deletion should succeed");
        
        // Verify deletion
        $deletedUser = $userModel->findById($userId);
        $this->assertTrue($deletedUser === null, "User should not exist after deletion");
        
        echo "✅ User deletion test passed\n\n";
    }
    
    public function testUserList()
    {
        echo "📋 Testing User List...\n";
        
        $userModel = $this->container->resolve(\Models\User::class);
        
        // Create test users
        $userModel->create('test_list1@example.com', 'password123', 'editor');
        $userModel->create('test_list2@example.com', 'password123', 'viewer');
        
        // Test listing users
        $users = $userModel->listAll();
        $this->assertTrue(count($users) >= 2, "Should have at least 2 users");
        
        // Verify user data structure
        $firstUser = $users[0];
        $this->assertArrayHasKey('id', $firstUser, "User should have ID");
        $this->assertArrayHasKey('email', $firstUser, "User should have email");
        $this->assertArrayHasKey('role', $firstUser, "User should have role");
        
        echo "✅ User list test passed\n\n";
    }
    
    public function testPasswordHashing()
    {
        echo "�� Testing Password Hashing...\n";
        
        $userModel = $this->container->resolve(\Models\User::class);
        
        // Test password hashing
        $password = 'test_password_123';
        $hash = $userModel->hashPassword($password);
        
        $this->assertTrue(strlen($hash) > 0, "Hash should not be empty");
        $this->assertTrue($hash !== $password, "Hash should be different from original password");
        
        // Test password verification
        $this->assertTrue(password_verify($password, $hash), "Password verification should work");
        $this->assertFalse(password_verify('wrong_password', $hash), "Wrong password should fail verification");
        
        echo "✅ Password hashing test passed\n\n";
    }
    
    public function runAllTests()
    {
        echo "🚀 Starting User Model Tests\n";
        echo "============================\n\n";
        
        try {
            $this->testUserCreation();
            $this->testUserAuthentication();
            $this->testUserValidation();
            $this->testUserUpdate();
            $this->testUserDeletion();
            $this->testUserList();
            $this->testPasswordHashing();
            
            echo "🎉 All User Model Tests Passed!\n";
            echo "============================\n";
            
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
$test = new UserModelTest();
$test->runAllTests();
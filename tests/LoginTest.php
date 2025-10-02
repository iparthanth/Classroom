<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/MockAuth.php';

class LoginTest extends TestCase
{
    private $auth;
    private $db;

    protected function setUp(): void
    {
        // Create a mock PDO object
        $this->db = $this->createMock(PDO::class);
        
        // Create the Auth instance with mocked database and disabled sessions
        $this->auth = new Auth($this->db, false);
    }

    public function testLoginWithEmptyCredentials()
    {
        $result = $this->auth->login('', '');
        $this->assertFalse($result['success']);
        $this->assertEquals('Username and password are required', $result['message']);
    }

    public function testLoginWithInvalidCredentials()
    {
        // Mock the database query for invalid credentials
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(false);
        $stmt->method('execute')->willReturn(true);
        
        $this->db->method('prepare')->willReturn($stmt);

        $result = $this->auth->login('invalid_user', 'wrong_password');
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid credentials', $result['message']);
    }

    public function testSuccessfulLogin()
    {
        // Mock user data
        $userData = [
            'id' => 1,
            'username' => 'testuser',
            'password' => password_hash('correct_password', PASSWORD_DEFAULT),
            'email' => 'test@example.com',
            'full_name' => 'Test User',
            'role' => 'student',
            'is_active' => 1
        ];

        // Mock the database query for valid credentials
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn($userData);
        $stmt->method('execute')->willReturn(true);
        
        $this->db->method('prepare')->willReturn($stmt);

        $result = $this->auth->login('testuser', 'correct_password');
        $this->assertTrue($result['success']);
        $this->assertEquals('Login successful', $result['message']);
        $this->assertEquals($userData, $result['user']);
    }

    public function testLoginWithInactiveUser()
    {
        // Mock inactive user data
        $userData = [
            'id' => 2,
            'username' => 'inactive_user',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'email' => 'inactive@example.com',
            'full_name' => 'Inactive User',
            'role' => 'student',
            'is_active' => 0
        ];

        // Mock the database query
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(false);
        $stmt->method('execute')->willReturn(true);
        
        $this->db->method('prepare')->willReturn($stmt);

        $result = $this->auth->login('inactive_user', 'password');
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid credentials', $result['message']);
    }

    public function testLogout()
    {
        $result = $this->auth->logout();
        $this->assertTrue($result['success']);
        $this->assertEquals('Logged out successfully', $result['message']);
    }

    public function testIsLoggedIn()
    {
        $this->assertFalse($this->auth->isLoggedIn());
    }
}
<?php
/**
 * Auth Controller
 * Handles user authentication
 */

class AuthController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Login user
     */
    public function login($data) {
        if (empty($data['email']) || empty($data['password'])) {
            throw new Exception('Email and password are required');
        }
        
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = :email AND status = 'active'",
            ['email' => $data['email']]
        );
        
        if (!$user || !password_verify($data['password'], $user['password'])) {
            throw new Exception('Invalid credentials');
        }
        
        // Update last login
        $this->db->update(
            'users',
            ['last_login' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $user['id']]
        );
        
        // Regenerate session ID to prevent session fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // Set session variables (session already started in API index.php)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['role'] = $user['role']; // For admin panel compatibility
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['is_admin'] = ($user['role'] === 'admin');

        // Generate CSRF token for admins
        if ($_SESSION['is_admin']) {
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
        }
        
        unset($user['password']);
        
        $responseData = [
            'user' => $user,
            'message' => 'Login successful'
        ];

        // Also return CSRF token in login response for admin users
        if ($_SESSION['is_admin']) {
            $responseData['csrf_token'] = $_SESSION['csrf_token'];
        }

        return $responseData;
    }
    
    /**
     * Register new user
     */
    public function register($data) {
        $required = ['email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '{$field}' is required");
            }
        }
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Check if email exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = :email",
            ['email' => $data['email']]
        );
        
        if ($existing) {
            throw new Exception('Email already registered');
        }
        
        // Validate password strength
        if (strlen($data['password']) < 8) {
            throw new Exception('Password must be at least 8 characters');
        }
        
        // Create user
        $userId = $this->db->insert('users', [
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'role' => 'customer',
            'status' => 'active'
        ]);
        
        // Auto login (session already started)
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $data['email'];
        $_SESSION['user_role'] = 'customer';
        $_SESSION['role'] = 'customer'; // For compatibility
        $_SESSION['first_name'] = $data['first_name'];
        $_SESSION['last_name'] = $data['last_name'];
        
        $user = $this->db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
        unset($user['password']);
        
        return [
            'user' => $user,
            'message' => 'Registration successful'
        ];
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        
        return ['message' => 'Logout successful'];
    }
    
    /**
     * Get current user
     */
    public function me() {
        if (empty($_SESSION['user_id'])) {
            throw new Exception('Not authenticated');
        }
        
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE id = :id",
            ['id' => $_SESSION['user_id']]
        );
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        unset($user['password']);
        return $user;
    }
}

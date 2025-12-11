<?php
/**
 * User Controller
 * Handles customer user operations for admin
 */

require_once __DIR__ . '/../config/database.php';

class UserController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all users (customers only)
     * GET /api/index.php?request=customers
     */
    public function index() {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
            $offset = ($page - 1) * $perPage;
            
            // Get total count (customers only, not admins)
            $countSql = "SELECT COUNT(*) as total FROM users WHERE role = 'customer' OR role IS NULL";
            $totalResult = $this->db->fetchOne($countSql);
            $total = $totalResult['total'];
            
            // Get users with pagination + order aggregates
            $sql = "SELECT 
                        u.id, 
                        u.email, 
                        u.first_name, 
                        u.last_name, 
                        u.phone, 
                        u.status,
                        u.role, 
                        u.created_at, 
                        u.updated_at,
                        (
                            SELECT COUNT(*) 
                            FROM orders o 
                            WHERE o.user_id = u.id
                        ) AS order_count,
                        (
                            SELECT COALESCE(SUM(o.total_amount), 0) 
                            FROM orders o 
                            WHERE o.user_id = u.id
                        ) AS total_spent
                    FROM users u
                    WHERE u.role = 'customer' OR u.role IS NULL
                    ORDER BY u.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $users = $this->db->fetchAll($sql, [
                'limit' => $perPage,
                'offset' => $offset
            ]);
            
            // Normalize status default
            foreach ($users as &$u) {
                if (empty($u['status'])) {
                    $u['status'] = 'unknown';
                }
            }

            return [
                'data' => $users,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $perPage)
                ]
            ];
        } catch (Exception $e) {
            error_log('UserController::index error: ' . $e->getMessage());
            throw new Exception('Failed to fetch users');
        }
    }
    
    /**
     * Get single user by ID
     * GET /api/index.php?request=customers&id={id}
     */
    public function show($id) {
        try {
            $sql = "SELECT 
                        u.id, 
                        u.email, 
                        u.first_name, 
                        u.last_name, 
                        u.phone, 
                        u.status,
                        u.role, 
                        u.created_at, 
                        u.updated_at,
                        (
                            SELECT COUNT(*) 
                            FROM orders o 
                            WHERE o.user_id = u.id
                        ) AS order_count,
                        (
                            SELECT COALESCE(SUM(o.total_amount), 0) 
                            FROM orders o 
                            WHERE o.user_id = u.id
                        ) AS total_spent
                    FROM users u
                    WHERE u.id = :id";
            
            $user = $this->db->fetchOne($sql, ['id' => $id]);
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            if (empty($user['status'])) {
                $user['status'] = 'unknown';
            }

            return $user;
        } catch (Exception $e) {
            error_log('UserController::show error: ' . $e->getMessage());
            throw new Exception('Failed to fetch user');
        }
    }
    
    /**
     * Update user
     * PUT /api/index.php?request=customers&id={id}
     */
    public function update($id, $data) {
        try {
            $allowedFields = ['first_name', 'last_name', 'email', 'phone'];
            $updates = [];
            $params = ['id' => $id];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = :$field";
                    $params[$field] = $data[$field];
                }
            }
            
            if (empty($updates)) {
                throw new Exception('No valid fields to update');
            }
            
            $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            
            $this->db->execute($sql, $params);
            
            return $this->show($id);
        } catch (Exception $e) {
            error_log('UserController::update error: ' . $e->getMessage());
            throw new Exception('Failed to update user');
        }
    }
    
    /**
     * Delete user
     * DELETE /api/index.php?request=customers&id={id}
     */
    public function delete($id) {
        try {
            // Check if user exists and is not admin
            $user = $this->show($id);
            if ($user['role'] === 'admin') {
                throw new Exception('Cannot delete admin users');
            }
            
            $sql = "DELETE FROM users WHERE id = :id";
            $this->db->execute($sql, ['id' => $id]);
            
            return ['success' => true, 'message' => 'User deleted successfully'];
        } catch (Exception $e) {
            error_log('UserController::delete error: ' . $e->getMessage());
            throw new Exception('Failed to delete user');
        }
    }
}

<?php
namespace App\Services;

use App\Core\Application;
use PDO;

class UserService {
    private $db;

    public function __construct() {
        $this->db = Application::getInstance()->getDB()->getConnection();
        if (!$this->db) {
            error_log("Failed to get database connection in UserService");
            throw new \Exception("Database connection failed");
        }
    }

    /**
     * Create a new user
     */
    public function createUser(array $data) {
        try {
            error_log("Starting user creation process...");
            
            // Validate required fields
            if (empty($data['username']) || empty($data['password']) || empty($data['email'])) {
                error_log("Missing required fields in user creation");
                throw new \Exception('All fields are required');
            }

            // Check if username exists
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$data['username']]);
            if ($stmt->fetchColumn() > 0) {
                error_log("Username already exists: " . $data['username']);
                throw new \Exception('Username already exists');
            }

            // Check if email exists
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetchColumn() > 0) {
                error_log("Email already exists: " . $data['email']);
                throw new \Exception('Email already exists');
            }

            error_log("Preparing to insert new user...");

            // Insert new user with named parameters
            $sql = "INSERT INTO users (username, password, email, role, status, created_at, updated_at) 
                    VALUES (:username, :password, :email, :role, :status, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            
            $params = [
                ':username' => $data['username'],
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':email' => $data['email'],
                ':role' => 'customer',
                ':status' => 'active'
            ];

            error_log("Executing user insert with params: " . print_r($params, true));
            
            $success = $stmt->execute($params);

            if (!$success) {
                error_log("Failed to insert user. PDO error info: " . print_r($stmt->errorInfo(), true));
                throw new \Exception('Failed to create user account');
            }

            $userId = $this->db->lastInsertId();
            error_log("Successfully created user with ID: " . $userId);
            
            return $userId;

        } catch (\PDOException $e) {
            error_log("PDO Error in createUser: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw new \Exception('Database error: ' . $e->getMessage());
        } catch (\Exception $e) {
            error_log("Error in createUser: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
}

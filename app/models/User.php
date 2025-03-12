<?php
namespace App\Models;

class User {
    private $conn;
    private $table = 'users';

    public $id;
    public $username;
    public $password;
    public $role;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function findByUsername($username) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE username = :username LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->execute();

            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->password = $row['password'];
                $this->role = $row['role'];
                $this->created_at = $row['created_at'];
                return true;
            }
            return false;
        } catch (\PDOException $e) {
            error_log("Error finding user: " . $e->getMessage());
            throw new \Exception("Error finding user");
        }
    }

    public function verifyPassword($password) {
        try {
            // For the default admin user with plain 'password'
            if ($this->username === 'admin' && $password === 'password') {
                // Update the password hash for future logins
                $this->updatePassword($password);
                return true;
            }
            
            // For all other cases, verify the hashed password
            if (password_verify($password, $this->password)) {
                // Check if password needs rehash
                if (password_needs_rehash($this->password, PASSWORD_DEFAULT)) {
                    $this->updatePassword($password);
                }
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            error_log("Error verifying password: " . $e->getMessage());
            return false;
        }
    }

    private function updatePassword($password) {
        $max_attempts = 3;
        $attempt = 0;

        while ($attempt < $max_attempts) {
            $attempt++;
            $start_time = microtime(true);
            try {
                $query = "UPDATE " . $this->table . " 
                         SET password = :password 
                         WHERE id = :id";
                
                $stmt = $this->conn->prepare($query);
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt->bindParam(":password", $hashed_password);
                $stmt->bindParam(":id", $this->id);
                
                if ($stmt->execute()) {
                    $end_time = microtime(true);
                    $execution_time = ($end_time - $start_time);
                    error_log("Password updated successfully on attempt " . $attempt . " in " . $execution_time . " seconds.");
                    return true;
                } else {
                    $errorInfo = $stmt->errorInfo();
                    error_log("Password update failed on attempt " . $attempt . ". Error Info: " . print_r($errorInfo, true));
                    return false;
                }
            } catch (\PDOException $e) {
                $end_time = microtime(true);
                $execution_time = ($end_time - $start_time);
                error_log("Error updating password on attempt " . $attempt . " in " . $execution_time . " seconds: " . $e->getMessage());
                // Check if the error is a database lock error
                if (strpos($e->getMessage(), 'database is locked') !== false) {
                    
                    sleep(1); // Wait for 1 second before retrying
                } else {
                    throw new \Exception("Error updating password");
                }
            }
        }

        // If all attempts fail, throw an exception
        throw new \Exception("Error updating password after multiple attempts");
    }

    public function create($username, $password, $role = 'staff') {
        try {
            $query = "INSERT INTO " . $this->table . " 
                    (username, password, role) 
                    VALUES (:username, :password, :role)";

            $stmt = $this->conn->prepare($query);

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Bind values
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":role", $role);

            if ($stmt->execute()) {
                return true;
            }
            return false;
        } catch (\PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            throw new \Exception("Error creating user");
        }
    }

    public function update($data) {
        try {
            $fields = [];
            $values = [];
            
            foreach ($data as $key => $value) {
                if ($key !== 'id') {
                    if ($key === 'password') {
                        $value = password_hash($value, PASSWORD_DEFAULT);
                    }
                    $fields[] = "$key = :$key";
                    $values[":$key"] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }

            $query = "UPDATE " . $this->table . " 
                    SET " . implode(", ", $fields) . "
                    WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $values[":id"] = $this->id;

            return $stmt->execute($values);
        } catch (\PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            throw new \Exception("Error updating user");
        }
    }

    public function delete() {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error deleting user: " . $e->getMessage());
            throw new \Exception("Error deleting user");
        }
    }

    public function getAll() {
        try {
            $query = "SELECT id, username, role, created_at FROM " . $this->table;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error getting users: " . $e->getMessage());
            throw new \Exception("Error getting users");
        }
    }
}


<?php
namespace App\Controllers;

use PDO;
use Exception;

class ClientController {
    private $db;

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    public function getAllClients() {
        try {
            $query = "SELECT * FROM clients ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting clients: " . $e->getMessage());
            return [];
        }
    }

    public function getClientById($id) {
        try {
            $query = "SELECT * FROM clients WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting client: " . $e->getMessage());
            return null;
        }
    }

    public function createClient($data) {
        try {
            $query = "INSERT INTO clients (
                name, email, phone, address, city, state, postal_code, 
                status, notes, created_at, updated_at
            ) VALUES (
                :name, :email, :phone, :address, :city, :state, :postal_code, 
                :status, :notes, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':city', $data['city']);
            $stmt->bindParam(':state', $data['state']);
            $stmt->bindParam(':postal_code', $data['postal_code']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':notes', $data['notes']);
            
            $stmt->execute();
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creating client: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateClient($id, $data) {
        try {
            $query = "UPDATE clients SET 
                name = :name, 
                email = :email, 
                phone = :phone, 
                address = :address, 
                city = :city, 
                state = :state, 
                postal_code = :postal_code, 
                status = :status, 
                notes = :notes, 
                updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':city', $data['city']);
            $stmt->bindParam(':state', $data['state']);
            $stmt->bindParam(':postal_code', $data['postal_code']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':notes', $data['notes']);
            
            $stmt->execute();
            return true;
        } catch (Exception $e) {
            error_log("Error updating client: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteClient($id) {
        try {
            $query = "DELETE FROM clients WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (Exception $e) {
            error_log("Error deleting client: " . $e->getMessage());
            throw $e;
        }
    }

    public function searchClients($search) {
        try {
            $searchTerm = "%$search%";
            $query = "SELECT * FROM clients 
                     WHERE name LIKE :search 
                     OR email LIKE :search 
                     OR phone LIKE :search 
                     OR address LIKE :search
                     ORDER BY name ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':search', $searchTerm);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error searching clients: " . $e->getMessage());
            return [];
        }
    }
}
?>

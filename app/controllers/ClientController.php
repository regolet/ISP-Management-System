<?php
namespace App\Controllers;

class ClientController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getClients($params = []) {
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? '';
        $page = $params['page'] ?? 1;
        $per_page = $params['per_page'] ?? 10;

        $query = "SELECT c.*, p.name AS plan_name
        FROM clients c
        LEFT JOIN client_subscriptions cs ON c.id = cs.client_id
        LEFT JOIN plans p ON cs.plan_id = p.id
        WHERE 1=1";

        if (!empty($search)) {
            $query .= " AND (c.first_name LIKE :search OR c.last_name LIKE :search OR c.email LIKE :search)";
        }

        if (!empty($status)) {
            $query .= " AND c.status = :status";
        }

        $query .= " LIMIT :offset, :per_page";

        $stmt = $this->db->prepare($query);

        if (!empty($search)) {
            $search_param = "%{$search}%";
            $stmt->bindParam(':search', $search_param);
        }

        if (!empty($status)) {
            $stmt->bindParam(':status', $status);
        }

        $offset = ($page - 1) * $per_page;
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->bindParam(':per_page', $per_page, \PDO::PARAM_INT);

        $stmt->execute();
        $clients = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get total clients
        $query = "SELECT COUNT(*) as total FROM clients WHERE 1=1";

        if (!empty($search)) {
            $query .= " AND (first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)";
        }

        if (!empty($status)) {
            $query .= " AND status = :status";
        }

        $stmt = $this->db->prepare($query);

         if (!empty($search)) {
            $search_param = "%{$search}%";
            $stmt->bindParam(':search', $search_param);
        }

        if (!empty($status)) {
            $stmt->bindParam(':status', $status);
        }

        $stmt->execute();
        $total = $stmt->fetch(\PDO::FETCH_ASSOC)['total'];
        $total_pages = ceil($total / $per_page);

        $pagination = [
            'current_page' => (int)$page,
            'last_page' => (int)$total_pages
        ];

        return [
            'clients' => $clients,
            'pagination' => $pagination
        ];
    }

    public function getClientStats() {
        $query = "SELECT COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended
            FROM clients";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'total' => $result['total'],
            'by_status' => [
                'active' => $result['active'],
                'inactive' => $result['inactive'],
                'suspended' => $result['suspended']
            ]
        ];
    }

    public function getClientById($id) {
        $query = "SELECT * FROM clients WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function updateClient($id, $data) {
        $query = "UPDATE clients SET
            first_name = :first_name,
            last_name = :last_name,
            email = :email,
            phone = :phone,
            address = :address,
            city = :city,
            state = :state,
            postal_code = :postal_code,
            status = :status,
            connection_date = :connection_date
            WHERE id = :id";

        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':first_name', $data['first_name']);
        $stmt->bindParam(':last_name', $data['last_name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':state', $data['state']);
        $stmt->bindParam(':postal_code', $data['postal_code']);
        $stmt->bindParam(':status', $data['status']);
        
        // Format connection date
        $connection_date = !empty($data['connection_date']) ? date('Y-m-d', strtotime($data['connection_date'])) : null;
        $stmt->bindParam(':connection_date', $connection_date);

        try {
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Client updated successfully!'];
            } else {
                error_log("Client update failed. Data: " . print_r($data, true));
                error_log("Client update error info: " . print_r($stmt->errorInfo(), true));
                return ['success' => false, 'message' => 'Failed to update client. ' . implode(":", $stmt->errorInfo())];
            }
        } catch (\PDOException $e) {
            error_log("Client update exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update client: ' . $e->getMessage()];
        }
    }
}
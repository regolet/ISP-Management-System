<?php
namespace App\Controllers;

require_once dirname(__DIR__) . '/Models/Client.php';

class ClientController {
    private $db;
    private $client;

    public function __construct($db) {
        $this->db = $db;
        $this->client = new \App\Models\Client($db);
    }

    /**
     * Get all clients with pagination and filtering
     */
    public function getClients($params = []) {
        return $this->client->getClients($params);
    }

    /**
     * Get a single client by ID
     */
    public function getClient($id) {
        return $this->client->getClient($id);
    }

    /**
     * Create a new client
     */
    public function createClient($data) {
        // Validate required fields
        if (empty($data['first_name'])) {
            return [
                'success' => false,
                'message' => 'First name is required'
            ];
        }

        if (empty($data['last_name'])) {
            return [
                'success' => false,
                'message' => 'Last name is required'
            ];
        }

        // Create client
        return $this->client->createClient($data);
    }

    /**
     * Update an existing client
     */
    public function updateClient($id, $data) {
        // Validate required fields
        if (empty($data['first_name'])) {
            return [
                'success' => false,
                'message' => 'First name is required'
            ];
        }

        if (empty($data['last_name'])) {
            return [
                'success' => false,
                'message' => 'Last name is required'
            ];
        }

        // Update client
        return $this->client->updateClient($id, $data);
    }

    /**
     * Delete a client
     */
    public function deleteClient($id) {
        return $this->client->deleteClient($id);
    }

    /**
     * Get client statistics
     */
    public function getClientStats() {
        return $this->client->getClientStats();
    }

    /**
     * Export clients to CSV
     */
    public function exportClientsToCSV() {
        // Get all clients without pagination
        $result = $this->client->getClients(['per_page' => 1000]);
        $clients = $result['clients'];
        
        if (empty($clients)) {
            return [
                'success' => false,
                'message' => 'No clients to export'
            ];
        }
        
        // Create CSV content
        $output = fopen('php://temp', 'w');
        
        // Add headers
        fputcsv($output, [
            'ID',
            'Client Number',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Address',
            'City',
            'State',
            'Postal Code',
            'Status',
            'Connection Date',
            'Created At'
        ]);
        
        // Add data
        foreach ($clients as $client) {
            fputcsv($output, [
                $client['id'],
                $client['client_number'],
                $client['first_name'],
                $client['last_name'],
                $client['email'],
                $client['phone'],
                $client['address'],
                $client['city'],
                $client['state'],
                $client['postal_code'],
                $client['status'],
                $client['connection_date'],
                $client['created_at']
            ]);
        }
        
        // Get the content
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return [
            'success' => true,
            'data' => $csv,
            'filename' => 'clients_export_' . date('Y-m-d') . '.csv',
            'mime' => 'text/csv'
        ];
    }
}
<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class SettingsController {

    public function updateCompanyProfile($data) {
        // Get database connection
        $db = Database::getInstance()->getConnection();

        // Prepare the query
        $query = "UPDATE company_info SET 
            company_name = :company_name,
            address = :company_address,
            phone_number = :company_phone,
            email_address = :company_email
        WHERE id = 1"; // Assuming there is only one company profile

        $stmt = $db->prepare($query);

        // Bind the parameters
        $stmt->bindParam(':company_name', $data['companyName']);
        $stmt->bindParam(':company_address', $data['companyAddress']);
        $stmt->bindParam(':company_phone', $data['companyPhone']);
        $stmt->bindParam(':company_email', $data['companyEmail']);

        // Execute the query
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function getCompanyProfile() {
        // Get database connection
        $db = Database::getInstance()->getConnection();

        // Prepare the query
        $query = "SELECT * FROM company_info WHERE id = 1"; // Assuming there is only one company profile

        $stmt = $db->prepare($query);

        // Execute the query
        $stmt->execute();

        // Fetch the data
        $companyProfile = $stmt->fetch(PDO::FETCH_ASSOC);

        return $companyProfile;
    }
}
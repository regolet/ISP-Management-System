<?php
namespace App\Controllers;

use App\Core\Controller;

class CustomerController extends Controller {
    public function __construct() {
        parent::__construct();
    }

    /**
     * Show customer dashboard
     */
    public function dashboard() {
        // User is already authenticated and role checked by middleware
        $userId = $_SESSION['user_id'];
        
        // For now, just render a basic dashboard view
        return $this->view('customer/dashboard', [
            'userId' => $userId,
            'username' => $_SESSION['username'],
            'layout' => 'customer'
        ]);
    }
}

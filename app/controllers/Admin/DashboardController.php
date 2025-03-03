<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class DashboardController extends Controller 
{
    public function __construct() 
    {
        parent::__construct();
    }

    /**
     * Show admin dashboard
     */
    public function index() 
    {
        // Check if user is logged in and is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            return $this->redirect('/login');
        }

        return $this->view('admin/dashboard/index', [
            'title' => 'Admin Dashboard',
            'username' => $_SESSION['username']
        ]);
    }
}

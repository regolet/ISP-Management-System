<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Network\OLT;
use App\Models\Network\ONU;
use App\Models\Network\NAP;
use App\Models\Network\LCP;

class NetworkController extends Controller {
    private $oltModel;
    private $onuModel;
    private $napModel;
    private $lcpModel;

    public function __construct() {
        parent::__construct();
        $this->oltModel = new OLT();
        $this->onuModel = new ONU();
        $this->napModel = new NAP();
        $this->lcpModel = new LCP();
    }

    // Network Dashboard
    public function index() {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $olts = $this->oltModel->all();
        $oltStats = [];
        $totalONUs = 0;
        $activeONUs = 0;

        foreach ($olts as $olt) {
            $stats = $this->oltModel->getUtilizationStats();
            $oltStats[$olt['id']] = $stats;
            $totalONUs += $stats['total_onus'];
            $activeONUs += $stats['active_onus'];
        }

        return $this->view('admin/network/dashboard', [
            'olts' => $olts,
            'oltStats' => $oltStats,
            'totalONUs' => $totalONUs,
            'activeONUs' => $activeONUs,
            'layout' => 'navbar',
            'title' => 'Network Dashboard'
        ]);
    }

    // OLT Management
    public function oltList() {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $olts = $this->oltModel->all();
        foreach ($olts as &$olt) {
            $olt['stats'] = $this->oltModel->getUtilizationStats();
        }

        return $this->view('admin/network/olt/index', [
            'olts' => $olts,
            'layout' => 'navbar',
            'title' => 'OLT Management'
        ]);
    }

    public function oltCreate() {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        if ($this->isPost()) {
            $data = $this->getPost();
            $errors = $this->oltModel->validate($data);

            if (empty($errors)) {
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');

                if ($this->oltModel->create($data)) {
                    $_SESSION['success'] = 'OLT created successfully';
                    return $this->redirect('/admin/network/olts');
                }
                
                $errors['general'] = 'Failed to create OLT';
            }

            return $this->view('admin/network/olt/create', [
                'errors' => $errors,
                'data' => $data,
                'layout' => 'navbar',
                'title' => 'Add OLT'
            ]);
        }

        return $this->view('admin/network/olt/create', [
            'layout' => 'navbar',
            'title' => 'Add OLT'
        ]);
    }

    public function oltShow($id) {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $olt = $this->oltModel->find($id);
        if (!$olt) {
            $_SESSION['error'] = 'OLT not found';
            return $this->redirect('/admin/network/olts');
        }

        $ponPorts = $this->oltModel->getPonPorts();
        $connectedONUs = $this->oltModel->getConnectedONUs();
        $stats = $this->oltModel->getUtilizationStats();

        return $this->view('admin/network/olt/show', [
            'olt' => $olt,
            'ponPorts' => $ponPorts,
            'connectedONUs' => $connectedONUs,
            'stats' => $stats,
            'layout' => 'navbar',
            'title' => 'OLT Details'
        ]);
    }

    // ONU Management
    public function onuList() {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $onus = $this->onuModel->all();
        foreach ($onus as &$onu) {
            $onu['details'] = $this->onuModel->getDetails();
            $onu['signal_quality'] = $this->onuModel->checkSignalQuality();
        }

        return $this->view('admin/network/onu/index', [
            'onus' => $onus,
            'layout' => 'navbar',
            'title' => 'ONU Management'
        ]);
    }

    public function onuShow($id) {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $onu = $this->onuModel->find($id);
        if (!$onu) {
            $_SESSION['error'] = 'ONU not found';
            return $this->redirect('/admin/network/onus');
        }

        $details = $this->onuModel->getDetails();
        $signalHistory = $this->onuModel->getSignalHistory();
        $configHistory = $this->onuModel->getConfigHistory();
        $signalQuality = $this->onuModel->checkSignalQuality();

        return $this->view('admin/network/onu/show', [
            'onu' => $onu,
            'details' => $details,
            'signalHistory' => $signalHistory,
            'configHistory' => $configHistory,
            'signalQuality' => $signalQuality,
            'layout' => 'navbar',
            'title' => 'ONU Details'
        ]);
    }

    // NAP Management
    public function napList() {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $naps = $this->napModel->all();
        foreach ($naps as &$nap) {
            $nap['details'] = $this->napModel->getDetails();
            $nap['utilization'] = $this->napModel->getPortUtilization();
        }

        return $this->view('admin/network/nap/index', [
            'naps' => $naps,
            'layout' => 'navbar',
            'title' => 'NAP Management'
        ]);
    }

    public function napCreate() {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        if ($this->isPost()) {
            $data = $this->getPost();
            $errors = $this->napModel->validate($data);

            if (empty($errors)) {
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');

                if ($this->napModel->create($data)) {
                    // Create ports based on total_ports
                    for ($i = 1; $i <= $data['total_ports']; $i++) {
                        $this->napModel->addPort($i);
                    }

                    $_SESSION['success'] = 'NAP created successfully';
                    return $this->redirect('/admin/network/naps');
                }
                
                $errors['general'] = 'Failed to create NAP';
            }

            return $this->view('admin/network/nap/create', [
                'errors' => $errors,
                'data' => $data,
                'layout' => 'navbar',
                'title' => 'Add NAP'
            ]);
        }

        // Get LCPs for dropdown
        $lcps = $this->lcpModel->all();

        return $this->view('admin/network/nap/create', [
            'lcps' => $lcps,
            'layout' => 'navbar',
            'title' => 'Add NAP'
        ]);
    }

    // LCP Management
    public function lcpList() {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $lcps = $this->lcpModel->all();
        foreach ($lcps as &$lcp) {
            $lcp['details'] = $this->lcpModel->getDetails();
            $lcp['utilization'] = $this->lcpModel->getCapacityUtilization();
        }

        return $this->view('admin/network/lcp/index', [
            'lcps' => $lcps,
            'layout' => 'navbar',
            'title' => 'LCP Management'
        ]);
    }

    public function lcpShow($id) {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $lcp = $this->lcpModel->find($id);
        if (!$lcp) {
            $_SESSION['error'] = 'LCP not found';
            return $this->redirect('/admin/network/lcps');
        }

        $details = $this->lcpModel->getDetails();
        $connectedNAPs = $this->lcpModel->getConnectedNAPs();
        $splitters = $this->lcpModel->getSplitterConfiguration();
        $maintenanceHistory = $this->lcpModel->getMaintenanceHistory();
        $utilization = $this->lcpModel->getCapacityUtilization();

        return $this->view('admin/network/lcp/show', [
            'lcp' => $lcp,
            'details' => $details,
            'connectedNAPs' => $connectedNAPs,
            'splitters' => $splitters,
            'maintenanceHistory' => $maintenanceHistory,
            'utilization' => $utilization,
            'layout' => 'navbar',
            'title' => 'LCP Details'
        ]);
    }

    // Network Map
    public function map() {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $olts = $this->oltModel->all();
        $lcps = $this->lcpModel->all();
        $naps = $this->napModel->all();

        return $this->view('admin/network/map', [
            'olts' => $olts,
            'lcps' => $lcps,
            'naps' => $naps,
            'layout' => 'navbar',
            'title' => 'Network Map'
        ]);
    }

    // Network Health Check
    public function healthCheck() {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $issues = [];
        
        // Check OLTs
        $olts = $this->oltModel->all();
        foreach ($olts as $olt) {
            if (!$this->oltModel->checkConnectivity()) {
                $issues[] = [
                    'type' => 'olt',
                    'device' => $olt['name'],
                    'issue' => 'Connectivity failed',
                    'severity' => 'high'
                ];
            }
        }

        // Check ONUs
        $onus = $this->onuModel->all();
        foreach ($onus as $onu) {
            $signalQuality = $this->onuModel->checkSignalQuality();
            if ($signalQuality['status'] !== 'good') {
                $issues[] = [
                    'type' => 'onu',
                    'device' => $onu['serial_number'],
                    'issue' => implode(', ', $signalQuality['issues']),
                    'severity' => 'medium'
                ];
            }
        }

        return $this->view('admin/network/health', [
            'issues' => $issues,
            'layout' => 'navbar',
            'title' => 'Network Health Check'
        ]);
    }
}

<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\NetworkService;
use App\Services\OLTService;
use App\Services\ONUService;
use App\Services\NAPService;
use App\Services\LCPService;
use App\Middleware\AuthMiddleware;

class NetworkController extends Controller 
{
    private $networkService;
    private $oltService;
    private $onuService;
    private $napService;
    private $lcpService;

    public function __construct(
        NetworkService $networkService,
        OLTService $oltService,
        ONUService $onuService,
        NAPService $napService,
        LCPService $lcpService
    ) {
        parent::__construct();
        
        // Register authentication middleware
        $this->registerMiddleware(new AuthMiddleware());
        
        $this->networkService = $networkService;
        $this->oltService = $oltService;
        $this->onuService = $onuService;
        $this->napService = $napService;
        $this->lcpService = $lcpService;
    }

    /**
     * Network Dashboard
     */
    public function index() 
    {
        try {
            $dashboardData = $this->networkService->getDashboardData();
            
            return $this->view('admin/network/dashboard', array_merge(
                $dashboardData,
                [
                    'layout' => 'navbar',
                    'title' => 'Network Dashboard'
                ]
            ));
        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to load dashboard: ' . $e->getMessage());
            return $this->redirect('/admin');
        }
    }

    /**
     * OLT Management
     */
    public function oltList() 
    {
        try {
            $olts = $this->oltService->getDeviceList();
            
            return $this->view('admin/network/olt/index', [
                'olts' => $olts,
                'layout' => 'navbar',
                'title' => 'OLT Management'
            ]);
        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to load OLT list: ' . $e->getMessage());
            return $this->redirect('/admin/network');
        }
    }

    public function oltCreate() 
    {
        if ($this->isPost()) {
            try {
                $data = $this->getPost();
                $this->oltService->createDevice($data);
                
                $this->setFlash('success', 'OLT created successfully');
                return $this->redirect('/admin/network/olts');
            } catch (\Exception $e) {
                return $this->view('admin/network/olt/create', [
                    'errors' => json_decode($e->getMessage(), true),
                    'data' => $this->getPost(),
                    'layout' => 'navbar',
                    'title' => 'Add OLT'
                ]);
            }
        }

        return $this->view('admin/network/olt/create', [
            'layout' => 'navbar',
            'title' => 'Add OLT'
        ]);
    }

    public function oltShow($id) 
    {
        try {
            $deviceDetails = $this->oltService->getDeviceDetails($id);
            
            return $this->view('admin/network/olt/show', array_merge(
                $deviceDetails,
                [
                    'layout' => 'navbar',
                    'title' => 'OLT Details'
                ]
            ));
        } catch (\Exception $e) {
            $this->setFlash('error', $e->getMessage());
            return $this->redirect('/admin/network/olts');
        }
    }

    /**
     * ONU Management
     */
    public function onuList() 
    {
        try {
            $onus = $this->onuService->getDeviceList();
            
            return $this->view('admin/network/onu/index', [
                'onus' => $onus,
                'layout' => 'navbar',
                'title' => 'ONU Management'
            ]);
        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to load ONU list: ' . $e->getMessage());
            return $this->redirect('/admin/network');
        }
    }

    public function onuShow($id) 
    {
        try {
            $deviceDetails = $this->onuService->getDeviceDetails($id);
            
            return $this->view('admin/network/onu/show', array_merge(
                $deviceDetails,
                [
                    'layout' => 'navbar',
                    'title' => 'ONU Details'
                ]
            ));
        } catch (\Exception $e) {
            $this->setFlash('error', $e->getMessage());
            return $this->redirect('/admin/network/onus');
        }
    }

    /**
     * NAP Management
     */
    public function napList() 
    {
        try {
            $naps = $this->napService->getDeviceList();
            
            return $this->view('admin/network/nap/index', [
                'naps' => $naps,
                'layout' => 'navbar',
                'title' => 'NAP Management'
            ]);
        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to load NAP list: ' . $e->getMessage());
            return $this->redirect('/admin/network');
        }
    }

    public function napCreate() 
    {
        if ($this->isPost()) {
            try {
                $data = $this->getPost();
                $this->napService->createDevice($data);
                
                $this->setFlash('success', 'NAP created successfully');
                return $this->redirect('/admin/network/naps');
            } catch (\Exception $e) {
                return $this->view('admin/network/nap/create', [
                    'errors' => json_decode($e->getMessage(), true),
                    'data' => $this->getPost(),
                    'lcps' => $this->lcpService->getDeviceList(),
                    'layout' => 'navbar',
                    'title' => 'Add NAP'
                ]);
            }
        }

        return $this->view('admin/network/nap/create', [
            'lcps' => $this->lcpService->getDeviceList(),
            'layout' => 'navbar',
            'title' => 'Add NAP'
        ]);
    }

    /**
     * LCP Management
     */
    public function lcpList() 
    {
        try {
            $lcps = $this->lcpService->getDeviceList();
            
            return $this->view('admin/network/lcp/index', [
                'lcps' => $lcps,
                'layout' => 'navbar',
                'title' => 'LCP Management'
            ]);
        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to load LCP list: ' . $e->getMessage());
            return $this->redirect('/admin/network');
        }
    }

    public function lcpShow($id) 
    {
        try {
            $deviceDetails = $this->lcpService->getDeviceDetails($id);
            
            return $this->view('admin/network/lcp/show', array_merge(
                $deviceDetails,
                [
                    'layout' => 'navbar',
                    'title' => 'LCP Details'
                ]
            ));
        } catch (\Exception $e) {
            $this->setFlash('error', $e->getMessage());
            return $this->redirect('/admin/network/lcps');
        }
    }

    /**
     * Network Map
     */
    public function map() 
    {
        try {
            $mapData = $this->networkService->getNetworkMapData();
            
            return $this->view('admin/network/map', array_merge(
                $mapData,
                [
                    'layout' => 'navbar',
                    'title' => 'Network Map'
                ]
            ));
        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to load network map: ' . $e->getMessage());
            return $this->redirect('/admin/network');
        }
    }

    /**
     * Network Health Check
     */
    public function healthCheck() 
    {
        try {
            $issues = $this->networkService->performHealthCheck();
            
            return $this->view('admin/network/health', [
                'issues' => $issues,
                'layout' => 'navbar',
                'title' => 'Network Health Check'
            ]);
        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to perform health check: ' . $e->getMessage());
            return $this->redirect('/admin/network');
        }
    }
}

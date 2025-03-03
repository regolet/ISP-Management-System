<?php
namespace App\Services;

use App\Services\OLTService;
use App\Services\ONUService;
use App\Services\NAPService;
use App\Services\LCPService;

class NetworkService 
{
    private $oltService;
    private $onuService;
    private $napService;
    private $lcpService;

    public function __construct(
        OLTService $oltService,
        ONUService $onuService,
        NAPService $napService,
        LCPService $lcpService
    ) {
        $this->oltService = $oltService;
        $this->onuService = $onuService;
        $this->napService = $napService;
        $this->lcpService = $lcpService;
    }

    /**
     * Get network dashboard data
     */
    public function getDashboardData() 
    {
        $olts = $this->oltService->getDeviceList();
        $oltStats = [];
        $totalONUs = 0;
        $activeONUs = 0;

        foreach ($olts as $olt) {
            $stats = $this->oltService->getUtilizationStats($olt['id']);
            $oltStats[$olt['id']] = $stats;
            $totalONUs += $stats['total_onus'];
            $activeONUs += $stats['active_onus'];
        }

        return [
            'olts' => $olts,
            'oltStats' => $oltStats,
            'totalONUs' => $totalONUs,
            'activeONUs' => $activeONUs
        ];
    }

    /**
     * Get network map data
     */
    public function getNetworkMapData() 
    {
        return [
            'olts' => $this->oltService->getDeviceList(),
            'lcps' => $this->lcpService->getDeviceList(),
            'naps' => $this->napService->getDeviceList()
        ];
    }

    /**
     * Perform network health check
     */
    public function performHealthCheck() 
    {
        $issues = [];

        // Check OLTs
        $olts = $this->oltService->getDeviceList();
        foreach ($olts as $olt) {
            try {
                if (!$this->oltService->checkConnectivity($olt['id'])) {
                    $issues[] = [
                        'type' => 'olt',
                        'device' => $olt['name'],
                        'issue' => 'Connectivity failed',
                        'severity' => 'high'
                    ];
                }
            } catch (\Exception $e) {
                $issues[] = [
                    'type' => 'olt',
                    'device' => $olt['name'],
                    'issue' => $e->getMessage(),
                    'severity' => 'high'
                ];
            }
        }

        // Check ONUs
        $onus = $this->onuService->getDeviceList();
        foreach ($onus as $onu) {
            try {
                $signalQuality = $this->onuService->checkSignalQuality($onu['id']);
                if ($signalQuality['status'] !== 'good') {
                    $issues[] = [
                        'type' => 'onu',
                        'device' => $onu['serial_number'],
                        'issue' => implode(', ', $signalQuality['issues']),
                        'severity' => 'medium'
                    ];
                }
            } catch (\Exception $e) {
                $issues[] = [
                    'type' => 'onu',
                    'device' => $onu['serial_number'],
                    'issue' => $e->getMessage(),
                    'severity' => 'medium'
                ];
            }
        }

        // Check NAPs
        $naps = $this->napService->getDeviceList();
        foreach ($naps as $nap) {
            try {
                $utilization = $this->napService->getPortUtilization($nap['id']);
                if ($utilization['percentage'] > 90) {
                    $issues[] = [
                        'type' => 'nap',
                        'device' => $nap['name'],
                        'issue' => 'High port utilization (' . $utilization['percentage'] . '%)',
                        'severity' => 'low'
                    ];
                }
            } catch (\Exception $e) {
                $issues[] = [
                    'type' => 'nap',
                    'device' => $nap['name'],
                    'issue' => $e->getMessage(),
                    'severity' => 'low'
                ];
            }
        }

        return $issues;
    }

    /**
     * Get device connections
     */
    public function getDeviceConnections($type, $id) 
    {
        switch ($type) {
            case 'olt':
                return $this->oltService->getConnectedONUs($id);
            case 'lcp':
                return $this->lcpService->getConnectedNAPs($id);
            case 'nap':
                return $this->napService->getConnectedONUs($id);
            default:
                throw new \Exception('Invalid device type');
        }
    }

    /**
     * Get device utilization
     */
    public function getDeviceUtilization($type, $id) 
    {
        switch ($type) {
            case 'olt':
                return $this->oltService->getUtilizationStats($id);
            case 'lcp':
                return $this->lcpService->getCapacityUtilization($id);
            case 'nap':
                return $this->napService->getPortUtilization($id);
            default:
                throw new \Exception('Invalid device type');
        }
    }

    /**
     * Validate network topology
     */
    public function validateTopology() 
    {
        $issues = [];

        // Check LCP connections
        $lcps = $this->lcpService->getDeviceList();
        foreach ($lcps as $lcp) {
            $connectedNAPs = $this->lcpService->getConnectedNAPs($lcp['id']);
            if (count($connectedNAPs) > $lcp['max_naps']) {
                $issues[] = "LCP {$lcp['name']} has too many NAP connections";
            }
        }

        // Check NAP connections
        $naps = $this->napService->getDeviceList();
        foreach ($naps as $nap) {
            $connectedONUs = $this->napService->getConnectedONUs($nap['id']);
            if (count($connectedONUs) > $nap['total_ports']) {
                $issues[] = "NAP {$nap['name']} has too many ONU connections";
            }
        }

        return $issues;
    }
}

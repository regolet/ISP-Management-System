<?php
namespace App\Services;

use App\Interfaces\Services\INetworkService;
use App\Models\Network\OLT;

class OLTService implements INetworkService 
{
    private OLT $oltModel;

    public function __construct(OLT $oltModel) 
    {
        $this->oltModel = $oltModel;
    }

    public function getDeviceList() 
    {
        $olts = $this->oltModel->all();
        foreach ($olts as &$olt) {
            $olt['stats'] = $this->getUtilizationStats($olt['id']);
        }
        return $olts;
    }

    public function getDeviceDetails($id) 
    {
        $olt = $this->oltModel->find($id);
        if (!$olt) {
            throw new \Exception('OLT not found');
        }

        return [
            'device' => $olt,
            'ponPorts' => $this->getPonPorts($id),
            'connectedONUs' => $this->getConnectedONUs($id),
            'stats' => $this->getUtilizationStats($id)
        ];
    }

    public function createDevice(array $data) 
    {
        $errors = $this->validateData($data);
        if (!empty($errors)) {
            throw new \Exception(json_encode($errors));
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->oltModel->create($data);
    }

    public function updateDevice($id, array $data) 
    {
        $olt = $this->oltModel->find($id);
        if (!$olt) {
            throw new \Exception('OLT not found');
        }

        $errors = $this->validateData($data, $id);
        if (!empty($errors)) {
            throw new \Exception(json_encode($errors));
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->oltModel->update($id, $data);
    }

    public function deleteDevice($id) 
    {
        $olt = $this->oltModel->find($id);
        if (!$olt) {
            throw new \Exception('OLT not found');
        }

        // Check if OLT has connected ONUs
        $connectedONUs = $this->getConnectedONUs($id);
        if (!empty($connectedONUs)) {
            throw new \Exception('Cannot delete OLT with connected ONUs');
        }

        return $this->oltModel->delete($id);
    }

    public function validateData(array $data, $id = null) 
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'OLT name is required';
        }

        if (empty($data['ip_address'])) {
            $errors['ip_address'] = 'IP address is required';
        } elseif (!filter_var($data['ip_address'], FILTER_VALIDATE_IP)) {
            $errors['ip_address'] = 'Invalid IP address format';
        } else {
            // Check if IP is unique (except for current OLT if updating)
            $existingOLT = $this->oltModel->findBy('ip_address', $data['ip_address']);
            if ($existingOLT && (!$id || $existingOLT['id'] != $id)) {
                $errors['ip_address'] = 'IP address already in use';
            }
        }

        if (empty($data['total_pon_ports'])) {
            $errors['total_pon_ports'] = 'Total PON ports is required';
        } elseif (!is_numeric($data['total_pon_ports']) || $data['total_pon_ports'] <= 0) {
            $errors['total_pon_ports'] = 'Invalid number of PON ports';
        }

        if (!empty($data['management_vlan'])) {
            if (!is_numeric($data['management_vlan']) || 
                $data['management_vlan'] < 1 || 
                $data['management_vlan'] > 4094) {
                $errors['management_vlan'] = 'Invalid VLAN ID';
            }
        }

        return $errors;
    }

    public function getUtilizationStats($id) 
    {
        $olt = $this->oltModel->find($id);
        if (!$olt) {
            throw new \Exception('OLT not found');
        }

        return $this->oltModel->getUtilizationStats();
    }

    // Additional OLT-specific methods
    public function getPonPorts($oltId) 
    {
        $olt = $this->oltModel->find($oltId);
        if (!$olt) {
            throw new \Exception('OLT not found');
        }

        return $this->oltModel->getPonPorts();
    }

    public function getConnectedONUs($oltId) 
    {
        $olt = $this->oltModel->find($oltId);
        if (!$olt) {
            throw new \Exception('OLT not found');
        }

        return $this->oltModel->getConnectedONUs();
    }

    public function checkConnectivity($oltId) 
    {
        $olt = $this->oltModel->find($oltId);
        if (!$olt) {
            throw new \Exception('OLT not found');
        }

        return $this->oltModel->checkConnectivity();
    }

    public function addPonPort($oltId, $portNumber, array $data) 
    {
        $olt = $this->oltModel->find($oltId);
        if (!$olt) {
            throw new \Exception('OLT not found');
        }

        return $this->oltModel->addPonPort($portNumber, $data);
    }

    public function updateStatus($oltId, $status, $notes = '') 
    {
        $olt = $this->oltModel->find($oltId);
        if (!$olt) {
            throw new \Exception('OLT not found');
        }

        return $this->oltModel->updateStatus($status, $notes);
    }
}

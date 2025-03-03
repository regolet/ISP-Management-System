<?php
namespace App\Services;

use App\Interfaces\Services\INetworkService;
use App\Models\Network\ONU;

class ONUService implements INetworkService 
{
    private ONU $onuModel;

    public function __construct(ONU $onuModel) 
    {
        $this->onuModel = $onuModel;
    }

    public function getDeviceList() 
    {
        return $this->onuModel->all();
    }

    public function getDeviceDetails($id) 
    {
        $onu = $this->onuModel->find($id);
        if (!$onu) {
            throw new \Exception('ONU not found');
        }

        return $onu;
    }

    public function createDevice(array $data) 
    {
        $errors = $this->validateData($data);
        if (!empty($errors)) {
            throw new \Exception(json_encode($errors));
        }

        return $this->onuModel->create($data);
    }

    public function updateDevice($id, array $data) 
    {
        $onu = $this->onuModel->find($id);
        if (!$onu) {
            throw new \Exception('ONU not found');
        }

        $errors = $this->validateData($data, $id);
        if (!empty($errors)) {
            throw new \Exception(json_encode($errors));
        }

        return $this->onuModel->update($id, $data);
    }

    public function deleteDevice($id) 
    {
        $onu = $this->onuModel->find($id);
        if (!$onu) {
            throw new \Exception('ONU not found');
        }

        return $this->onuModel->delete($id);
    }

    public function validateData(array $data, $id = null) 
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'ONU name is required';
        }

        if (empty($data['serial_number'])) {
            $errors['serial_number'] = 'Serial number is required';
        }

        return $errors;
    }

    public function getUtilizationStats($id) 
    {
        $onu = $this->onuModel->find($id);
        if (!$onu) {
            throw new \Exception('ONU not found');
        }

        return $this->onuModel->getUtilizationStats();
    }
}

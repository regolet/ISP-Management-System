<?php
namespace App\Interfaces\Services;

interface INetworkService 
{
    public function getDeviceList();
    public function getDeviceDetails($id);
    public function createDevice(array $data);
    public function updateDevice($id, array $data);
    public function deleteDevice($id);
    public function validateData(array $data);
    public function getUtilizationStats($id);
}

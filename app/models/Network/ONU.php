<?php
namespace App\Models\Network;

use App\Core\Model;

class ONU extends Model 
{
    protected $table = 'onus'; // Assuming the table name is 'onus'
    protected $fillable = ['name', 'serial_number', 'olt_id', 'status', 'ip_address', 'created_at', 'updated_at'];

    public function getUtilizationStats() 
    {
        // Logic to get utilization stats for the ONU
        return [
            'status' => 'good',
            'utilization' => 75 // Example value
        ];
    }

    public function getConnectedONUs() 
    {
        // Logic to get connected ONUs
        return [];
    }
}

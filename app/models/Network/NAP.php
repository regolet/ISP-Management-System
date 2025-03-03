<?php
namespace App\Models\Network;

use App\Core\Model;

class NAP extends Model 
{
    protected $table = 'naps'; // Assuming the table name is 'naps'
    protected $fillable = ['name', 'location', 'olt_id', 'status', 'created_at', 'updated_at'];

    public function getUtilizationStats() 
    {
        // Logic to get utilization stats for the NAP
        return [
            'status' => 'good',
            'utilization' => 65 // Example value
        ];
    }

    public function getConnectedONUs() 
    {
        // Logic to get connected ONUs
        return [];
    }
}

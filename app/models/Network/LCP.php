<?php
namespace App\Models\Network;

use App\Core\Model;

class LCP extends Model 
{
    protected $table = 'lcps'; // Assuming the table name is 'lcps'
    protected $fillable = ['name', 'location', 'olt_id', 'status', 'created_at', 'updated_at'];

    public function getUtilizationStats() 
    {
        // Logic to get utilization stats for the LCP
        return [
            'status' => 'good',
            'utilization' => 80 // Example value
        ];
    }

    public function getConnectedNAPs() 
    {
        // Logic to get connected NAPs
        return [];
    }
}

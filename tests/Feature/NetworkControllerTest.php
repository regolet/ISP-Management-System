<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin\Network\OLT;
use App\Models\Admin\Network\ONU;
use App\Services\NetworkService;
use App\Core\Request;
use App\Core\Response;

class NetworkControllerTest extends TestCase
{
    protected NetworkService $networkService;
    protected Request $request;
    protected Response $response;

    protected function setUp(): void
    {
        parent::setUp();

        // Get instances from container
        $this->networkService = $this->app->make(NetworkService::class);
        $this->request = $this->app->make(Request::class);
        $this->response = $this->app->make(Response::class);

        // Mock network service methods if needed
        // $this->networkService = $this->mock(NetworkService::class, [
        //     'getOLTDevices' => [/* mock data */],
        // ]);
        // $this->container->singleton(NetworkService::class, fn() => $this->networkService);
    }

    public function testIndexPageLoads()
    {
        // Simulate a GET request to /admin/network
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/admin/network';

        // Get response from router
        $response = $this->app->getRouter()->resolve('/admin/network', 'GET');

        // Assert response contains expected content
        $this->assertStringContainsString('Network Management', $response);
    }

    public function testOLTDevicesCanBeListed()
    {
        // Create test OLT devices
        $olt1 = new OLT();
        $olt1->name = 'Test OLT 1';
        $olt1->ip_address = '192.168.1.1';
        $olt1->save();

        $olt2 = new OLT();
        $olt2->name = 'Test OLT 2';
        $olt2->ip_address = '192.168.1.2';
        $olt2->save();

        // Simulate GET request to /admin/network/olt
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/admin/network/olt';

        // Get response
        $response = $this->app->getRouter()->resolve('/admin/network/olt', 'GET');

        // Assert response contains OLT information
        $this->assertStringContainsString('Test OLT 1', $response);
        $this->assertStringContainsString('192.168.1.1', $response);
        $this->assertStringContainsString('Test OLT 2', $response);
        $this->assertStringContainsString('192.168.1.2', $response);
    }

    public function testONUCanBeAssignedToOLT()
    {
        // Create test OLT
        $olt = new OLT();
        $olt->name = 'Test OLT';
        $olt->ip_address = '192.168.1.1';
        $olt->save();

        // Create test ONU
        $onu = new ONU();
        $onu->serial_number = 'ABCD1234';
        $onu->customer_id = 1;
        $onu->save();

        // Simulate POST request to assign ONU to OLT
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/admin/network/olt/' . $olt->id . '/assign-onu';
        $_POST = [
            'onu_id' => $onu->id,
            'port' => 1,
            'slot' => 1
        ];

        // Get response
        $response = $this->app->getRouter()->resolve(
            '/admin/network/olt/' . $olt->id . '/assign-onu',
            'POST'
        );

        // Assert ONU was assigned correctly
        $this->assertDatabaseHas('onu_assignments', [
            'olt_id' => $olt->id,
            'onu_id' => $onu->id,
            'port' => 1,
            'slot' => 1
        ]);

        // Assert response indicates success
        $this->assertStringContainsString('ONU assigned successfully', $response);
    }

    public function testInvalidOLTAssignmentFails()
    {
        // Simulate POST request with invalid OLT ID
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/admin/network/olt/999/assign-onu';
        $_POST = [
            'onu_id' => 1,
            'port' => 1,
            'slot' => 1
        ];

        // Get response
        $response = $this->app->getRouter()->resolve('/admin/network/olt/999/assign-onu', 'POST');

        // Assert response indicates failure
        $this->assertStringContainsString('OLT not found', $response);
    }

    protected function tearDown(): void
    {
        // Clean up test data
        OLT::query()->delete();
        ONU::query()->delete();

        parent::tearDown();
    }
}

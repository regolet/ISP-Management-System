<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Admin\Asset\Asset;
use App\Models\Admin\Asset\AssetCollection;
use App\Models\Admin\Asset\AssetExpense;

class AssetController extends Controller 
{
    private $assetModel;
    private $collectionModel;
    private $expenseModel;

    public function __construct() 
    {
        parent::__construct();
        $this->assetModel = new Asset();
        $this->collectionModel = new AssetCollection();
        $this->expenseModel = new AssetExpense();
    }

    /**
     * Display asset listing page with statistics
     */
    public function index() 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        // Get current month range
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');

        // Get statistics
        $stats = $this->assetModel->getStatistics();
        $collections = $this->assetModel->getCollectionStatistics($startDate, $endDate);
        $uncollected = $this->assetModel->getUncollectedStatistics($startDate, $endDate);

        // Get search parameters
        $search = $this->getQuery('search');
        $status = $this->getQuery('status');
        $page = (int)($this->getQuery('page', 1));
        $perPage = 10;

        // Search assets with filters
        $result = $this->assetModel->searchAssets(
            ['search' => $search, 'status' => $status],
            $page,
            $perPage
        );

        return $this->view('admin/assets/index', [
            'assets' => $result['assets'],
            'total_pages' => $result['pages'],
            'current_page' => $result['current_page'],
            'stats' => $stats,
            'collections' => $collections,
            'uncollected' => $uncollected,
            'search' => $search,
            'status' => $status,
            'layout' => 'navbar',
            'title' => 'Asset Management'
        ]);
    }

    /**
     * Update asset status
     */
    public function updateStatus($id) 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $status = $this->getQuery('status');
        if (!in_array($status, ['active', 'inactive'])) {
            $_SESSION['error'] = 'Invalid status';
            return $this->redirect('/admin/assets');
        }

        $asset = $this->assetModel->find($id);
        if (!$asset) {
            $_SESSION['error'] = 'Asset not found';
            return $this->redirect('/admin/assets');
        }

        if ($this->assetModel->updateStatus($status)) {
            $_SESSION['success'] = 'Asset status updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update asset status';
        }

        return $this->redirect('/admin/assets');
    }

    // ... (keep existing methods from the previous version)

    /**
     * Generate asset report
     */
    public function report() 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $startDate = $this->getQuery('start_date') ?? date('Y-m-01');
        $endDate = $this->getQuery('end_date') ?? date('Y-m-t');

        $assets = $this->assetModel->all();
        $totalValue = 0;
        $totalDepreciation = 0;
        $expenseStats = [];

        foreach ($assets as &$asset) {
            $depreciation = $this->assetModel->calculateDepreciation();
            $asset['current_value'] = $depreciation['current_value'];
            $asset['total_depreciation'] = $depreciation['total_depreciation'];
            
            $totalValue += $asset['current_value'];
            $totalDepreciation += $depreciation['total_depreciation'];

            $expenses = $this->expenseModel->getExpensesByAsset(
                $asset['id'],
                $startDate,
                $endDate
            );
            $asset['expenses'] = $expenses;
        }

        $expenseStats = $this->expenseModel->getStatistics(null, $startDate, $endDate);

        return $this->view('admin/assets/report', [
            'assets' => $assets,
            'totalValue' => $totalValue,
            'totalDepreciation' => $totalDepreciation,
            'expenseStats' => $expenseStats,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'layout' => 'navbar',
            'title' => 'Asset Report'
        ]);
    }
}

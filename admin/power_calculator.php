<?php
require_once '../config.php';
check_auth();

$page_title = 'Power Budget Calculator';
$_SESSION['active_menu'] = 'pon_management';
include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Power Budget Calculator</h1>
        <a href="FTTH.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to FTTH Management
        </a>
    </div>

    <div class="row">
        <!-- Calculator Form -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Calculate Power Budget</h5>
                </div>
                <div class="card-body">
                    <form id="powerCalculatorForm">
                        <div class="mb-3">
                            <label class="form-label">OLT TX Power (dBm)</label>
                            <input type="number" class="form-control" id="txPower" value="3.0" step="0.1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Total Distance (meters)</label>
                            <input type="number" class="form-control" id="distance" value="1000" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Wavelength</label>
                            <select class="form-select" id="wavelength">
                                <option value="1310nm">1310nm</option>
                                <option value="1490nm">1490nm</option>
                                <option value="1550nm">1550nm</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Connector Type</label>
                            <select class="form-select" id="connectorType">
                                <option value="LCP">LCP (0.3 dB/conn)</option>
                                <option value="PLC">PLC (0.2 dB/conn)</option>
                                <option value="FBT">FBT (0.4 dB/conn)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Number of Connectors</label>
                            <input type="number" class="form-control" id="connectorCount" value="2" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Splitter Ratio</label>
                            <select class="form-select" id="splitterRatio">
                                <option value="1:2">1:2 (3.6 dB)</option>
                                <option value="1:4">1:4 (7.0 dB)</option>
                                <option value="1:8">1:8 (10.5 dB)</option>
                                <option value="1:16">1:16 (13.5 dB)</option>
                                <option value="1:32" selected>1:32 (17.0 dB)</option>
                                <option value="1:64">1:64 (21.0 dB)</option>
                                <option value="1:128">1:128 (25.0 dB)</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Calculate</button>
                        <button type="button" class="btn btn-secondary" onclick="calculateMaxDistance()">Find Max Distance</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Results Panel -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Results</h5>
                </div>
                <div class="card-body">
                    <div id="resultsPanel" style="display: none;">
                        <h6 class="border-bottom pb-2">Power Budget Analysis</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th>TX Power:</th>
                                        <td id="resultTxPower"></td>
                                    </tr>
                                    <tr>
                                        <th>Fiber Loss:</th>
                                        <td id="resultFiberLoss"></td>
                                    </tr>
                                    <tr>
                                        <th>Connector Loss:</th>
                                        <td id="resultConnectorLoss"></td>
                                    </tr>
                                    <tr>
                                        <th>Splitter Loss:</th>
                                        <td id="resultSplitterLoss"></td>
                                    </tr>
                                    <tr>
                                        <th>Safety Margin:</th>
                                        <td id="resultSafetyMargin"></td>
                                    </tr>
                                    <tr class="table-active">
                                        <th>Total Loss:</th>
                                        <td id="resultTotalLoss"></td>
                                    </tr>
                                    <tr>
                                        <th>Power Budget:</th>
                                        <td id="resultPowerBudget"></td>
                                    </tr>
                                    <tr>
                                        <th>Power Margin:</th>
                                        <td id="resultPowerMargin"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div id="statusAlert" class="alert mt-3" role="alert"></div>
                    </div>

                    <div id="maxDistancePanel" style="display: none;">
                        <h6 class="border-bottom pb-2">Maximum Distance Analysis</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th>Maximum Distance:</th>
                                        <td id="resultMaxDistance"></td>
                                    </tr>
                                    <tr>
                                        <th>Fixed Losses:</th>
                                        <td id="resultFixedLosses"></td>
                                    </tr>
                                    <tr>
                                        <th>Available Loss Budget:</th>
                                        <td id="resultAvailableLoss"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('powerCalculatorForm').addEventListener('submit', function(e) {
    e.preventDefault();
    calculatePowerBudget();
});

function calculatePowerBudget() {
    const params = {
        txPower: parseFloat(document.getElementById('txPower').value),
        distance: parseFloat(document.getElementById('distance').value),
        wavelength: document.getElementById('wavelength').value,
        connectorType: document.getElementById('connectorType').value,
        connectorCount: parseInt(document.getElementById('connectorCount').value),
        splitterRatio: document.getElementById('splitterRatio').value
    };

    const results = window.powerCalculator.calculatePowerBudget(params);
    
    // Update results panel
    document.getElementById('resultTxPower').textContent = `${results.txPower} dBm`;
    document.getElementById('resultFiberLoss').textContent = `${results.fiberLoss} dB`;
    document.getElementById('resultConnectorLoss').textContent = `${results.connectorLoss} dB`;
    document.getElementById('resultSplitterLoss').textContent = `${results.splitterLoss} dB`;
    document.getElementById('resultSafetyMargin').textContent = `${results.safetyMargin} dB`;
    document.getElementById('resultTotalLoss').textContent = `${results.totalLoss} dB`;
    document.getElementById('resultPowerBudget').textContent = `${results.powerBudget} dBm`;
    document.getElementById('resultPowerMargin').textContent = `${results.powerMargin} dB`;

    // Update status alert
    const statusAlert = document.getElementById('statusAlert');
    statusAlert.className = `alert alert-${getAlertClass(results.status.level)}`;
    statusAlert.textContent = results.status.message;

    // Show results panel, hide max distance panel
    document.getElementById('resultsPanel').style.display = 'block';
    document.getElementById('maxDistancePanel').style.display = 'none';
}

function calculateMaxDistance() {
    const params = {
        txPower: parseFloat(document.getElementById('txPower').value),
        wavelength: document.getElementById('wavelength').value,
        connectorType: document.getElementById('connectorType').value,
        connectorCount: parseInt(document.getElementById('connectorCount').value),
        splitterRatio: document.getElementById('splitterRatio').value,
        minPowerMargin: 2
    };

    const results = window.powerCalculator.calculateMaxDistance(params);
    
    // Update max distance panel
    document.getElementById('resultMaxDistance').textContent = `${results.maxDistance} meters`;
    document.getElementById('resultFixedLosses').textContent = `${results.fixedLosses} dB`;
    document.getElementById('resultAvailableLoss').textContent = `${results.availableLoss} dB`;

    // Show max distance panel, hide results panel
    document.getElementById('maxDistancePanel').style.display = 'block';
    document.getElementById('resultsPanel').style.display = 'none';
}

function getAlertClass(level) {
    switch (level) {
        case 'critical': return 'danger';
        case 'warning': return 'warning';
        case 'acceptable': return 'info';
        case 'good': return 'success';
        default: return 'secondary';
    }
}
</script>

<?php include 'footer.php'; ?>

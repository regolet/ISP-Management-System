class PowerCalculator {
    constructor() {
        // Constants for power calculations
        this.CONNECTOR_LOSS = {
            'LCP': 0.3,  // dB loss per connection
            'PLC': 0.2,  // Planar lightwave circuit splitter
            'FBT': 0.4   // Fused biconical taper splitter
        };
        this.FIBER_LOSS_PER_KM = {
            '1310nm': 0.35,  // dB/km for 1310nm
            '1490nm': 0.30,  // dB/km for 1490nm
            '1550nm': 0.25   // dB/km for 1550nm
        };
        this.SPLITTER_LOSS = {
            '1:2': 3.6,
            '1:4': 7.0,
            '1:8': 10.5,
            '1:16': 13.5,
            '1:32': 17.0,
            '1:64': 21.0,
            '1:128': 25.0
        };
        this.SAFETY_MARGIN = 3.0;  // dB safety margin
        this.RECEIVER_SENSITIVITY = -28;  // dBm typical ONT sensitivity
    }

    // Calculate total fiber loss
    calculateFiberLoss(distance, wavelength = '1310nm') {
        return (distance / 1000) * this.FIBER_LOSS_PER_KM[wavelength];
    }

    // Calculate total connector loss
    calculateConnectorLoss(connectorType, count) {
        return this.CONNECTOR_LOSS[connectorType] * count;
    }

    // Calculate total splitter loss
    calculateSplitterLoss(ratio) {
        return this.SPLITTER_LOSS[ratio] || 0;
    }

    // Calculate total link loss
    calculateTotalLoss({
        distance,
        wavelength = '1310nm',
        connectorType = 'LCP',
        connectorCount = 2,
        splitterRatio = '1:32'
    }) {
        const fiberLoss = this.calculateFiberLoss(distance, wavelength);
        const connectorLoss = this.calculateConnectorLoss(connectorType, connectorCount);
        const splitterLoss = this.calculateSplitterLoss(splitterRatio);
        
        return {
            fiberLoss: parseFloat(fiberLoss.toFixed(2)),
            connectorLoss: parseFloat(connectorLoss.toFixed(2)),
            splitterLoss: parseFloat(splitterLoss.toFixed(2)),
            totalLoss: parseFloat((fiberLoss + connectorLoss + splitterLoss).toFixed(2))
        };
    }

    // Calculate power budget
    calculatePowerBudget({
        txPower,
        distance,
        wavelength = '1310nm',
        connectorType = 'LCP',
        connectorCount = 2,
        splitterRatio = '1:32'
    }) {
        const losses = this.calculateTotalLoss({
            distance,
            wavelength,
            connectorType,
            connectorCount,
            splitterRatio
        });

        const powerBudget = txPower - losses.totalLoss - this.SAFETY_MARGIN;
        const powerMargin = powerBudget - Math.abs(this.RECEIVER_SENSITIVITY);

        return {
            ...losses,
            txPower: parseFloat(txPower.toFixed(2)),
            safetyMargin: this.SAFETY_MARGIN,
            receiverSensitivity: this.RECEIVER_SENSITIVITY,
            powerBudget: parseFloat(powerBudget.toFixed(2)),
            powerMargin: parseFloat(powerMargin.toFixed(2)),
            status: this.getPowerBudgetStatus(powerMargin)
        };
    }

    // Get power budget status
    getPowerBudgetStatus(powerMargin) {
        if (powerMargin < 0) {
            return {
                level: 'critical',
                message: 'Insufficient power budget'
            };
        } else if (powerMargin < 2) {
            return {
                level: 'warning',
                message: 'Low power margin'
            };
        } else if (powerMargin < 4) {
            return {
                level: 'acceptable',
                message: 'Acceptable power margin'
            };
        } else {
            return {
                level: 'good',
                message: 'Good power margin'
            };
        }
    }

    // Calculate maximum distance
    calculateMaxDistance({
        txPower,
        wavelength = '1310nm',
        connectorType = 'LCP',
        connectorCount = 2,
        splitterRatio = '1:32',
        minPowerMargin = 2
    }) {
        const fixedLosses = 
            this.calculateConnectorLoss(connectorType, connectorCount) +
            this.calculateSplitterLoss(splitterRatio) +
            this.SAFETY_MARGIN;

        const availableLoss = txPower - Math.abs(this.RECEIVER_SENSITIVITY) - fixedLosses - minPowerMargin;
        const maxDistance = (availableLoss / this.FIBER_LOSS_PER_KM[wavelength]) * 1000;

        return {
            maxDistance: parseFloat(maxDistance.toFixed(0)),
            fixedLosses: parseFloat(fixedLosses.toFixed(2)),
            availableLoss: parseFloat(availableLoss.toFixed(2))
        };
    }

    // Get recommended splitter based on distance and client count
    getRecommendedSplitter(distance, clientCount) {
        const splitterRatios = Object.keys(this.SPLITTER_LOSS);
        const validSplitters = splitterRatios.filter(ratio => {
            const ports = parseInt(ratio.split(':')[1]);
            return ports >= clientCount;
        });

        return validSplitters.reduce((best, ratio) => {
            const loss = this.calculateSplitterLoss(ratio);
            const fiberLoss = this.calculateFiberLoss(distance);
            const totalLoss = loss + fiberLoss;

            if (!best || totalLoss < best.totalLoss) {
                return {
                    ratio,
                    ports: parseInt(ratio.split(':')[1]),
                    splitterLoss: loss,
                    fiberLoss,
                    totalLoss
                };
            }
            return best;
        }, null);
    }
}

// Initialize calculator when document is ready
document.addEventListener('DOMContentLoaded', () => {
    window.powerCalculator = new PowerCalculator();
});

// Example usage:
/*
const calc = new PowerCalculator();

// Calculate power budget for a connection
const budget = calc.calculatePowerBudget({
    txPower: 3.0,      // OLT TX power in dBm
    distance: 2000,    // Total fiber distance in meters
    wavelength: '1310nm',
    connectorType: 'LCP',
    connectorCount: 2,
    splitterRatio: '1:32'
});

// Calculate maximum distance
const maxDist = calc.calculateMaxDistance({
    txPower: 3.0,
    splitterRatio: '1:32',
    minPowerMargin: 2
});

// Get recommended splitter
const splitter = calc.getRecommendedSplitter(2000, 24);
*/

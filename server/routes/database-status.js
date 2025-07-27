const express = require('express');
const { authenticateToken } = require('../middleware/auth');
const dbManager = require('../utils/database-manager');

const router = express.Router();

// Get database status and connectivity info
router.get('/status', authenticateToken, async (req, res) => {
  try {
    const status = dbManager.getStatus();
    
    res.json({
      success: true,
      database: {
        mode: status.databaseMode,
        isOnline: status.isOnline,
        syncQueue: status.syncQueueLength,
        lastSync: status.lastSyncAttempt,
        status: status.isOnline ? 'Connected to Neon PostgreSQL' : 'Running in Offline Mode (SQLite)'
      }
    });
  } catch (error) {
    console.error('Error getting database status:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Failed to get database status',
      details: error.message 
    });
  }
});

// Force sync offline data
router.post('/sync', authenticateToken, async (req, res) => {
  try {
    const status = dbManager.getStatus();
    
    if (!status.isOnline) {
      return res.status(400).json({
        success: false,
        error: 'Cannot sync while offline',
        message: 'Database is currently in offline mode'
      });
    }

    if (status.syncQueueLength === 0) {
      return res.json({
        success: true,
        message: 'No data to sync',
        synced: 0
      });
    }

    await dbManager.syncOfflineData();
    const newStatus = dbManager.getStatus();
    
    res.json({
      success: true,
      message: 'Sync completed successfully',
      before: status.syncQueueLength,
      after: newStatus.syncQueueLength,
      synced: status.syncQueueLength - newStatus.syncQueueLength
    });
    
  } catch (error) {
    console.error('Error syncing data:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Failed to sync data',
      details: error.message 
    });
  }
});

// Get offline data summary
router.get('/offline-summary', authenticateToken, async (req, res) => {
  try {
    const status = dbManager.getStatus();
    
    if (status.isOnline) {
      return res.json({
        success: true,
        message: 'Currently online - no offline data',
        offlineData: {}
      });
    }

    // Get counts of pending sync data from SQLite
    const pendingCounts = {};
    const tables = ['clients', 'plans', 'client_plans', 'billings', 'payments'];
    
    for (const table of tables) {
      try {
        const result = await dbManager.sqliteQuery(
          `SELECT COUNT(*) as count FROM ${table} WHERE sync_status = 'pending'`
        );
        pendingCounts[table] = result.rows[0]?.count || 0;
      } catch (error) {
        pendingCounts[table] = 0;
      }
    }

    res.json({
      success: true,
      offlineData: {
        mode: 'Offline (SQLite)',
        pendingSync: pendingCounts,
        queueLength: status.syncQueueLength,
        totalPending: Object.values(pendingCounts).reduce((sum, count) => sum + count, 0)
      }
    });
    
  } catch (error) {
    console.error('Error getting offline summary:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Failed to get offline summary',
      details: error.message 
    });
  }
});

// Switch to offline mode (for testing)
router.post('/switch-offline', authenticateToken, async (req, res) => {
  try {
    dbManager.isOnline = false;
    
    res.json({
      success: true,
      message: 'Switched to offline mode',
      status: dbManager.getStatus()
    });
    
  } catch (error) {
    console.error('Error switching to offline mode:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Failed to switch to offline mode',
      details: error.message 
    });
  }
});

// Switch to online mode (for testing)
router.post('/switch-online', authenticateToken, async (req, res) => {
  try {
    const isConnected = await dbManager.testConnectivity();
    
    if (isConnected) {
      dbManager.isOnline = true;
      
      res.json({
        success: true,
        message: 'Switched to online mode',
        status: dbManager.getStatus()
      });
    } else {
      res.status(400).json({
        success: false,
        error: 'Cannot switch to online mode - no connectivity to Neon database'
      });
    }
    
  } catch (error) {
    console.error('Error switching to online mode:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Failed to switch to online mode',
      details: error.message 
    });
  }
});

module.exports = router;
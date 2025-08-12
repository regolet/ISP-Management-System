const express = require('express');
const sqliteManager = require('../utils/sqlite-database-manager');

const router = express.Router();

// Get SQLite database status
router.get('/status', async (req, res) => {
  try {
    const status = sqliteManager.getStatus();
    
    res.json({
      success: true,
      database: {
        mode: status.databaseMode,
        isOnline: false,
        status: 'SQLite Database Active',
        dbPath: status.dbPath
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

// SQLite-only system - sync not needed
router.post('/sync', async (req, res) => {
  res.json({
    success: true,
    message: 'SQLite-only system - no sync required',
    synced: 0
  });
});

// Initialize SQLite database
router.post('/init-offline', async (req, res) => {
  try {
    console.log('[Database Status] Reinitializing SQLite database...');
    
    // Reinitialize the SQLite database
    await sqliteManager.initializeSQLiteDatabase();
    
    res.json({
      success: true,
      message: 'SQLite database reinitialized successfully',
      status: sqliteManager.getStatus()
    });
    
  } catch (error) {
    console.error('Error reinitializing SQLite database:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Failed to reinitialize SQLite database',
      details: error.message 
    });
  }
});

module.exports = router;
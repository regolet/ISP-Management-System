const express = require('express');
const { authenticateToken } = require('../middleware/auth');
const dbManager = require('../utils/database-manager');

const router = express.Router();

// Get database status and connectivity info
router.get('/status', authenticateToken, async (req, res) => {
  try {
    const status = dbManager.getStatus();
    
    // If test parameter is provided, also test connectivity
    if (req.query.test === 'true') {
      console.log('[Database Status] Testing connectivity as requested...');
      const startTime = Date.now();
      const isConnected = await dbManager.testConnectivity();
      const responseTime = Date.now() - startTime;
      
      // Get connection details (hide password)
      const connectionString = dbManager.pgPool.options.connectionString || '';
      const sanitizedConnString = connectionString.replace(/:[^@]+@/, ':****@');
      
      return res.json({
        success: true,
        database: {
          mode: status.databaseMode,
          isOnline: status.isOnline,
          syncQueue: status.syncQueueLength,
          lastSync: status.lastSyncAttempt,
          status: status.isOnline ? 'Connected to Neon PostgreSQL' : 'Running in Offline Mode (SQLite)'
        },
        connectionTest: {
          tested: true,
          connected: isConnected,
          responseTime: `${responseTime}ms`,
          connectionString: sanitizedConnString,
          ssl: dbManager.pgPool.options.ssl ? 'Enabled' : 'Disabled',
          advice: isConnected 
            ? 'Database connection is working. You can switch to online mode.'
            : 'Cannot connect to database. Check your internet connection and database credentials.'
        }
      });
    }
    
    // Normal status response
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
    // Before switching offline, sync essential data
    console.log('[Database Status] Preparing to switch offline - syncing essential data...');
    
    // Sync current user to offline database
    try {
      await dbManager.syncUserToOffline(req.user.username);
    } catch (syncError) {
      console.error('[Database Status] Error syncing user data:', syncError);
    }
    
    // Switch to offline mode
    dbManager.isOnline = false;
    console.log('[Database Status] Switched to offline mode');
    
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
    const { forceOnline } = req.body;
    
    // Allow force online mode for testing/development
    if (forceOnline) {
      dbManager.isOnline = true;
      console.log('[Database Status] Force switched to online mode (bypass connectivity check)');
      
      return res.json({
        success: true,
        message: 'Force switched to online mode (connectivity bypassed)',
        status: dbManager.getStatus(),
        warning: 'Database operations may fail if cloud database is actually unreachable'
      });
    }
    
    // Normal connectivity check
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
        error: 'Cannot connect to cloud database. Please check your internet connection or database configuration.',
        message: 'The system will remain in offline mode. Your data is safe and will sync when connection is restored.',
        isOffline: true,
        tip: 'You can force online mode for testing, but database operations may fail.'
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

// Initialize offline database endpoint
router.post('/init-offline', authenticateToken, async (req, res) => {
  try {
    console.log('[Database Status] Initializing offline database...');
    
    // Re-initialize the offline database (this will recreate it)
    await dbManager.initializeOfflineDatabase();
    
    res.json({
      success: true,
      message: 'Offline database initialized successfully',
      status: dbManager.getStatus()
    });
    
  } catch (error) {
    console.error('Error initializing offline database:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Failed to initialize offline database',
      details: error.message 
    });
  }
});

// Download data from online to offline endpoint
router.post('/download-data', authenticateToken, async (req, res) => {
  try {
    console.log('[Database Status] Starting data download from online to offline...');
    
    // Check if we can connect to online database
    const isConnected = await dbManager.testConnectivity();
    if (!isConnected) {
      return res.status(400).json({
        success: false,
        error: 'Cannot connect to online database',
        message: 'Please check your internet connection and try again'
      });
    }
    
    // Download data from online to offline
    const result = await dbManager.downloadDataToOffline();
    
    res.json({
      success: true,
      message: 'Data download completed successfully',
      ...result
    });
    
  } catch (error) {
    console.error('Error downloading data to offline:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Failed to download data to offline',
      details: error.message 
    });
  }
});

// Check schema differences between PostgreSQL and SQLite
router.get('/schema-check', authenticateToken, async (req, res) => {
  try {
    console.log('[Database Status] Checking schema differences...');
    
    const dbManager = require('../utils/database-manager');
    
    // Get PostgreSQL schema
    const pgClient = await dbManager.pgPool.connect();
    const pgSchemaQuery = `
      SELECT table_name, column_name, data_type, is_nullable, column_default
      FROM information_schema.columns 
      WHERE table_schema = 'public' 
      AND table_name IN ('users', 'clients', 'plans', 'client_plans', 'billings', 'payments', 'company_info', 'mikrotik_settings', 'network_summary', 'interface_stats', 'scheduler_settings', 'monitoring_groups', 'monitoring_categories', 'inventory_categories', 'inventory_suppliers', 'inventory_items', 'inventory_assignments', 'inventory_movements', 'assets', 'asset_collections', 'asset_subitems')
      ORDER BY table_name, ordinal_position
    `;
    const pgResult = await pgClient.query(pgSchemaQuery);
    pgClient.release();
    
    // Get SQLite schema  
    const sqliteSchema = {};
    const tables = ['users', 'clients', 'plans', 'client_plans', 'billings', 'payments', 'company_info', 'mikrotik_settings', 'network_summary', 'interface_stats', 'scheduler_settings', 'monitoring_groups', 'monitoring_categories', 'inventory_categories', 'inventory_suppliers', 'inventory_items', 'inventory_assignments', 'inventory_movements', 'assets', 'asset_collections', 'asset_subitems'];
    
    for (const table of tables) {
      try {
        const result = await dbManager.sqliteQuery(`PRAGMA table_info(${table})`);
        sqliteSchema[table] = result.rows.map(row => ({
          column_name: row.name,
          data_type: row.type,
          is_nullable: row.notnull === 0 ? 'YES' : 'NO',
          column_default: row.dflt_value
        }));
      } catch (error) {
        sqliteSchema[table] = [];
      }
    }
    
    // Compare schemas
    const pgSchema = {};
    pgResult.rows.forEach(row => {
      if (!pgSchema[row.table_name]) pgSchema[row.table_name] = [];
      pgSchema[row.table_name].push(row);
    });
    
    const differences = {};
    
    // Check each table
    Object.keys(pgSchema).forEach(tableName => {
      const pgColumns = pgSchema[tableName];
      const sqliteColumns = sqliteSchema[tableName] || [];
      
      const pgColumnNames = pgColumns.map(col => col.column_name);
      const sqliteColumnNames = sqliteColumns.map(col => col.column_name);
      
      const missingInSqlite = pgColumnNames.filter(col => !sqliteColumnNames.includes(col));
      const extraInSqlite = sqliteColumnNames.filter(col => !pgColumnNames.includes(col));
      
      if (missingInSqlite.length > 0 || extraInSqlite.length > 0) {
        differences[tableName] = {
          missingInSqlite,
          extraInSqlite,
          pgColumns: pgColumnNames,
          sqliteColumns: sqliteColumnNames
        };
      }
    });
    
    // Check for completely missing tables
    const pgTableNames = Object.keys(pgSchema);
    const sqliteTableNames = Object.keys(sqliteSchema).filter(table => sqliteSchema[table].length > 0);
    const missingTables = pgTableNames.filter(table => !sqliteTableNames.includes(table));
    
    res.json({
      success: true,
      schemaComparison: {
        differences,
        missingTables,
        summary: {
          pgTables: pgTableNames.length,
          sqliteTables: sqliteTableNames.length,
          tablesWithDifferences: Object.keys(differences).length
        }
      }
    });
    
  } catch (error) {
    console.error('Error checking schema:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Failed to check schema differences',
      details: error.message 
    });
  }
});

// Test database connection endpoint
router.get('/test-connection', authenticateToken, async (req, res) => {
  try {
    console.log('[Database Status] Testing database connection...');
    
    // Test connectivity
    const startTime = Date.now();
    const isConnected = await dbManager.testConnectivity();
    const responseTime = Date.now() - startTime;
    
    // Get connection details (hide password)
    const connectionString = dbManager.pgPool.options.connectionString || '';
    const sanitizedConnString = connectionString.replace(/:[^@]+@/, ':****@');
    
    res.json({
      success: true,
      connected: isConnected,
      responseTime: `${responseTime}ms`,
      connectionDetails: {
        connectionString: sanitizedConnString,
        ssl: dbManager.pgPool.options.ssl ? 'Enabled' : 'Disabled',
        currentMode: dbManager.isOnline ? 'Online' : 'Offline'
      },
      advice: isConnected 
        ? 'Database connection is working. You can switch to online mode.'
        : 'Cannot connect to database. Check your internet connection and database credentials.'
    });
    
  } catch (error) {
    console.error('Error testing connection:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Failed to test connection',
      details: error.message 
    });
  }
});

module.exports = router;
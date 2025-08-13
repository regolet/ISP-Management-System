const express = require('express');
const router = express.Router();
const pool = require('../config/database');
const SupabaseSync = require('../utils/supabase-sync');

// Get Supabase configuration
router.get('/config', async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT * FROM supabase_config 
      ORDER BY created_at DESC 
      LIMIT 1
    `);
    client.release();

    if (result.rows.length === 0) {
      return res.json({ 
        success: true, 
        configured: false 
      });
    }

    // Don't send the actual key, just indicate if configured
    const config = result.rows[0];
    res.json({
      success: true,
      configured: true,
      url: config.url,
      lastSync: config.last_sync
    });
  } catch (error) {
    // Table might not exist yet
    res.json({ 
      success: true, 
      configured: false 
    });
  }
});

// Save Supabase configuration
router.post('/config', async (req, res) => {
  try {
    const { url, anonKey } = req.body;

    if (!url || !anonKey) {
      return res.status(400).json({
        success: false,
        error: 'Supabase URL and Anon Key are required'
      });
    }

    const client = await pool.connect();

    // Create config table if it doesn't exist
    await client.query(`
      CREATE TABLE IF NOT EXISTS supabase_config (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        url VARCHAR(255) NOT NULL,
        anon_key TEXT NOT NULL,
        last_sync TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Test connection before saving
    const sync = new SupabaseSync(url, anonKey);
    const testResult = await sync.testConnection();

    // Clear existing config and save new one
    await client.query('DELETE FROM supabase_config');
    await client.query(
      'INSERT INTO supabase_config (url, anon_key) VALUES (?, ?)',
      [url, anonKey]
    );

    client.release();

    res.json({
      success: true,
      message: 'Supabase configuration saved successfully',
      connectionTest: testResult
    });
  } catch (error) {
    console.error('Error saving Supabase config:', error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Test Supabase connection
router.post('/test-connection', async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT * FROM supabase_config 
      ORDER BY created_at DESC 
      LIMIT 1
    `);
    client.release();

    if (result.rows.length === 0) {
      return res.status(400).json({
        success: false,
        error: 'Supabase not configured'
      });
    }

    const config = result.rows[0];
    const sync = new SupabaseSync(config.url, config.anon_key);
    const testResult = await sync.testConnection();

    res.json({
      success: true,
      ...testResult
    });
  } catch (error) {
    console.error('Error testing connection:', error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});


// Sync all data to Supabase
router.post('/sync', async (req, res) => {
  try {
    const client = await pool.connect();
    
    // Get Supabase configuration
    const configResult = await client.query(`
      SELECT * FROM supabase_config 
      ORDER BY created_at DESC 
      LIMIT 1
    `);

    if (configResult.rows.length === 0) {
      client.release();
      return res.status(400).json({
        success: false,
        error: 'Supabase not configured. Please configure Supabase settings first.'
      });
    }

    const config = configResult.rows[0];
    
    // Initialize Supabase sync
    const sync = new SupabaseSync(config.url, config.anon_key);
    
    // Test connection first
    const connectionTest = await sync.testConnection();
    
    if (connectionTest.needsSchema) {
      client.release();
      return res.status(400).json({
        success: false,
        error: 'Supabase database schema not created. Please create the schema first using the SQL provided.',
        needsSchema: true
      });
    }

    // Perform sync
    console.log('Starting Supabase sync...');
    console.log('Supabase URL:', config.url);
    const syncResult = await sync.syncAllData();
    console.log('Sync completed. Success:', syncResult.success);
    if (!syncResult.success && syncResult.errors) {
      console.log('Sync errors:', syncResult.errors);
    }

    // Update last sync time
    await client.query(
      'UPDATE supabase_config SET last_sync = CURRENT_TIMESTAMP WHERE id = ?',
      [config.id]
    );

    client.release();

    // Prepare detailed response
    let message = 'Data sync completed';
    if (syncResult.success) {
      const totalRecords = Object.values(syncResult.tables).reduce((sum, table) => sum + (table.count || 0), 0);
      message = `Successfully synced ${totalRecords} records across ${Object.keys(syncResult.tables).length} tables`;
    } else {
      message = `Sync completed with ${syncResult.errors.length} errors. Check console for details.`;
    }

    res.json({
      success: syncResult.success,
      message: message,
      results: syncResult,
      details: {
        tablesProcessed: Object.keys(syncResult.tables),
        errors: syncResult.errors
      }
    });
  } catch (error) {
    console.error('Error during sync:', error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Get sync status
router.get('/status', async (req, res) => {
  try {
    const client = await pool.connect();
    
    // Check if config exists
    const configResult = await client.query(`
      SELECT url, last_sync FROM supabase_config 
      ORDER BY created_at DESC 
      LIMIT 1
    `);

    if (configResult.rows.length === 0) {
      client.release();
      return res.json({
        success: true,
        configured: false,
        message: 'Supabase not configured'
      });
    }

    const config = configResult.rows[0];

    // Get table counts from local database
    const tables = [
      'clients', 'plans', 'client_plans', 
      'billings', 'payments', 'inventory_items',
      'monitoring_groups', 'monitoring_categories',
      'tickets', 'assets', 'network_summary'
    ];
    
    const counts = {};
    for (const table of tables) {
      try {
        const countResult = await client.query(`SELECT COUNT(*) as count FROM ${table}`);
        counts[table] = parseInt(countResult.rows[0].count);
      } catch (error) {
        counts[table] = 0;
      }
    }

    client.release();

    res.json({
      success: true,
      configured: true,
      url: config.url,
      lastSync: config.last_sync,
      localDataCounts: counts
    });
  } catch (error) {
    console.error('Error getting sync status:', error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

module.exports = router;
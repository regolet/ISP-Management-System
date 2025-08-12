const express = require('express');
const pool = require('../config/database');
const RouterOSAPI = require('routeros-api').RouterOSAPI;

const router = express.Router();

// Get PPP accounts from MikroTik
router.get('/accounts', async (req, res) => {
  try {
    const client = await pool.connect();
    const settingsResult = await client.query('SELECT * FROM mikrotik_settings ORDER BY created_at DESC LIMIT 1');
    client.release();
    
    if (settingsResult.rows.length === 0) {
      return res.status(400).json({ error: 'MikroTik settings not configured' });
    }
    
    const settings = settingsResult.rows[0];
    const conn = new RouterOSAPI({
      host: settings.host,
      user: settings.username,
      password: settings.password,
      port: settings.port || 8728
    });
    
    await conn.connect();
    const accounts = await conn.write('/ppp/secret/print');
    await conn.close();
    
    res.json({ success: true, accounts: Array.isArray(accounts) ? accounts : [] });
  } catch (error) {
    console.error('MikroTik connection error:', error);
    res.status(500).json({ error: 'Failed to connect to MikroTik', details: error.message });
  }
});

// Get PPP profiles from MikroTik
router.get('/profiles', async (req, res) => {
  try {
    const client = await pool.connect();
    const settingsResult = await client.query('SELECT * FROM mikrotik_settings ORDER BY created_at DESC LIMIT 1');
    client.release();
    
    if (settingsResult.rows.length === 0) {
      return res.status(400).json({ error: 'MikroTik settings not configured' });
    }
    
    const settings = settingsResult.rows[0];
    const conn = new RouterOSAPI({
      host: settings.host,
      user: settings.username,
      password: settings.password,
      port: settings.port || 8728
    });
    
    await conn.connect();
    const profiles = await conn.write('/ppp/profile/print');
    await conn.close();
    
    res.json({ success: true, profiles: Array.isArray(profiles) ? profiles : [] });
  } catch (error) {
    console.error('MikroTik connection error:', error);
    res.status(500).json({ error: 'Failed to connect to MikroTik', details: error.message });
  }
});

// Import clients from MikroTik PPP accounts
router.post('/import-clients', async (req, res) => {
  try {
    const { selectedAccounts } = req.body;
    
    if (!selectedAccounts || !Array.isArray(selectedAccounts)) {
      return res.status(400).json({ error: 'Selected accounts are required' });
    }
    
    const client = await pool.connect();
    let importedCount = 0;
    
    for (const account of selectedAccounts) {
      try {
        // Check if client already exists
        const existing = await client.query('SELECT id FROM clients WHERE name = ?', [account.name]);
        
        if (existing.rows.length === 0) {
          // Import as new client
          await client.query(
            'INSERT INTO clients (name, email, address, status) VALUES (?, ?, ?, ?)',
            [account.name, account.comment || `${account.name}@isp.local`, account.profile || 'Imported from MikroTik', 'active']
          );
          importedCount++;
        }
      } catch (error) {
        console.error(`Error importing client ${account.name}:`, error);
      }
    }
    
    client.release();
    res.json({ success: true, imported: importedCount });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

module.exports = router;
const express = require('express');
const pool = require('../config/database');
const { authenticateToken } = require('../middleware/auth');
const RouterOSAPI = require('routeros-api').RouterOSAPI;

const router = express.Router();

// Get MikroTik settings
router.get('/settings', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT * FROM mikrotik_settings ORDER BY created_at DESC LIMIT 1');
    client.release();
    res.json(result.rows[0] || {});
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Save MikroTik settings
router.post('/settings', authenticateToken, async (req, res) => {
  try {
    const { host, username, password, port = 8728 } = req.body;
    
    if (!host || !username || !password) {
      return res.status(400).json({ error: 'Host, username, and password are required' });
    }
    
    const client = await pool.connect();
    
    // Delete existing settings and insert new ones
    await client.query('DELETE FROM mikrotik_settings');
    const result = await client.query(
      'INSERT INTO mikrotik_settings (host, username, password, port) VALUES ($1, $2, $3, $4) RETURNING *',
      [host, username, password, port]
    );
    
    client.release();
    res.json({ success: true, settings: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

module.exports = router;
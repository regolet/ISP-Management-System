const express = require('express');
const router = express.Router();
const pool = require('../config/database');

// Initialize Supabase config table
router.post('/init-table', async (req, res) => {
  try {
    const client = await pool.connect();
    
    // Create supabase_config table if it doesn't exist
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
    
    client.release();
    
    res.json({
      success: true,
      message: 'Supabase config table created successfully'
    });
  } catch (error) {
    console.error('Error creating supabase_config table:', error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

module.exports = router;
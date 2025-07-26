const express = require('express');
const pool = require('../config/database');
const { authenticateToken } = require('../middleware/auth');

const router = express.Router();

// Get company information
router.get('/', async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT * FROM company_info ORDER BY created_at DESC LIMIT 1');
    client.release();
    res.json(result.rows[0] || {});
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update company information
router.post('/', authenticateToken, async (req, res) => {
  try {
    const { company_name, address, phone, email, website } = req.body;
    const client = await pool.connect();
    
    // Check if company info exists
    const existing = await client.query('SELECT id FROM company_info LIMIT 1');
    
    let result;
    if (existing.rows.length > 0) {
      // Update existing
      result = await client.query(
        'UPDATE company_info SET company_name = $1, address = $2, phone = $3, email = $4, website = $5, updated_at = CURRENT_TIMESTAMP WHERE id = $6 RETURNING *',
        [company_name, address, phone, email, website, existing.rows[0].id]
      );
    } else {
      // Insert new
      result = await client.query(
        'INSERT INTO company_info (company_name, address, phone, email, website) VALUES ($1, $2, $3, $4, $5) RETURNING *',
        [company_name, address, phone, email, website]
      );
    }
    
    client.release();
    res.json({ success: true, company: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

module.exports = router;
const express = require('express');
const pool = require('../config/database');
const { authenticateToken } = require('../middleware/auth');

const router = express.Router();


// Get company information
router.get('/', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    
    const result = await client.query('SELECT * FROM company_info ORDER BY created_at DESC LIMIT 1');
    client.release();
    
    let data = result.rows[0] || {};
    
    // Map database columns to frontend expected format
    if (data.name) {
      data.company_name = data.name;
    }
    if (data.logo_url) {
      data.website = data.logo_url;
    }
    
    res.json(data);
  } catch (error) {
    console.error('Error loading company info:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update company information
router.post('/', authenticateToken, async (req, res) => {
  try {
    const { company_name, address, phone, email, website } = req.body;
    
    if (!company_name) {
      return res.status(400).json({ error: 'Company name is required' });
    }
    
    const client = await pool.connect();
    
    // Check if company info exists
    const existing = await client.query('SELECT id FROM company_info LIMIT 1');
    
    let result;
    if (existing.rows.length > 0) {
      // Update existing record
      result = await client.query(
        'UPDATE company_info SET name = $1, address = $2, phone = $3, email = $4, logo_url = $5 WHERE id = $6 RETURNING *',
        [company_name, address || '', phone || '', email || '', website || '', existing.rows[0].id]
      );
    } else {
      // Insert new record
      result = await client.query(
        'INSERT INTO company_info (name, address, phone, email, logo_url) VALUES ($1, $2, $3, $4, $5) RETURNING *',
        [company_name, address || '', phone || '', email || '', website || '']
      );
    }
    
    client.release();
    res.json({ success: true, company: result.rows[0] });
    
  } catch (error) {
    console.error('Error saving company info:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

module.exports = router;
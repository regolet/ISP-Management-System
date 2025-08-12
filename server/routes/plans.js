const express = require('express');
const pool = require('../config/database');

const router = express.Router();

// Get all plans
router.get('/', async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT * FROM plans ORDER BY created_at DESC');
    client.release();
    res.json(result.rows);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Create new plan
router.post('/', async (req, res) => {
  try {
    const { name, description, price, speed, download_speed, upload_speed, status } = req.body;
    const client = await pool.connect();
    const result = await client.query(
      'INSERT INTO plans (name, description, price, speed, download_speed, upload_speed, status) VALUES ($1, $2, $3, $4, $5, $6, $7) RETURNING *',
      [name, description, price, speed, download_speed, upload_speed, status || 'active']
    );
    client.release();
    res.json({ success: true, plan: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update plan by ID
router.put('/:id', async (req, res) => {
  try {
    const planId = req.params.id;
    const { name, description, price, speed, download_speed, upload_speed, status } = req.body;
    const client = await pool.connect();
    const result = await client.query(
      'UPDATE plans SET name = $1, description = $2, price = $3, speed = $4, download_speed = $5, upload_speed = $6, status = $7, updated_at = CURRENT_TIMESTAMP WHERE id = $8 RETURNING *',
      [name, description, price, speed, download_speed, upload_speed, status, planId]
    );
    client.release();
    if (result.rowCount === 0) {
      return res.status(404).json({ error: 'Plan not found' });
    }
    res.json({ success: true, plan: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Delete plan by ID
router.delete('/:id', async (req, res) => {
  try {
    const planId = req.params.id;
    const client = await pool.connect();
    const result = await client.query('DELETE FROM plans WHERE id = $1 RETURNING *', [planId]);
    client.release();
    if (result.rowCount === 0) {
      return res.status(404).json({ error: 'Plan not found' });
    }
    res.json({ success: true, deleted: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

module.exports = router;
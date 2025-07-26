const express = require('express');
const pool = require('../config/database');
const { authenticateToken } = require('../middleware/auth');

const router = express.Router();

// Get client plans by client ID
router.get('/:clientId', authenticateToken, async (req, res) => {
  try {
    const clientId = req.params.clientId;
    const client = await pool.connect();
    const result = await client.query(`
      SELECT cp.*, p.name as plan_name, p.price, p.speed
      FROM client_plans cp
      JOIN plans p ON cp.plan_id = p.id
      WHERE cp.client_id = $1
      ORDER BY cp.created_at DESC
    `, [clientId]);
    client.release();
    res.json(result.rows);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Create new client plan assignment
router.post('/', authenticateToken, async (req, res) => {
  try {
    const { client_id, plan_id, status = 'active' } = req.body;
    
    if (!client_id || !plan_id) {
      return res.status(400).json({ error: 'Client ID and Plan ID are required' });
    }

    const client = await pool.connect();
    
    // Check if this client-plan combination already exists
    const existing = await client.query(
      'SELECT id FROM client_plans WHERE client_id = $1 AND plan_id = $2',
      [client_id, plan_id]
    );
    
    if (existing.rows.length > 0) {
      client.release();
      return res.status(400).json({ error: 'This plan is already assigned to this client' });
    }
    
    const result = await client.query(
      'INSERT INTO client_plans (client_id, plan_id, status) VALUES ($1, $2, $3) RETURNING *',
      [client_id, plan_id, status]
    );
    
    client.release();
    res.json({ success: true, clientPlan: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Delete client plan assignment
router.delete('/:id', authenticateToken, async (req, res) => {
  try {
    const clientPlanId = req.params.id;
    const client = await pool.connect();
    const result = await client.query('DELETE FROM client_plans WHERE id = $1 RETURNING *', [clientPlanId]);
    client.release();
    
    if (result.rowCount === 0) {
      return res.status(404).json({ error: 'Client plan not found' });
    }
    res.json({ success: true, deleted: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Note: /api/client-plans-count and /api/client-plans-all need separate route handling
// These will be handled directly in the main app.js file since they don't follow REST pattern

module.exports = router;
const express = require('express');
const pool = require('../config/database');
const { authenticateToken } = require('../middleware/auth');
const { recalculateClientBalance, autoPayUnpaidBillings } = require('../utils/billingHelpers');

const router = express.Router();

// Get all billings
router.get('/', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT 
        b.id, 
        b.client_id,
        b.amount, 
        b.due_date, 
        b.balance as prev_balance,
        (COALESCE(b.balance, 0) - COALESCE((
          SELECT SUM(p.amount) 
          FROM payments p 
          WHERE p.client_id = b.client_id 
            AND p.created_at >= (
              SELECT COALESCE(MAX(prev_b.created_at), '1900-01-01'::timestamp)
              FROM billings prev_b 
              WHERE prev_b.client_id = b.client_id 
                AND prev_b.created_at < b.created_at
            )
            AND p.created_at <= b.created_at
        ), 0) + b.amount) as total_amount_due,
        b.created_at, 
        b.updated_at,
        c.name AS client_name,
        p.name AS plan_name,
        c.balance as client_balance,
        b.status,
        TO_CHAR(b.due_date - INTERVAL '1 month' + INTERVAL '1 day', 'Mon DD, YYYY') as period_start,
        TO_CHAR(b.due_date, 'Mon DD, YYYY') as period_end,
        TO_CHAR(b.due_date - INTERVAL '1 month' + INTERVAL '1 day', 'Mon/DD/YYYY') || ' - ' || TO_CHAR(b.due_date, 'Mon/DD/YYYY') as period,
        EXTRACT(MONTH FROM b.due_date) as month,
        EXTRACT(YEAR FROM b.due_date) as year,
        COALESCE((
          SELECT SUM(p.amount) 
          FROM payments p 
          WHERE p.client_id = b.client_id 
            AND p.created_at >= (
              SELECT COALESCE(MAX(prev_b.created_at), '1900-01-01'::timestamp)
              FROM billings prev_b 
              WHERE prev_b.client_id = b.client_id 
                AND prev_b.created_at < b.created_at
            )
            AND p.created_at <= b.created_at
        ), 0) as prev_payments
      FROM billings b
      LEFT JOIN clients c ON b.client_id = c.id
      LEFT JOIN plans p ON b.plan_id = p.id
      ORDER BY b.created_at DESC
    `);
    client.release();
    res.json(result.rows);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error', details: error.message });
  }
});

// Create new billing
router.post('/', authenticateToken, async (req, res) => {
  try {
    let { client_id, plan_id, amount, due_date, status = 'pending' } = req.body;
    if (!client_id || !plan_id || !amount) {
      return res.status(400).json({ error: 'Client ID, Plan ID, and Amount are required' });
    }
    const client = await pool.connect();
    
    // Get client's due date
    const clientResult = await client.query(
      'SELECT TO_CHAR(due_date, \'YYYY-MM-DD\') as due_date FROM clients WHERE id = $1',
      [client_id]
    );
    
    if (clientResult.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Client not found' });
    }
    
    const clientData = clientResult.rows[0];
    
    // Get the total amount due from the most recent billing for this client
    const lastBillingResult = await client.query(`
      SELECT 
        (COALESCE(b.balance, 0) - COALESCE((
          SELECT SUM(p.amount) 
          FROM payments p 
          WHERE p.client_id = b.client_id 
            AND p.created_at >= (
              SELECT COALESCE(MAX(prev_b.created_at), '1900-01-01'::timestamp)
              FROM billings prev_b 
              WHERE prev_b.client_id = b.client_id 
                AND prev_b.created_at < b.created_at
            )
            AND p.created_at <= b.created_at
        ), 0) + b.amount) as total_amount_due
      FROM billings b 
      WHERE b.client_id = $1 
      ORDER BY b.created_at DESC 
      LIMIT 1
    `, [client_id]);
    
    // Previous balance is the total amount due from the last billing (0 if no previous billing)
    const previousBalance = lastBillingResult.rows.length > 0 ? 
      parseFloat(lastBillingResult.rows[0].total_amount_due) : 0;
    
    // If due_date is not provided, use client's due date or current date
    if (!due_date) {
      if (clientData.due_date) {
        due_date = clientData.due_date;
      } else {
        due_date = new Date().toISOString().split('T')[0];
      }
    }
    
    // Create billing with previous balance from last billing's total amount due
    const result = await client.query(
      'INSERT INTO billings (client_id, plan_id, amount, due_date, status, balance) VALUES ($1, $2, $3, $4, $5, $6) RETURNING *',
      [client_id, plan_id, amount, due_date, status, previousBalance]
    );
    
    // Recalculate client balance and update due date in one operation
    const balanceInfo = await recalculateClientBalance(client_id, client, due_date);
    
    // Auto-pay unpaid billings if client has sufficient credit
    await autoPayUnpaidBillings(client_id, client);
    
    client.release();
    res.json({ 
      success: true, 
      billing: result.rows[0],
      client_balance: balanceInfo.calculatedBalance,
      client_payment_status: balanceInfo.clientPaymentStatus
    });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Get individual billing by ID
router.get('/:id', authenticateToken, async (req, res) => {
  try {
    const billingId = req.params.id;
    const client = await pool.connect();
    const result = await client.query(`
      SELECT 
        b.id, 
        b.client_id, 
        b.plan_id, 
        b.amount, 
        b.due_date, 
        b.status, 
        b.created_at, 
        b.updated_at,
        c.name AS client_name,
        p.name AS plan_name
      FROM billings b
      LEFT JOIN clients c ON b.client_id = c.id
      LEFT JOIN plans p ON b.plan_id = p.id
      WHERE b.id = $1
    `, [billingId]);
    client.release();
    
    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Billing not found' });
    }
    
    res.json(result.rows[0]);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update billing by ID
router.put('/:id', authenticateToken, async (req, res) => {
  try {
    const billingId = req.params.id;
    const { client_id, plan_id, amount, due_date, status } = req.body;
    const client = await pool.connect();
    const result = await client.query(
      'UPDATE billings SET client_id = $1, plan_id = $2, amount = $3, due_date = $4, status = $5, updated_at = CURRENT_TIMESTAMP WHERE id = $6 RETURNING *',
      [client_id, plan_id, amount, due_date, status, billingId]
    );
    
    if (result.rowCount === 0) {
      client.release();
      return res.status(404).json({ error: 'Billing not found' });
    }
    
    // Update client's due date to match the updated billing due date
    if (due_date && client_id) {
      await client.query(
        'UPDATE clients SET due_date = $2, updated_at = CURRENT_TIMESTAMP WHERE id = $1',
        [client_id, due_date]
      );
    }
    
    // Recalculate client balance after billing update
    const balanceInfo = await recalculateClientBalance(client_id, client);
    
    client.release();
    res.json({ 
      success: true, 
      billing: result.rows[0],
      client_balance: balanceInfo.calculatedBalance,
      client_payment_status: balanceInfo.clientPaymentStatus
    });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Delete billing by ID
router.delete('/:id', authenticateToken, async (req, res) => {
  try {
    const billingId = req.params.id;
    const client = await pool.connect();
    const result = await client.query('DELETE FROM billings WHERE id = $1 RETURNING *', [billingId]);
    client.release();
    
    if (result.rowCount === 0) {
      return res.status(404).json({ error: 'Billing not found' });
    }
    res.json({ success: true, deleted: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

module.exports = router;
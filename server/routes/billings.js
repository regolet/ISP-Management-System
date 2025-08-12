const express = require('express');
const pool = require('../config/database');
const { recalculateClientBalance, autoPayUnpaidBillings } = require('../utils/billingHelpers');

const router = express.Router();

// Get all billings - minimal SQLite-compatible version
router.get('/', async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT 
        b.*,
        c.name AS client_name,
        p.name AS plan_name
      FROM billings b
      LEFT JOIN clients c ON b.client_id = c.id
      LEFT JOIN plans p ON b.plan_id = p.id
      ORDER BY b.created_at DESC
    `);
    client.release();
    
    // Format the results for the frontend
    const formattedRows = result.rows.map(row => ({
      ...row,
      total_amount_due: row.amount,
      prev_balance: row.balance || 0,
      client_balance: 0,
      status: row.status || 'pending',
      period_start: row.due_date,
      period_end: row.due_date,
      period: 'Monthly Bill',
      month: row.due_date ? new Date(row.due_date).getMonth() + 1 : 1,
      year: row.due_date ? new Date(row.due_date).getFullYear() : new Date().getFullYear(),
      prev_payments: 0
    }));
    
    res.json(formattedRows);
  } catch (error) {
    console.error('Error in billings GET route:', error);
    res.status(500).json({ error: 'Internal server error', details: error.message });
  }
});

// Create new billing
router.post('/', async (req, res) => {
  try {
    let { client_id, plan_id, amount, due_date, status = 'pending' } = req.body;
    if (!client_id || !plan_id || !amount) {
      return res.status(400).json({ error: 'Client ID, Plan ID, and Amount are required' });
    }
    const client = await pool.connect();
    
    // Get client's due date
    const clientResult = await client.query(
      'SELECT due_date FROM clients WHERE id = $1',
      [client_id]
    );
    
    if (clientResult.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Client not found' });
    }
    
    const clientData = clientResult.rows[0];
    
    // Get the most recent billing for this client (simplified)
    const lastBillingResult = await client.query(`
      SELECT amount, balance
      FROM billings 
      WHERE client_id = $1 
      ORDER BY created_at DESC 
      LIMIT 1
    `, [client_id]);
    
    // Previous balance is from the last billing (0 if no previous billing)
    const previousBalance = lastBillingResult.rows.length > 0 ? 
      parseFloat(lastBillingResult.rows[0].balance || 0) : 0;
    
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
    console.error('Error in billings POST route:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Get individual billing by ID
router.get('/:id', async (req, res) => {
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
    console.error('Error in billings POST route:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update billing by ID
router.put('/:id', async (req, res) => {
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
    console.error('Error in billings POST route:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Delete billing by ID
router.delete('/:id', async (req, res) => {
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
    console.error('Error in billings POST route:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Generate billing for clients with due date on specific day
router.post('/generate-for-day', async (req, res) => {
  const dbClient = await pool.connect();
  try {
    const { day } = req.body; // day should be 1-31
    
    if (!day || day < 1 || day > 31) {
      dbClient.release();
      return res.status(400).json({ error: 'Valid day (1-31) is required' });
    }
    
    await dbClient.query('BEGIN');
    
    // Get all clients with active plans that have due date on the specified day
    // This looks for clients whose due date day matches, regardless of month/year
    const clientsResult = await dbClient.query(`
      SELECT DISTINCT
        c.id as client_id,
        c.name as client_name,
        c.due_date,
        cp.plan_id,
        p.name as plan_name,
        p.price as plan_amount
      FROM clients c
      INNER JOIN client_plans cp ON c.id = cp.client_id AND cp.status = 'active'
      INNER JOIN plans p ON cp.plan_id = p.id AND (p.status = 'active' OR p.status IS NULL)
      WHERE CAST(strftime('%d', c.due_date) AS INTEGER) = $1
        AND c.status = 'active'
      ORDER BY c.name
    `, [day]);
    
    // Debug: Let's also check what clients exist with this day, regardless of their plan status
    const debugResult = await dbClient.query(`
      SELECT 
        c.id,
        c.name,
        c.status as client_status,
        c.due_date,
        cp.status as plan_status,
        p.status as plan_active_status
      FROM clients c
      LEFT JOIN client_plans cp ON c.id = cp.client_id
      LEFT JOIN plans p ON cp.plan_id = p.id
      WHERE CAST(strftime('%d', c.due_date) AS INTEGER) = $1
      ORDER BY c.name
    `, [day]);
    
    console.log(`Debug: Found ${debugResult.rows.length} clients with due date day ${day}:`);
    debugResult.rows.forEach(row => {
      console.log(`- ${row.name}: client_status=${row.client_status}, plan_status=${row.plan_status}, plan_active=${row.plan_active_status}, due_date=${row.due_date}`);
    });
    
    if (clientsResult.rows.length === 0) {
      await dbClient.query('ROLLBACK');
      dbClient.release();
      return res.json({ 
        success: true, 
        message: `No active clients found with due date on day ${day}. Debug: Found ${debugResult.rows.length} total clients with this due date day - check their active status and plan assignments.`,
        billings_created: 0,
        clients_processed: [],
        debug_info: debugResult.rows
      });
    }
    
    const billingsCreated = [];
    const errors = [];
    
    // Get current date and set the due date for this month
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear();
    const currentMonth = currentDate.getMonth(); // 0-based
    
    // Calculate the due date for this month
    const lastDayOfMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    const dueDateDay = Math.min(day, lastDayOfMonth); // Handle cases where day doesn't exist in current month
    const dueDate = new Date(currentYear, currentMonth, dueDateDay);
    const dueDateString = dueDate.toISOString().split('T')[0];
    
    for (const clientData of clientsResult.rows) {
      try {
        // Check if billing already exists for this client and month
        const existingBillingResult = await dbClient.query(`
          SELECT id FROM billings 
          WHERE client_id = $1 
            AND plan_id = $2
            AND CAST(strftime('%m', due_date) AS INTEGER) = $3 
            AND CAST(strftime('%Y', due_date) AS INTEGER) = $4
        `, [clientData.client_id, clientData.plan_id, currentMonth + 1, currentYear]);
        
        if (existingBillingResult.rows.length > 0) {
          errors.push({
            client_id: clientData.client_id,
            client_name: clientData.client_name,
            plan_name: clientData.plan_name,
            error: 'Billing already exists for this month'
          });
          continue;
        }
        
        // Get the total amount due from the most recent billing for this client
        const lastBillingResult = await dbClient.query(`
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
        `, [clientData.client_id]);
        
        // Previous balance is the total amount due from the last billing (0 if no previous billing)
        const previousBalance = lastBillingResult.rows.length > 0 ? 
          parseFloat(lastBillingResult.rows[0].total_amount_due) : 0;
        
        // Create the billing
        const billingResult = await dbClient.query(
          'INSERT INTO billings (client_id, plan_id, amount, due_date, status, balance) VALUES ($1, $2, $3, $4, $5, $6) RETURNING *',
          [clientData.client_id, clientData.plan_id, clientData.plan_amount, dueDateString, 'pending', previousBalance]
        );
        
        // Recalculate client balance
        const balanceInfo = await recalculateClientBalance(clientData.client_id, dbClient, dueDateString);
        
        // Auto-pay unpaid billings if client has sufficient credit
        await autoPayUnpaidBillings(clientData.client_id, dbClient);
        
        billingsCreated.push({
          billing_id: billingResult.rows[0].id,
          client_id: clientData.client_id,
          client_name: clientData.client_name,
          plan_name: clientData.plan_name,
          amount: clientData.plan_amount,
          due_date: dueDateString,
          previous_balance: previousBalance,
          new_balance: balanceInfo.calculatedBalance
        });
        
      } catch (error) {
        console.error(`Error creating billing for client ${clientData.client_id}:`, error);
        errors.push({
          client_id: clientData.client_id,
          client_name: clientData.client_name,
          plan_name: clientData.plan_name,
          error: error.message
        });
      }
    }
    
    await dbClient.query('COMMIT');
    dbClient.release();
    
    res.json({ 
      success: true, 
      message: `Generated ${billingsCreated.length} billings for clients with due date on day ${day}`,
      billings_created: billingsCreated.length,
      clients_processed: billingsCreated,
      errors: errors,
      due_date_used: dueDateString
    });
    
  } catch (error) {
    await dbClient.query('ROLLBACK');
    dbClient.release();
    console.error('Error generating billings for day:', error);
    res.status(500).json({ error: 'Internal server error', details: error.message });
  }
});

module.exports = router;
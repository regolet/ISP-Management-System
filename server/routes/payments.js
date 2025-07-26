const express = require('express');
const pool = require('../config/database');
const { authenticateToken } = require('../middleware/auth');
const { recalculateClientBalance } = require('../utils/billingHelpers');

const router = express.Router();

// Helper to get next due date on the same day of month, or last day if not possible
function getNextDueDate(currentDueDate) {
  const date = new Date(currentDueDate);
  const originalDay = date.getDate();
  
  // Move to next month
  let year = date.getFullYear();
  let month = date.getMonth() + 1; // next month (0-based, so +1)
  if (month > 11) {
    month = 0;
    year += 1;
  }
  
  // Get the last day of the next month
  const lastDayOfMonth = new Date(year, month + 1, 0).getDate();
  
  // Use original day if it exists in the month, otherwise use last day
  const day = Math.min(originalDay, lastDayOfMonth);
  const nextDueDate = new Date(year, month, day);
  
  // Format as YYYY-MM-DD
  const yyyy = nextDueDate.getFullYear();
  const mm = String(nextDueDate.getMonth() + 1).padStart(2, '0');
  const dd = String(nextDueDate.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}

// Get all payments
router.get('/', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT 
        p.*,
        c.name as client_name,
        c.balance as current_client_balance,
        -- Calculate balance progression: current balance + sum of all payments made after this one
        (
          c.balance + COALESCE(
            (SELECT SUM(later_p.amount) 
             FROM payments later_p 
             WHERE later_p.client_id = p.client_id 
               AND later_p.created_at > p.created_at), 0
          )
        ) as new_balance,
        -- Previous balance: new balance + this payment amount
        (
          c.balance + COALESCE(
            (SELECT SUM(later_p.amount) 
             FROM payments later_p 
             WHERE later_p.client_id = p.client_id 
               AND later_p.created_at > p.created_at), 0
          ) + p.amount
        ) as prev_balance
      FROM payments p
      LEFT JOIN clients c ON p.client_id = c.id
      ORDER BY p.payment_date DESC, p.created_at DESC
    `);
    client.release();
    res.json(result.rows);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Create new payment
router.post('/', authenticateToken, async (req, res) => {
  const dbClient = await pool.connect();
  try {
    const { client_id, amount, payment_date, method, notes } = req.body;
    const paymentAmount = parseFloat(amount);
    
    if (!client_id || paymentAmount <= 0) {
      dbClient.release();
      return res.status(400).json({ error: 'Client ID and valid payment amount are required' });
    }
    
    // Verify client exists
    const clientCheck = await dbClient.query('SELECT id FROM clients WHERE id = $1', [client_id]);
    if (clientCheck.rows.length === 0) {
      dbClient.release();
      return res.status(404).json({ error: 'Client not found' });
    }
    
    // Insert payment - using correct column names
    const paymentResult = await dbClient.query(
      'INSERT INTO payments (client_id, amount, payment_date, method, notes) VALUES ($1, $2, $3, $4, $5) RETURNING *',
      [client_id, paymentAmount, payment_date, method || 'cash', notes]
    );
    
    // Recalculate client balance after payment
    const balanceInfo = await recalculateClientBalance(client_id, dbClient);
    
    // Update billing statuses based on payments
    // Get all pending billings for this client ordered by due date
    const pendingBillingsResult = await dbClient.query(
      `SELECT id, amount FROM billings 
       WHERE client_id = $1 AND status = 'pending' 
       ORDER BY due_date ASC, created_at ASC`,
      [client_id]
    );
    
    // Get total payments for this client
    const totalPaymentsResult = await dbClient.query(
      'SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE client_id = $1',
      [client_id]
    );
    
    let totalPaid = parseFloat(totalPaymentsResult.rows[0].total_paid);
    let runningTotal = 0;
    
    // Mark billings as paid based on FIFO (First In First Out) principle
    for (const billing of pendingBillingsResult.rows) {
      runningTotal += parseFloat(billing.amount);
      
      if (runningTotal <= totalPaid) {
        // This billing is fully covered by payments
        await dbClient.query(
          'UPDATE billings SET status = $1, updated_at = CURRENT_TIMESTAMP WHERE id = $2',
          ['paid', billing.id]
        );
      }
    }
    
    dbClient.release();
    res.json({ 
      success: true, 
      payment: paymentResult.rows[0],
      client_balance: balanceInfo.calculatedBalance,
      client_payment_status: balanceInfo.clientPaymentStatus
    });
  } catch (error) {
    console.error('Payment creation error:', error);
    dbClient.release();
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Delete payment by ID
router.delete('/:id', authenticateToken, async (req, res) => {
  const dbClient = await pool.connect();
  try {
    const paymentId = req.params.id;
    
    // Get the payment details before deleting
    const paymentResult = await dbClient.query('SELECT * FROM payments WHERE id = $1', [paymentId]);
    
    if (paymentResult.rows.length === 0) {
      dbClient.release();
      return res.status(404).json({ error: 'Payment not found' });
    }
    
    const payment = paymentResult.rows[0];
    
    // Delete the payment
    const deleteResult = await dbClient.query('DELETE FROM payments WHERE id = $1 RETURNING *', [paymentId]);
    
    // Recalculate client balance after payment deletion
    const balanceInfo = await recalculateClientBalance(payment.client_id, dbClient);
    
    // Recalculate billing statuses after payment deletion
    // Reset all billings to pending first
    await dbClient.query(
      'UPDATE billings SET status = $1, updated_at = CURRENT_TIMESTAMP WHERE client_id = $2',
      ['pending', payment.client_id]
    );
    
    // Get total remaining payments for this client
    const totalPaymentsResult = await dbClient.query(
      'SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE client_id = $1',
      [payment.client_id]
    );
    
    // Get all billings for this client ordered by due date
    const billingsResult = await dbClient.query(
      `SELECT id, amount FROM billings 
       WHERE client_id = $1 
       ORDER BY due_date ASC, created_at ASC`,
      [payment.client_id]
    );
    
    let totalPaid = parseFloat(totalPaymentsResult.rows[0].total_paid);
    let runningTotal = 0;
    
    // Mark billings as paid based on FIFO principle
    for (const billing of billingsResult.rows) {
      runningTotal += parseFloat(billing.amount);
      
      if (runningTotal <= totalPaid) {
        await dbClient.query(
          'UPDATE billings SET status = $1, updated_at = CURRENT_TIMESTAMP WHERE id = $2',
          ['paid', billing.id]
        );
      }
    }
    
    dbClient.release();
    res.json({ 
      success: true, 
      deleted: deleteResult.rows[0],
      client_balance: balanceInfo.calculatedBalance,
      client_payment_status: balanceInfo.clientPaymentStatus
    });
  } catch (error) {
    console.error('Payment deletion error:', error);
    dbClient.release();
    res.status(500).json({ error: 'Internal server error' });
  }
});

module.exports = router;
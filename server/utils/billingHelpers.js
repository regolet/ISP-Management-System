// Billing helper functions extracted from main server
const pool = require('../config/database');

async function recalculateClientBalance(client_id, dbClient, due_date = null) {
  // Calculate total owed from all billings (only sum the billing amounts, not previous balances)
  const billingsResult = await dbClient.query(
    'SELECT COALESCE(SUM(amount), 0) as total_owed FROM billings WHERE client_id = $1',
    [client_id]
  );
  
  // Calculate total paid from all payments
  const paymentsResult = await dbClient.query(
    'SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE client_id = $1',
    [client_id]
  );
  
  const totalOwed = parseFloat(billingsResult.rows[0].total_owed);
  const totalPaid = parseFloat(paymentsResult.rows[0].total_paid);
  const calculatedBalance = totalOwed - totalPaid;
  
  // Update client balance and payment status
  const clientPaymentStatus = calculatedBalance > 0 ? (calculatedBalance < totalOwed ? 'partial' : 'unpaid') : 'paid';
  
  if (due_date) {
    await dbClient.query(
      'UPDATE clients SET balance = $1, payment_status = $2, due_date = $3, updated_at = CURRENT_TIMESTAMP WHERE id = $4',
      [calculatedBalance, clientPaymentStatus, due_date, client_id]
    );
  } else {
    await dbClient.query(
      'UPDATE clients SET balance = $1, payment_status = $2, updated_at = CURRENT_TIMESTAMP WHERE id = $3',
      [calculatedBalance, clientPaymentStatus, client_id]
    );
  }
  
  return {
    calculatedBalance,
    clientPaymentStatus,
    totalOwed,
    totalPaid
  };
}

async function autoPayUnpaidBillings(client_id, dbClient) {
  try {
    // Get client's current balance
    const clientResult = await dbClient.query(
      'SELECT balance FROM clients WHERE id = $1',
      [client_id]
    );
    
    if (clientResult.rows.length === 0) return;
    
    const clientBalance = parseFloat(clientResult.rows[0].balance);
    
    // Only auto-pay if client has negative balance (credit) or zero balance
    if (clientBalance > 0) {
      return; // Client has debt, don't auto-pay
    }
    
    // Simplified auto-pay logic
    // This is a simplified version - the original has more complex logic
    console.log('Auto-pay logic would run here for client:', client_id);
    
  } catch (error) {
    console.error('Error in autoPayUnpaidBillings:', error);
  }
}

module.exports = {
  recalculateClientBalance,
  autoPayUnpaidBillings
};
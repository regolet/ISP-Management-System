const express = require('express');
const pool = require('../config/database'); // Now automatically handles offline/online
const { formatDateForDB, formatClientDates } = require('../utils/dateHelpers');
const { authenticateToken } = require('../middleware/auth');

const router = express.Router();

// Get all clients with pagination and filtering
router.get('/', authenticateToken, async (req, res) => {
  try {
    const { 
      page = 1, 
      sortBy = 'created_at', 
      sortOrder = 'desc',
      search = '',
      paymentStatus = '',
      status = ''
    } = req.query;
    
    // Parse and validate limit with a reasonable maximum
    let limit = parseInt(req.query.limit) || 20;
    if (limit > 1000) limit = 1000; // Cap at 1000 for performance
    
    const offset = (page - 1) * limit;
    const client = await pool.connect();
    
    // Build WHERE clause for filtering
    let whereConditions = [];
    let queryParams = [];
    let paramCount = 0;
    
    if (search) {
      paramCount++;
      // Use LIKE for SQLite compatibility (case-insensitive in SQLite by default)
      whereConditions.push(`c.name LIKE $${paramCount}`);
      queryParams.push(`%${search}%`);
    }
    
    if (paymentStatus) {
      paramCount++;
      whereConditions.push(`c.payment_status = $${paramCount}`);
      queryParams.push(paymentStatus);
    }
    
    if (status) {
      paramCount++;
      whereConditions.push(`c.status = $${paramCount}`);
      queryParams.push(status);
    }
    
    const whereClause = whereConditions.length > 0 ? `WHERE ${whereConditions.join(' AND ')}` : '';
    
    // Valid sort columns
    const validSortColumns = ['id', 'name', 'address', 'installation_date', 'due_date', 'payment_status', 'status', 'created_at'];
    const sortColumn = validSortColumns.includes(sortBy) ? sortBy : 'created_at';
    const sortDirection = sortOrder.toLowerCase() === 'asc' ? 'ASC' : 'DESC';
    
    // Get total count for pagination
    const countQuery = `
      SELECT COUNT(*) as total
      FROM clients c
      LEFT JOIN client_plans cp ON c.id = cp.client_id
      LEFT JOIN plans p ON cp.plan_id = p.id
      ${whereClause}
    `;
    const countResult = await client.query(countQuery, queryParams);
    const totalCount = parseInt(countResult.rows[0].total);
    
    // Get paginated results - using database-agnostic date formatting
    const dataQuery = `
      SELECT 
        c.id,
        c.name,
        c.email,
        c.phone,
        c.address,
        c.status,
        c.payment_status,
        c.balance,
        c.created_at,
        c.updated_at,
        c.installation_date,
        c.due_date,
        cp.plan_id,
        p.name as plan_name
      FROM clients c
      LEFT JOIN client_plans cp ON c.id = cp.client_id AND cp.status = 'active'
      LEFT JOIN plans p ON cp.plan_id = p.id
      ${whereClause}
      ORDER BY c.${sortColumn} ${sortDirection}
      LIMIT $${paramCount + 1} OFFSET $${paramCount + 2}
    `;
    
    queryParams.push(limit, offset);
    const result = await client.query(dataQuery, queryParams);
    
    client.release();
    
    res.json({ 
      success: true, 
      clients: result.rows,
      pagination: {
        currentPage: parseInt(page),
        totalPages: Math.ceil(totalCount / limit),
        totalCount: totalCount,
        limit: parseInt(limit)
      }
    });
  } catch (error) {
    console.error('Error in clients GET route:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Create new client
router.post('/', authenticateToken, async (req, res) => {
  try {
    const { name, email, phone, address, installation_date, due_date, payment_status = 'unpaid', status = 'active', balance = 0 } = req.body;
    if (!name) {
      return res.status(400).json({ error: 'Name is required' });
    }
    const client = await pool.connect();
    const result = await client.query(
      'INSERT INTO clients (name, email, phone, address, installation_date, due_date, payment_status, status, balance) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9) RETURNING *',
      [name, email || null, phone || null, address, formatDateForDB(installation_date), formatDateForDB(due_date), payment_status, status, balance]
    );
    client.release();
    res.json({ success: true, client: formatClientDates(result.rows[0]) });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update client by ID
router.put('/:id', authenticateToken, async (req, res) => {
  try {
    const clientId = req.params.id;
    const { name, email, phone, address, installation_date, due_date, payment_status, status, balance } = req.body;
    const client = await pool.connect();
    const result = await client.query(
      'UPDATE clients SET name = $1, email = $2, phone = $3, address = $4, installation_date = $5, due_date = $6, payment_status = $7, status = $8, balance = $9, updated_at = CURRENT_TIMESTAMP WHERE id = $10 RETURNING *',
      [name, email || null, phone || null, address, formatDateForDB(installation_date), formatDateForDB(due_date), payment_status, status, balance || 0, clientId]
    );
    client.release();
    if (result.rowCount === 0) {
      return res.status(404).json({ error: 'Client not found' });
    }
    res.json({ success: true, updated: formatClientDates(result.rows[0]) });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Delete client by ID
router.delete('/:id', authenticateToken, async (req, res) => {
  try {
    const clientId = req.params.id;
    const client = await pool.connect();
    const result = await client.query('DELETE FROM clients WHERE id = $1 RETURNING *', [clientId]);
    client.release();
    if (result.rowCount === 0) {
      return res.status(404).json({ error: 'Client not found' });
    }
    res.json({ success: true, deleted: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

module.exports = router;
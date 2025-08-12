const express = require('express');
const pool = require('../config/database');
const { authenticateToken } = require('../middleware/auth');

const router = express.Router();

// Get all tickets with pagination and filtering
router.get('/', authenticateToken, async (req, res) => {
  try {
    const { 
      page = 1, 
      limit = 25, 
      sort = 'created_at', 
      order = 'desc',
      status,
      priority,
      category,
      assigned_to,
      client_id,
      search
    } = req.query;

    const offset = (page - 1) * limit;
    const sortColumn = ['id', 'ticket_number', 'title', 'priority', 'category', 'status', 'created_at', 'updated_at'].includes(sort) ? sort : 'created_at';
    const sortDirection = order.toLowerCase() === 'asc' ? 'ASC' : 'DESC';

    let whereClause = 'WHERE 1=1';
    let queryParams = [];
    let paramCount = 0;

    if (status) {
      paramCount++;
      whereClause += ` AND t.status = $${paramCount}`;
      queryParams.push(status);
    }

    if (priority) {
      paramCount++;
      whereClause += ` AND t.priority = $${paramCount}`;
      queryParams.push(priority);
    }

    if (category) {
      paramCount++;
      whereClause += ` AND t.category = $${paramCount}`;
      queryParams.push(category);
    }

    if (assigned_to) {
      paramCount++;
      whereClause += ` AND t.assigned_to = $${paramCount}`;
      queryParams.push(assigned_to);
    }

    if (client_id) {
      paramCount++;
      whereClause += ` AND t.client_id = $${paramCount}`;
      queryParams.push(client_id);
    }

    if (search) {
      paramCount++;
      whereClause += ` AND (t.title LIKE $${paramCount} OR t.description LIKE $${paramCount} OR t.ticket_number LIKE $${paramCount})`;
      queryParams.push(`%${search}%`);
    }

    const client = await pool.connect();

    // Get total count
    const countQuery = `
      SELECT COUNT(*) as total
      FROM tickets t
      LEFT JOIN clients c ON t.client_id = c.id
      LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.id
      LEFT JOIN users u_created ON t.created_by = u_created.id
      ${whereClause}
    `;
    const countResult = await client.query(countQuery, queryParams);
    const total = parseInt(countResult.rows[0].total);

    // Get paginated data
    const dataQuery = `
      SELECT 
        t.*,
        c.name as client_name,
        u_assigned.username as assigned_to_name,
        u_created.username as created_by_name
      FROM tickets t
      LEFT JOIN clients c ON t.client_id = c.id
      LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.id
      LEFT JOIN users u_created ON t.created_by = u_created.id
      ${whereClause}
      ORDER BY t.${sortColumn} ${sortDirection}
      LIMIT $${paramCount + 1} OFFSET $${paramCount + 2}
    `;

    queryParams.push(limit, offset);
    const result = await client.query(dataQuery, queryParams);

    client.release();

    res.json({ 
      success: true, 
      tickets: result.rows,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total: total,
        pages: Math.ceil(total / limit)
      }
    });
  } catch (error) {
    console.error('Error fetching tickets:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Get ticket statistics
router.get('/stats', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    
    const result = await client.query(`
      SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'open' THEN 1 END) as open,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
        COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved,
        COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed,
        COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority,
        COUNT(CASE WHEN priority = 'medium' THEN 1 END) as medium_priority,
        COUNT(CASE WHEN priority = 'low' THEN 1 END) as low_priority
      FROM tickets
    `);
    
    client.release();
    res.json({ success: true, stats: result.rows[0] });
  } catch (error) {
    console.error('Error fetching ticket stats:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Get single ticket by ID
router.get('/:id', authenticateToken, async (req, res) => {
  try {
    const ticketId = req.params.id;
    const client = await pool.connect();
    
    const result = await client.query(`
      SELECT 
        t.*,
        c.name as client_name,
        c.email as client_email,
        c.phone as client_phone,
        u_assigned.username as assigned_to_name,
        u_created.username as created_by_name
      FROM tickets t
      LEFT JOIN clients c ON t.client_id = c.id
      LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.id
      LEFT JOIN users u_created ON t.created_by = u_created.id
      WHERE t.id = $1
    `, [ticketId]);
    
    client.release();
    
    if (result.rows.length === 0) {
      return res.status(404).json({ success: false, error: 'Ticket not found' });
    }
    
    res.json({ success: true, ticket: result.rows[0] });
  } catch (error) {
    console.error('Error fetching ticket:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Create new ticket
router.post('/', authenticateToken, async (req, res) => {
  try {
    const { title, description, client_id, priority = 'medium', category = 'general', assigned_to, due_date } = req.body;
    const created_by = req.user.userId;
    
    if (!title) {
      return res.status(400).json({ success: false, error: 'Title is required' });
    }
    
    const client = await pool.connect();
    
    // Generate ticket number with random string like existing tickets
    const randomString = () => Math.random().toString(36).substring(2, 10).toUpperCase();
    const randomId = () => Math.random().toString(36).substring(2, 5).toUpperCase();
    const ticketNumber = `TKT-${randomString()}-${randomId()}`;
    
    const result = await client.query(
      `INSERT INTO tickets 
       (ticket_number, title, description, client_id, priority, category, assigned_to, created_by, due_date)
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9) 
       RETURNING *`,
      [ticketNumber, title, description, client_id, priority, category, assigned_to, created_by, due_date]
    );
    
    // Log ticket creation in history
    await client.query(
      'INSERT INTO ticket_history (ticket_id, user_id, action, description) VALUES ($1, $2, $3, $4)',
      [result.rows[0].id, created_by, 'created', 'Ticket created']
    );
    
    client.release();
    res.json({ success: true, ticket: result.rows[0] });
  } catch (error) {
    console.error('Error creating ticket:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Update ticket
router.put('/:id', authenticateToken, async (req, res) => {
  const dbClient = await pool.connect();
  try {
    const ticketId = req.params.id;
    let { title, description, client_id, priority, category, status, assigned_to, resolution, due_date } = req.body;
    const updated_by = req.user.userId;
    
    // Convert empty strings to null for integer fields
    if (client_id === '') client_id = null;
    if (assigned_to === '') assigned_to = null;
    if (description === '') description = null;
    if (resolution === '') resolution = null;
    if (due_date === '') due_date = null;
    
    // Handle legacy "admin" value for assigned_to
    if (assigned_to === 'admin') {
      // Get the admin user's ID
      const adminResult = await dbClient.query('SELECT id FROM users WHERE username = $1', ['admin']);
      if (adminResult.rows.length > 0) {
        assigned_to = adminResult.rows[0].id;
      } else {
        assigned_to = null;
      }
    }
    
    // Get current ticket data for history
    const currentResult = await dbClient.query('SELECT * FROM tickets WHERE id = $1', [ticketId]);
    if (currentResult.rows.length === 0) {
      dbClient.release();
      return res.status(404).json({ success: false, error: 'Ticket not found' });
    }
    
    const currentTicket = currentResult.rows[0];
    
    // Update ticket
    const updateFields = [];
    const updateValues = [];
    let paramCount = 0;
    
    if (title !== undefined) {
      paramCount++;
      updateFields.push(`title = $${paramCount}`);
      updateValues.push(title);
    }
    
    if (description !== undefined) {
      paramCount++;
      updateFields.push(`description = $${paramCount}`);
      updateValues.push(description);
    }
    
    if (client_id !== undefined) {
      paramCount++;
      updateFields.push(`client_id = $${paramCount}`);
      updateValues.push(client_id);
    }
    
    if (priority !== undefined) {
      paramCount++;
      updateFields.push(`priority = $${paramCount}`);
      updateValues.push(priority);
    }
    
    if (category !== undefined) {
      paramCount++;
      updateFields.push(`category = $${paramCount}`);
      updateValues.push(category);
    }
    
    if (status !== undefined) {
      paramCount++;
      updateFields.push(`status = $${paramCount}`);
      updateValues.push(status);
      
      if (status === 'resolved' || status === 'closed') {
        paramCount++;
        updateFields.push(`resolved_at = $${paramCount}`);
        updateValues.push(new Date());
      }
    }
    
    if (assigned_to !== undefined) {
      paramCount++;
      updateFields.push(`assigned_to = $${paramCount}`);
      updateValues.push(assigned_to);
    }
    
    if (resolution !== undefined) {
      paramCount++;
      updateFields.push(`resolution = $${paramCount}`);
      updateValues.push(resolution);
    }
    
    if (due_date !== undefined) {
      paramCount++;
      updateFields.push(`due_date = $${paramCount}`);
      updateValues.push(due_date);
    }
    
    paramCount++;
    updateFields.push(`updated_at = $${paramCount}`);
    updateValues.push(new Date());
    
    paramCount++;
    updateValues.push(ticketId);
    
    const updateQuery = `
      UPDATE tickets 
      SET ${updateFields.join(', ')}
      WHERE id = $${paramCount}
      RETURNING *
    `;
    
    const result = await dbClient.query(updateQuery, updateValues);
    
    // Log changes in history
    const changes = [];
    if (status !== undefined && status !== currentTicket.status) {
      changes.push(`Status changed from '${currentTicket.status}' to '${status}'`);
    }
    if (priority !== undefined && priority !== currentTicket.priority) {
      changes.push(`Priority changed from '${currentTicket.priority}' to '${priority}'`);
    }
    if (assigned_to !== undefined && assigned_to !== currentTicket.assigned_to) {
      changes.push(`Assignment changed`);
    }
    
    if (changes.length > 0) {
      await dbClient.query(
        'INSERT INTO ticket_history (ticket_id, user_id, action, description) VALUES ($1, $2, $3, $4)',
        [ticketId, updated_by, 'updated', changes.join('; ')]
      );
    }
    
    dbClient.release();
    res.json({ success: true, ticket: result.rows[0] });
  } catch (error) {
    console.error('Error updating ticket:', error);
    dbClient.release();
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Delete ticket
router.delete('/:id', authenticateToken, async (req, res) => {
  try {
    const ticketId = req.params.id;
    const client = await pool.connect();
    
    const result = await client.query('DELETE FROM tickets WHERE id = $1 RETURNING *', [ticketId]);
    
    if (result.rows.length === 0) {
      client.release();
      return res.status(404).json({ success: false, error: 'Ticket not found' });
    }
    
    client.release();
    res.json({ success: true, deleted: result.rows[0] });
  } catch (error) {
    console.error('Error deleting ticket:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Get ticket comments
router.get('/:id/comments', authenticateToken, async (req, res) => {
  try {
    const ticketId = req.params.id;
    const client = await pool.connect();
    
    const result = await client.query(`
      SELECT 
        tc.*,
        u.username as user_name
      FROM ticket_comments tc
      LEFT JOIN users u ON tc.user_id = u.id
      WHERE tc.ticket_id = $1
      ORDER BY tc.created_at ASC
    `, [ticketId]);
    
    client.release();
    res.json({ success: true, comments: result.rows });
  } catch (error) {
    console.error('Error fetching comments:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Add ticket comment
router.post('/:id/comments', authenticateToken, async (req, res) => {
  try {
    const ticketId = req.params.id;
    const { comment, is_internal = false } = req.body;
    const user_id = req.user.userId;
    
    if (!comment) {
      return res.status(400).json({ success: false, error: 'Comment is required' });
    }
    
    const client = await pool.connect();
    
    const result = await client.query(
      'INSERT INTO ticket_comments (ticket_id, user_id, comment, is_internal) VALUES ($1, $2, $3, $4) RETURNING *',
      [ticketId, user_id, comment, is_internal]
    );
    
    // Log comment in history
    await client.query(
      'INSERT INTO ticket_history (ticket_id, user_id, action, description) VALUES ($1, $2, $3, $4)',
      [ticketId, user_id, 'commented', is_internal ? 'Internal comment added' : 'Comment added']
    );
    
    client.release();
    res.json({ success: true, comment: result.rows[0] });
  } catch (error) {
    console.error('Error adding comment:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Get ticket history
router.get('/:id/history', authenticateToken, async (req, res) => {
  try {
    const ticketId = req.params.id;
    const client = await pool.connect();
    
    const result = await client.query(`
      SELECT 
        th.*,
        u.username as user_name
      FROM ticket_history th
      LEFT JOIN users u ON th.user_id = u.id
      WHERE th.ticket_id = $1
      ORDER BY th.created_at DESC
    `, [ticketId]);
    
    client.release();
    res.json({ success: true, history: result.rows });
  } catch (error) {
    console.error('Error fetching history:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

module.exports = router;
const express = require('express');
const cors = require('cors');
const { Pool } = require('pg');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const RouterOSAPI = require('routeros-api').RouterOSAPI;

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());

// Helper function to handle date formatting for PostgreSQL
function formatDateForDB(dateString) {
  if (!dateString) return null;
  // Return the date string as-is to avoid timezone conversion
  return dateString;
}

// Helper function to format dates in responses
function formatClientDates(client) {
  if (!client) return client;
  
  if (client.installation_date && typeof client.installation_date === 'string') {
    if (client.installation_date.includes('T')) {
      client.installation_date = client.installation_date.split('T')[0];
    }
  }
  if (client.due_date && typeof client.due_date === 'string') {
    if (client.due_date.includes('T')) {
      client.due_date = client.due_date.split('T')[0];
    }
  }
  return client;
}

// Serve static files from the public directory
const path = require('path');
app.use(express.static(path.join(__dirname, '../public')));

// Database configuration
const pool = new Pool({
  connectionString: process.env.DATABASE_URL || "postgresql://neondb_owner:npg_4ZPlK1gJEbeo@ep-dark-brook-ae1ictl5-pooler.c-2.us-east-2.aws.neon.tech/neondb?sslmode=require&channel_binding=require",
  ssl: {
    rejectUnauthorized: false
  }
});

// JWT secret (in production, use environment variable)
const JWT_SECRET = process.env.JWT_SECRET || 'your-secret-key';

// Health check endpoint
app.get('/api/health', async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT NOW()');
    client.release();
    res.json({ 
      status: 'ok', 
      timestamp: result.rows[0].now,
      message: 'Database connection successful'
    });
  } catch (error) {
    res.status(500).json({ 
      status: 'error', 
      message: 'Database connection failed',
      error: error.message 
    });
  }
});


// Create ticketing system tables
async function createTicketingTables() {
  const client = await pool.connect();
  
  try {
    // Create tickets table
    await client.query(`
      CREATE TABLE IF NOT EXISTS tickets (
        id SERIAL PRIMARY KEY,
        ticket_number VARCHAR(20) UNIQUE NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        client_id INTEGER REFERENCES clients(id) ON DELETE SET NULL,
        priority VARCHAR(20) DEFAULT 'medium', -- 'low', 'medium', 'high', 'urgent'
        category VARCHAR(50) DEFAULT 'general', -- 'technical', 'billing', 'general', 'installation', 'maintenance'
        status VARCHAR(20) DEFAULT 'open', -- 'open', 'in_progress', 'resolved', 'closed', 'pending'
        assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
        created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
        resolution TEXT,
        resolved_at TIMESTAMP,
        due_date TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create ticket comments table
    await client.query(`
      CREATE TABLE IF NOT EXISTS ticket_comments (
        id SERIAL PRIMARY KEY,
        ticket_id INTEGER REFERENCES tickets(id) ON DELETE CASCADE,
        user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
        comment TEXT NOT NULL,
        is_internal BOOLEAN DEFAULT false,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create ticket attachments table
    await client.query(`
      CREATE TABLE IF NOT EXISTS ticket_attachments (
        id SERIAL PRIMARY KEY,
        ticket_id INTEGER REFERENCES tickets(id) ON DELETE CASCADE,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_size INTEGER,
        mime_type VARCHAR(100),
        uploaded_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create ticket history table for tracking status changes
    await client.query(`
      CREATE TABLE IF NOT EXISTS ticket_history (
        id SERIAL PRIMARY KEY,
        ticket_id INTEGER REFERENCES tickets(id) ON DELETE CASCADE,
        user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
        action VARCHAR(50) NOT NULL, -- 'created', 'status_changed', 'assigned', 'commented', 'resolved'
        old_value TEXT,
        new_value TEXT,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
  } finally {
    client.release();
  }
}

async function insertSampleData(client) {
  // Remove sample plans insertion
  /*
  const plans = [
    { name: 'Basic Plan', description: 'Basic internet plan', price: 29.99, speed: '50 Mbps' },
    { name: 'Standard Plan', description: 'Standard internet plan', price: 49.99, speed: '100 Mbps' },
    { name: 'Premium Plan', description: 'Premium internet plan', price: 79.99, speed: '200 Mbps' }
  ];

  for (const plan of plans) {
    const exists = await client.query('SELECT id FROM plans WHERE name = $1', [plan.name]);
    if (exists.rows.length === 0) {
      await client.query(
        'INSERT INTO plans (name, description, price, speed) VALUES ($1, $2, $3, $4)',
        [plan.name, plan.description, plan.price, plan.speed]
      );
    }
  }
  */

  // Remove sample clients insertion
  /*
  const clients = [
    { name: 'John Doe', email: 'john@example.com', phone: '555-0101', address: '123 Main St' },
    { name: 'Jane Smith', email: 'jane@example.com', phone: '555-0102', address: '456 Oak Ave' }
  ];

  for (const client_data of clients) {
    const exists = await client.query('SELECT id FROM clients WHERE email = $1', [client_data.email]);
    if (exists.rows.length === 0) {
      await client.query(
        'INSERT INTO clients (name, email, phone, address) VALUES ($1, $2, $3, $4)',
        [client_data.name, client_data.email, client_data.phone, client_data.address]
      );
    }
  }
  */

  // Insert sample billings
  try {
    // Get the first client and plan IDs
    const clientResult = await client.query('SELECT id FROM clients LIMIT 1');
    const planResult = await client.query('SELECT id FROM plans LIMIT 1');
    
    if (clientResult.rows.length > 0 && planResult.rows.length > 0) {
      const clientId = clientResult.rows[0].id;
      const planId = planResult.rows[0].id;
      
      // Check if billing already exists
      const billingExists = await client.query('SELECT id FROM billings WHERE client_id = $1 AND plan_id = $2', [clientId, planId]);
      if (billingExists.rows.length === 0) {
        await client.query(
          'INSERT INTO billings (client_id, plan_id, amount, due_date, status) VALUES ($1, $2, $3, $4, $5)',
          [clientId, planId, 49.99, '2024-02-01', 'pending']
        );
      }
    }
  } catch (error) {
  }

}

// Extract database initialization logic into reusable function
async function initializeDatabaseTables() {
  const client = await pool.connect();
  
  try {
    // Create users table
    await client.query(`
      CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        role VARCHAR(20) DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create clients table
    await client.query(`
      CREATE TABLE IF NOT EXISTS clients (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        address TEXT,
        installation_date DATE,
        due_date DATE,
        payment_status VARCHAR(20) DEFAULT 'unpaid',
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    
    // Add new columns to existing clients table if they don't exist
    await client.query(`
      ALTER TABLE clients 
      ADD COLUMN IF NOT EXISTS installation_date DATE,
      ADD COLUMN IF NOT EXISTS due_date DATE,
      ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'unpaid',
      ADD COLUMN IF NOT EXISTS email VARCHAR(100),
      ADD COLUMN IF NOT EXISTS phone VARCHAR(50),
      ADD COLUMN IF NOT EXISTS balance DECIMAL(10,2) DEFAULT 0.00
    `);

    // Create plans table
    await client.query(`
      CREATE TABLE IF NOT EXISTS plans (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        speed VARCHAR(50),
        download_speed INTEGER,
        upload_speed INTEGER,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create client_plans table (replacing subscriptions)
    await client.query(`
      CREATE TABLE IF NOT EXISTS client_plans (
        id SERIAL PRIMARY KEY,
        client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
        plan_id INTEGER REFERENCES plans(id) ON DELETE CASCADE,
        start_date DATE NOT NULL DEFAULT CURRENT_DATE,
        end_date DATE,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(client_id, plan_id)
      )
    `);

    // Remove anchor_day column from client_plans if it exists
    try {
      await client.query('ALTER TABLE client_plans DROP COLUMN IF EXISTS anchor_day');
    } catch (error) {
      // Column may not exist, ignore error
    }

    // Create billings table
    await client.query(`
      CREATE TABLE IF NOT EXISTS billings (
        id SERIAL PRIMARY KEY,
        client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
        plan_id INTEGER REFERENCES plans(id) ON DELETE CASCADE,
        amount DECIMAL(10,2) NOT NULL,
        billing_date DATE NOT NULL DEFAULT CURRENT_DATE,
        due_date DATE NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        balance DECIMAL(10,2) DEFAULT 0.00,
        paid_amount DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Add new columns to existing billings table if they don't exist
    try {
      await client.query(`
        ALTER TABLE billings 
        ADD COLUMN IF NOT EXISTS balance DECIMAL(10,2) DEFAULT 0.00,
        ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(10,2) DEFAULT 0.00
      `);
    } catch (error) {
    }

    // Create payments table
    await client.query(`
      CREATE TABLE IF NOT EXISTS payments (
        id SERIAL PRIMARY KEY,
        client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
        amount DECIMAL(10,2) NOT NULL,
        payment_date DATE NOT NULL DEFAULT CURRENT_DATE,
        method VARCHAR(50) DEFAULT 'cash',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Add client_id column to existing payments table if it doesn't exist
    try {
      await client.query(`
        ALTER TABLE payments 
        ADD COLUMN IF NOT EXISTS client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE
      `);
    } catch (error) {
    }

    // Remove billing_id constraint and column from payments table if it exists
    try {
      await client.query('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_billing_id_fkey');
      await client.query('ALTER TABLE payments DROP COLUMN IF EXISTS billing_id');
    } catch (error) {
    }

    // Create MikroTik settings table
    await client.query(`
      CREATE TABLE IF NOT EXISTS mikrotik_settings (
        id SERIAL PRIMARY KEY,
        host VARCHAR(255) NOT NULL,
        username VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        port INTEGER DEFAULT 8728,
        is_active BOOLEAN DEFAULT true,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create company info table
    await client.query(`
      CREATE TABLE IF NOT EXISTS company_info (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        address TEXT,
        phone VARCHAR(50),
        email VARCHAR(100),
        logo_url TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create monitoring groups table
    await client.query(`
      CREATE TABLE IF NOT EXISTS monitoring_groups (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        max_members INTEGER,
        accounts JSONB DEFAULT '[]',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create monitoring categories table
    await client.query(`
      CREATE TABLE IF NOT EXISTS monitoring_categories (
        id SERIAL PRIMARY KEY,
        category_name VARCHAR(255) NOT NULL,
        subcategory_name VARCHAR(255),
        group_ids JSONB DEFAULT '[]',
        category_index INTEGER DEFAULT 0,
        subcategory_index INTEGER DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    
    // Add missing columns if table already exists
    await client.query(`
      ALTER TABLE monitoring_categories 
      ADD COLUMN IF NOT EXISTS group_ids JSONB DEFAULT '[]'
    `);
    await client.query(`
      ALTER TABLE monitoring_categories 
      ADD COLUMN IF NOT EXISTS category_index INTEGER DEFAULT 0
    `);
    await client.query(`
      ALTER TABLE monitoring_categories 
      ADD COLUMN IF NOT EXISTS subcategory_index INTEGER DEFAULT 0
    `);
    
    // Rename groups column to group_ids if it exists
    await client.query(`
      ALTER TABLE monitoring_categories 
      RENAME COLUMN groups TO group_ids
    `).catch(() => {
      // Column already renamed or doesn't exist
    });

    // Create inventory categories table
    await client.query(`
      CREATE TABLE IF NOT EXISTS inventory_categories (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create suppliers table
    await client.query(`
      CREATE TABLE IF NOT EXISTS suppliers (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        contact_person VARCHAR(100),
        email VARCHAR(100),
        phone VARCHAR(20),
        address TEXT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create inventory items table
    await client.query(`
      CREATE TABLE IF NOT EXISTS inventory_items (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        category_id INTEGER REFERENCES inventory_categories(id) ON DELETE SET NULL,
        supplier_id INTEGER REFERENCES suppliers(id) ON DELETE SET NULL,
        sku VARCHAR(50) UNIQUE,
        unit_price DECIMAL(10,2) DEFAULT 0.00,
        quantity_in_stock INTEGER DEFAULT 0,
        minimum_stock_level INTEGER DEFAULT 0,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create inventory assignments table
    await client.query(`
      CREATE TABLE IF NOT EXISTS inventory_assignments (
        id SERIAL PRIMARY KEY,
        item_id INTEGER REFERENCES inventory_items(id) ON DELETE CASCADE,
        client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
        quantity INTEGER NOT NULL,
        assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        installation_address TEXT,
        notes TEXT,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create stock movements table
    await client.query(`
      CREATE TABLE IF NOT EXISTS stock_movements (
        id SERIAL PRIMARY KEY,
        item_id INTEGER REFERENCES inventory_items(id) ON DELETE CASCADE,
        movement_type VARCHAR(20) NOT NULL, -- 'in', 'out', 'adjustment'
        quantity INTEGER NOT NULL,
        reason VARCHAR(100), -- 'purchase', 'assignment', 'return', 'adjustment', 'damaged'
        reference_id INTEGER, -- Reference to client_id for assignments
        unit_cost DECIMAL(10,2),
        notes TEXT,
        movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create default admin user if not exists
    const hashedPassword = await bcrypt.hash('admin123', 10);
    await client.query(`
      INSERT INTO users (username, password, email, role) 
      VALUES ($1, $2, $3, $4) 
      ON CONFLICT (username) DO NOTHING
    `, ['admin', hashedPassword, 'admin@localhost', 'admin']);

  } finally {
    client.release();
  }
}

// Authentication routes
app.post('/api/auth/login', async (req, res) => {
  try {
    const { username, password } = req.body;

    if (!username || !password) {
      return res.status(400).json({ error: 'Username and password are required' });
    }

    const client = await pool.connect();
    const result = await client.query('SELECT * FROM users WHERE username = $1', [username]);
    client.release();

    if (result.rows.length === 0) {
      return res.status(401).json({ error: 'Invalid username or password' });
    }

    const user = result.rows[0];
    const isValidPassword = await bcrypt.compare(password, user.password);

    if (!isValidPassword) {
      return res.status(401).json({ error: 'Invalid username or password' });
    }

    // Generate JWT token
    const token = jwt.sign(
      { 
        userId: user.id, 
        username: user.username, 
        role: user.role 
      },
      JWT_SECRET,
      { expiresIn: '24h' }
    );

    res.json({
      success: true,
      token,
      user: {
        id: user.id,
        username: user.username,
        email: user.email,
        role: user.role
      }
    });
  } catch (error) {
      message: error.message,
      stack: error.stack,
      code: error.code,
      detail: error.detail
    });
    res.status(500).json({ error: 'Internal server error', details: error.message });
  }
});

// Middleware to verify JWT token
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];

  if (!token) {
    return res.status(401).json({ error: 'Access token required' });
  }

  jwt.verify(token, JWT_SECRET, (err, user) => {
    if (err) {
      return res.status(403).json({ error: 'Invalid token' });
    }
    req.user = user;
    next();
  });
};

// Get current user
app.get('/api/auth/me', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT id, username, email, role FROM users WHERE id = $1', [req.user.userId]);
    client.release();

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'User not found' });
    }

    res.json({ user: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// API routes for data
app.get('/api/clients', authenticateToken, async (req, res) => {
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
      whereConditions.push(`c.name ILIKE $${paramCount}`);
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
    
    // Get paginated results
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
        TO_CHAR(c.installation_date, 'YYYY-MM-DD') as installation_date,
        TO_CHAR(c.due_date, 'YYYY-MM-DD') as due_date,
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
    res.status(500).json({ error: 'Internal server error' });
  }
});


// Create new client
app.post('/api/clients', authenticateToken, async (req, res) => {
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
app.put('/api/clients/:id', authenticateToken, async (req, res) => {
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
app.delete('/api/clients/:id', authenticateToken, async (req, res) => {
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


app.get('/api/plans', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT * FROM plans ORDER BY created_at DESC');
    client.release();
    res.json(result.rows);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

app.post('/api/plans', authenticateToken, async (req, res) => {
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

app.put('/api/plans/:id', authenticateToken, async (req, res) => {
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

app.delete('/api/plans/:id', authenticateToken, async (req, res) => {
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

// Client Plans API endpoints
app.get('/api/client-plans/:clientId', authenticateToken, async (req, res) => {
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

app.post('/api/client-plans', authenticateToken, async (req, res) => {
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

app.delete('/api/client-plans/:id', authenticateToken, async (req, res) => {
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

app.get('/api/client-plans-count', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT COUNT(*) as count FROM client_plans WHERE status = $1', ['active']);
    client.release();
    res.json({ count: parseInt(result.rows[0].count) });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

app.get('/api/client-plans-all', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT cp.client_id, cp.plan_id, cp.status, p.name as plan_name
      FROM client_plans cp
      JOIN plans p ON cp.plan_id = p.id
      WHERE cp.status = 'active'
      ORDER BY cp.client_id
    `);
    client.release();
    res.json(result.rows);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

app.get('/api/billings', authenticateToken, async (req, res) => {
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
        -- Calculate billing period (1 month before due date)
        TO_CHAR(b.due_date - INTERVAL '1 month' + INTERVAL '1 day', 'Mon DD, YYYY') as period_start,
        TO_CHAR(b.due_date, 'Mon DD, YYYY') as period_end,
        TO_CHAR(b.due_date - INTERVAL '1 month' + INTERVAL '1 day', 'Mon/DD/YYYY') || ' - ' || TO_CHAR(b.due_date, 'Mon/DD/YYYY') as period,
        EXTRACT(MONTH FROM b.due_date) as month,
        EXTRACT(YEAR FROM b.due_date) as year,
        -- Calculate previous payments (payments made between last billing and this billing)
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
app.post('/api/billings', authenticateToken, async (req, res) => {
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
    
    // Get the total amount due from the most recent billing for this client using correct calculation
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
app.get('/api/billings/:id', authenticateToken, async (req, res) => {
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

app.put('/api/billings/:id', authenticateToken, async (req, res) => {
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
    
    // Recalculate client balance after billing update (in case amount changed)
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
// Helper function to recalculate client balance
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
  
  // Update client balance and payment status (and due_date if provided)
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
    totalOwed,
    totalPaid,
    calculatedBalance,
    clientPaymentStatus
  };
}

// Helper function to automatically pay unpaid billings when client has sufficient credit
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
    
    let availableCredit;
    
    if (clientBalance < 0) {
      // Client has credit (negative balance)
      availableCredit = Math.abs(clientBalance);
    } else {
      // Client has exactly 0 balance - use simple chronological payment distribution
      const totalPaymentsResult = await dbClient.query(
        'SELECT COALESCE(SUM(amount), 0) as total_payments FROM payments WHERE client_id = $1',
        [client_id]
      );
      const totalPayments = parseFloat(totalPaymentsResult.rows[0].total_payments);
      
      const unpaidBillingsResult = await dbClient.query(`
        SELECT id, amount, created_at
        FROM billings 
        WHERE client_id = $1 AND status != 'paid'
        ORDER BY created_at ASC
      `, [client_id]);
      
      // Distribute total payments chronologically across unpaid billings
      let remainingPayments = totalPayments;
      const billingsToUpdate = [];
      
      
      for (const billing of unpaidBillingsResult.rows) {
        const billingAmount = parseFloat(billing.amount);
        
        if (remainingPayments >= billingAmount) {
          billingsToUpdate.push(billing.id);
          remainingPayments -= billingAmount;
        } else {
          break; // Not enough payments to cover this billing
        }
      }
      
      if (billingsToUpdate.length > 0) {
        await dbClient.query(
          'UPDATE billings SET status = $1, updated_at = CURRENT_TIMESTAMP WHERE id = ANY($2)',
          ['paid', billingsToUpdate]
        );
        await recalculateClientBalance(client_id, dbClient);
      }
      return; // Exit early for zero balance case
    }
    
    // Get unpaid billings ordered by creation date (oldest first) for credit-based payment
    const unpaidBillingsResult = await dbClient.query(`
      SELECT id, amount, created_at
      FROM billings 
      WHERE client_id = $1 AND status != 'paid'
      ORDER BY created_at ASC
    `, [client_id]);
    
    // Pay billings chronologically with available credit
    let remainingCredit = availableCredit;
    const billingsToUpdate = [];
    
    for (const billing of unpaidBillingsResult.rows) {
      const billingAmount = parseFloat(billing.amount);
      
      if (remainingCredit >= billingAmount) {
        // This billing can be fully paid with available credit
        billingsToUpdate.push(billing.id);
        remainingCredit -= billingAmount;
      } else {
        // Not enough credit left to cover this billing
        break; // Stop here as subsequent billings also cannot be paid
      }
    }
    
    // Update billing statuses to 'paid'
    if (billingsToUpdate.length > 0) {
      await dbClient.query(
        'UPDATE billings SET status = $1, updated_at = CURRENT_TIMESTAMP WHERE id = ANY($2)',
        ['paid', billingsToUpdate]
      );
      
      // Recalculate client balance after auto-payment
      await recalculateClientBalance(client_id, dbClient);
    }
    
  } catch (error) {
    // Don't throw error to avoid breaking the main payment/billing operation
  }
}


app.delete('/api/billings/:id', authenticateToken, async (req, res) => {
  try {
    const billingId = req.params.id;
    const client = await pool.connect();
    
    // Get billing info before deletion
    const billingResult = await client.query('SELECT * FROM billings WHERE id = $1', [billingId]);
    if (billingResult.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Billing not found' });
    }
    
    const deletedBilling = billingResult.rows[0];
    const client_id = deletedBilling.client_id;
    
    // Delete the billing
    await client.query('DELETE FROM billings WHERE id = $1', [billingId]);
    
    // Recalculate client balance after deletion
    const balanceInfo = await recalculateClientBalance(client_id, client);
    
    client.release();
    res.json({ 
      success: true, 
      deleted: deletedBilling,
      client_balance: balanceInfo.calculatedBalance,
      client_payment_status: balanceInfo.clientPaymentStatus
    });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// List all payments
app.get('/api/payments', authenticateToken, async (req, res) => {
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

app.post('/api/payments', authenticateToken, async (req, res) => {
  try {
    const { client_id, amount, payment_date, method, notes } = req.body;
    const paymentAmount = parseFloat(amount);
    
    if (!client_id || paymentAmount <= 0) {
      return res.status(400).json({ error: 'Client ID and valid payment amount are required' });
    }
    
    const client = await pool.connect();
    
    // Verify client exists
    const clientCheck = await client.query('SELECT id FROM clients WHERE id = $1', [client_id]);
    if (clientCheck.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Client not found' });
    }
    
    // Insert payment - simple account payment
    const paymentResult = await client.query(
      'INSERT INTO payments (client_id, amount, payment_date, method, notes) VALUES ($1, $2, $3, $4, $5) RETURNING *',
      [client_id, paymentAmount, payment_date, method, notes]
    );
    
    // Calculate client's current balance dynamically
    const billingsResult = await client.query(
      'SELECT COALESCE(SUM(amount), 0) as total_owed FROM billings WHERE client_id = $1',
      [client_id]
    );
    
    const paymentsResult = await client.query(
      'SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE client_id = $1',
      [client_id]
    );
    
    const totalOwed = parseFloat(billingsResult.rows[0].total_owed);
    const totalPaid = parseFloat(paymentsResult.rows[0].total_paid);
    const calculatedBalance = totalOwed - totalPaid;
    
    // Update client balance and payment status
    const clientPaymentStatus = calculatedBalance > 0 ? 'partial' : 'paid';
    await client.query(
      'UPDATE clients SET balance = $1, payment_status = $2, updated_at = CURRENT_TIMESTAMP WHERE id = $3',
      [calculatedBalance, clientPaymentStatus, client_id]
    );
    
    // Auto-pay unpaid billings if client has sufficient credit
    await autoPayUnpaidBillings(client_id, client);
    
    // Add balance info to the payment record for display
    const paymentWithBalances = {
      ...paymentResult.rows[0],
      prev_balance: totalOwed - totalPaid + paymentAmount, // Balance before this payment
      new_balance: calculatedBalance // Balance after this payment
    };
    
    client.release();
    res.json({ 
      success: true, 
      payment: paymentWithBalances,
      client_balance: calculatedBalance,
      client_payment_status: clientPaymentStatus,
      total_owed: totalOwed,
      total_paid: totalPaid
    });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Delete payment
app.delete('/api/payments/:id', authenticateToken, async (req, res) => {
  try {
    const { id } = req.params;
    const client = await pool.connect();
    
    // First, get the payment details before deleting
    const paymentResult = await client.query('SELECT * FROM payments WHERE id = $1', [id]);
    if (paymentResult.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Payment not found' });
    }
    
    const payment = paymentResult.rows[0];
    const client_id = payment.client_id;
    
    // Delete the payment
    await client.query('DELETE FROM payments WHERE id = $1', [id]);
    
    // Recalculate client's current balance after deletion
    const billingsResult = await client.query(
      'SELECT COALESCE(SUM(amount), 0) as total_owed FROM billings WHERE client_id = $1',
      [client_id]
    );
    
    const paymentsResult = await client.query(
      'SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE client_id = $1',
      [client_id]
    );
    
    const totalOwed = parseFloat(billingsResult.rows[0].total_owed);
    const totalPaid = parseFloat(paymentsResult.rows[0].total_paid);
    const calculatedBalance = totalOwed - totalPaid;
    
    // Update client balance and payment status
    const clientPaymentStatus = calculatedBalance > 0 ? 'partial' : 'paid';
    await client.query(
      'UPDATE clients SET balance = $1, payment_status = $2, updated_at = CURRENT_TIMESTAMP WHERE id = $3',
      [calculatedBalance, clientPaymentStatus, client_id]
    );
    
    client.release();
    res.json({ 
      success: true, 
      message: 'Payment deleted successfully',
      deleted_payment: payment,
      client_balance: calculatedBalance,
      client_payment_status: clientPaymentStatus
    });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// MikroTik API routes
app.get('/api/mikrotik/settings', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT * FROM mikrotik_settings ORDER BY created_at DESC LIMIT 1');
    client.release();
    res.json(result.rows[0] || null);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

app.post('/api/mikrotik/settings', authenticateToken, async (req, res) => {
  try {
    const { host, username, password, port = 8728 } = req.body;
    
    if (!host || !username || !password) {
      return res.status(400).json({ error: 'Host, username, and password are required' });
    }

    const client = await pool.connect();
    
    // Delete existing settings
    await client.query('DELETE FROM mikrotik_settings');
    
    // Insert new settings
    const result = await client.query(
      'INSERT INTO mikrotik_settings (host, username, password, port, is_active) VALUES ($1, $2, $3, $4, $5) RETURNING *',
      [host, username, password, port, true]
    );
    
    client.release();
    res.json({ success: true, settings: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});


app.get('/api/ppp-accounts', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    
    // Get MikroTik settings
    const settingsResult = await client.query('SELECT * FROM mikrotik_settings WHERE is_active = true ORDER BY created_at DESC LIMIT 1');
    
    if (settingsResult.rows.length === 0) {
      client.release();
      return res.status(400).json({ error: 'No active MikroTik settings found' });
    }

    const settings = settingsResult.rows[0];
    
    const connection = new RouterOSAPI({
      host: settings.host,
      user: settings.username,
      password: settings.password,
      port: settings.port
    });

    connection.connect()
      .then(async () => {
        try {
          // Get PPPoE users
          const pppoeUsers = await connection.write('/ppp/secret/print');
          
          connection.close();
          client.release();
          
          res.json({ 
            success: true, 
            accounts: pppoeUsers 
          });
        } catch (error) {
          connection.close();
          client.release();
          throw error;
        }
      })
      .catch((error) => {
        client.release();
        res.status(500).json({ error: 'Failed to fetch PPP accounts', details: error.message });
      });

  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

app.post('/api/import-clients', authenticateToken, async (req, res) => {
  try {
    const { selectedAccounts } = req.body;
    
    if (!selectedAccounts || !Array.isArray(selectedAccounts)) {
      return res.status(400).json({ error: 'Selected accounts are required' });
    }

    const client = await pool.connect();
    const importedClients = [];

    for (const account of selectedAccounts) {
      // Check if client already exists
      const existingClient = await client.query('SELECT id FROM clients WHERE name = $1', [account.name]);
      
      if (existingClient.rows.length === 0) {
        // Import new client
        const newClient = await client.query(
          'INSERT INTO clients (name, email, phone, address, status) VALUES ($1, $2, $3, $4, $5) RETURNING *',
          [account.name, `${account.name}@isp.com`, '', '', 'active']
        );
        importedClients.push(newClient.rows[0]);
      }
    }

    client.release();
    
    res.json({ 
      success: true, 
      message: `Imported ${importedClients.length} new clients`,
      importedClients 
    });

  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

app.get('/api/ppp-profiles', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const settingsResult = await client.query('SELECT * FROM mikrotik_settings WHERE is_active = true ORDER BY created_at DESC LIMIT 1');
    if (settingsResult.rows.length === 0) {
      client.release();
      return res.status(400).json({ error: 'No active MikroTik settings found' });
    }
    const settings = settingsResult.rows[0];
    const connection = new RouterOSAPI({
      host: settings.host,
      user: settings.username,
      password: settings.password,
      port: settings.port
    });
    connection.connect()
      .then(async () => {
        try {
          const profiles = await connection.write('/ppp/profile/print');
          connection.close();
          client.release();
          res.json({ success: true, profiles });
        } catch (error) {
          connection.close();
          client.release();
          throw error;
        }
      })
      .catch((error) => {
        client.release();
        res.status(500).json({ error: 'Failed to fetch PPP profiles', details: error.message });
      });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Add this endpoint after other API routes
app.get('/api/clients-with-plan', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT 
        c.id, c.name, c.address, c.installation_date, c.due_date, c.payment_status, c.status, c.created_at, c.updated_at,
        cp.plan_id, p.name AS plan_name, p.price AS plan_price
      FROM clients c
      LEFT JOIN client_plans cp ON c.id = cp.client_id AND cp.status = 'active'
      LEFT JOIN plans p ON cp.plan_id = p.id
      ORDER BY c.name
    `);
    client.release();
    res.json(result.rows);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error', details: error.message });
  }
});

// Get company info
app.get('/api/company-info', async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT * FROM company_info ORDER BY updated_at DESC LIMIT 1');
    client.release();
    res.json(result.rows[0] || {});
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update company info
app.post('/api/company-info', authenticateToken, async (req, res) => {
  try {
    const { name, address, phone, email, logo_url } = req.body;
    const client = await pool.connect();
    // Upsert: delete all, insert new
    await client.query('DELETE FROM company_info');
    const result = await client.query(
      'INSERT INTO company_info (name, address, phone, email, logo_url, updated_at) VALUES ($1, $2, $3, $4, $5, CURRENT_TIMESTAMP) RETURNING *',
      [name, address, phone, email, logo_url]
    );
    client.release();
    res.json({ success: true, company: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});


// INVENTORY API ENDPOINTS
// Get all inventory categories
app.get('/api/inventory/categories', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT * FROM inventory_categories ORDER BY name');
    client.release();
    res.json({ success: true, categories: result.rows });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Add inventory category
app.post('/api/inventory/categories', authenticateToken, async (req, res) => {
  try {
    const { name, description } = req.body;
    const client = await pool.connect();
    const result = await client.query(
      'INSERT INTO inventory_categories (name, description) VALUES ($1, $2) RETURNING *',
      [name, description]
    );
    client.release();
    res.json({ success: true, category: result.rows[0] });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Update inventory category 
app.put('/api/inventory/categories/:id', authenticateToken, async (req, res) => {
  try {
    const { id } = req.params;
    const { name, description } = req.body;
    const client = await pool.connect();
    const result = await client.query(
      'UPDATE inventory_categories SET name = $1, description = $2, updated_at = CURRENT_TIMESTAMP WHERE id = $3 RETURNING *',
      [name, description, id]
    );
    client.release();
    res.json({ success: true, category: result.rows[0] });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Delete inventory category
app.delete('/api/inventory/categories/:id', authenticateToken, async (req, res) => {
  try {
    const { id } = req.params;
    const client = await pool.connect();
    await client.query('DELETE FROM inventory_categories WHERE id = $1', [id]);
    client.release();
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Get all suppliers
app.get('/api/inventory/suppliers', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT * FROM suppliers ORDER BY name');
    client.release();
    res.json({ success: true, suppliers: result.rows });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Add supplier
app.post('/api/inventory/suppliers', authenticateToken, async (req, res) => {
  try {
    const { name, contact_person, email, phone, address, notes } = req.body;
    const client = await pool.connect();
    const result = await client.query(
      'INSERT INTO suppliers (name, contact_person, email, phone, address, notes) VALUES ($1, $2, $3, $4, $5, $6) RETURNING *',
      [name, contact_person, email, phone, address, notes]
    );
    client.release();
    res.json({ success: true, supplier: result.rows[0] });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Update supplier
app.put('/api/inventory/suppliers/:id', authenticateToken, async (req, res) => {
  try {
    const { id } = req.params;
    const { name, contact_person, email, phone, address, notes } = req.body;
    const client = await pool.connect();
    const result = await client.query(
      'UPDATE suppliers SET name = $1, contact_person = $2, email = $3, phone = $4, address = $5, notes = $6, updated_at = CURRENT_TIMESTAMP WHERE id = $7 RETURNING *',
      [name, contact_person, email, phone, address, notes, id]
    );
    client.release();
    res.json({ success: true, supplier: result.rows[0] });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Delete supplier
app.delete('/api/inventory/suppliers/:id', authenticateToken, async (req, res) => {
  try {
    const { id } = req.params;
    const client = await pool.connect();
    await client.query('DELETE FROM suppliers WHERE id = $1', [id]);
    client.release();
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Get all inventory items with joins
app.get('/api/inventory/items', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT 
        i.*,
        c.name as category_name,
        s.name as supplier_name
      FROM inventory_items i
      LEFT JOIN inventory_categories c ON i.category_id = c.id
      LEFT JOIN suppliers s ON i.supplier_id = s.id
      ORDER BY i.name
    `);
    client.release();
    res.json({ success: true, items: result.rows });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Add inventory item
app.post('/api/inventory/items', authenticateToken, async (req, res) => {
  try {
    const { name, description, category_id, supplier_id, sku, unit_price, quantity_in_stock, minimum_stock_level } = req.body;
    const client = await pool.connect();
    
    const result = await client.query(
      'INSERT INTO inventory_items (name, description, category_id, supplier_id, sku, unit_price, quantity_in_stock, minimum_stock_level) VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING *',
      [name, description, category_id || null, supplier_id || null, sku, unit_price, quantity_in_stock, minimum_stock_level]
    );
    
    // Record initial stock movement if quantity > 0
    if (quantity_in_stock > 0) {
      await client.query(
        'INSERT INTO stock_movements (item_id, movement_type, quantity, reason, unit_cost) VALUES ($1, $2, $3, $4, $5)',
        [result.rows[0].id, 'in', quantity_in_stock, 'initial_stock', unit_price]
      );
    }
    
    client.release();
    res.json({ success: true, item: result.rows[0] });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Update inventory item
app.put('/api/inventory/items/:id', authenticateToken, async (req, res) => {
  try {
    const { id } = req.params;
    const { name, description, category_id, supplier_id, sku, unit_price, minimum_stock_level, status } = req.body;
    const client = await pool.connect();
    
    const result = await client.query(
      'UPDATE inventory_items SET name = $1, description = $2, category_id = $3, supplier_id = $4, sku = $5, unit_price = $6, minimum_stock_level = $7, status = $8, updated_at = CURRENT_TIMESTAMP WHERE id = $9 RETURNING *',
      [name, description, category_id || null, supplier_id || null, sku, unit_price, minimum_stock_level, status, id]
    );
    
    client.release();
    res.json({ success: true, item: result.rows[0] });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Delete inventory item
app.delete('/api/inventory/items/:id', authenticateToken, async (req, res) => {
  try {
    const { id } = req.params;
    const client = await pool.connect();
    await client.query('DELETE FROM inventory_items WHERE id = $1', [id]);
    client.release();
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Adjust inventory stock
app.post('/api/inventory/items/:id/adjust-stock', authenticateToken, async (req, res) => {
  try {
    const { id } = req.params;
    const { quantity, reason, notes } = req.body;
    const client = await pool.connect();
    
    // Get current stock
    const itemResult = await client.query('SELECT quantity_in_stock FROM inventory_items WHERE id = $1', [id]);
    if (itemResult.rows.length === 0) {
      client.release();
      return res.status(404).json({ success: false, error: 'Item not found' });
    }
    
    const currentStock = itemResult.rows[0].quantity_in_stock;
    const newStock = currentStock + quantity;
    
    if (newStock < 0) {
      client.release();
      return res.status(400).json({ success: false, error: 'Insufficient stock' });
    }
    
    // Update stock
    await client.query('UPDATE inventory_items SET quantity_in_stock = $1, updated_at = CURRENT_TIMESTAMP WHERE id = $2', [newStock, id]);
    
    // Record stock movement
    await client.query(
      'INSERT INTO stock_movements (item_id, movement_type, quantity, reason, notes) VALUES ($1, $2, $3, $4, $5)',
      [id, quantity > 0 ? 'in' : 'out', Math.abs(quantity), reason, notes]
    );
    
    client.release();
    res.json({ success: true, new_stock: newStock });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Get inventory assignments
app.get('/api/inventory/assignments', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT 
        a.*,
        i.name as item_name,
        i.sku as item_sku,
        c.name as client_name
      FROM inventory_assignments a
      JOIN inventory_items i ON a.item_id = i.id
      JOIN clients c ON a.client_id = c.id
      ORDER BY a.assigned_date DESC
    `);
    client.release();
    res.json({ success: true, assignments: result.rows });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Assign equipment to client
app.post('/api/inventory/assignments', authenticateToken, async (req, res) => {
  try {
    const { item_id, client_id, quantity, installation_address, notes } = req.body;
    const client = await pool.connect();
    
    // Check available stock
    const itemResult = await client.query('SELECT quantity_in_stock, name FROM inventory_items WHERE id = $1', [item_id]);
    if (itemResult.rows.length === 0) {
      client.release();
      return res.status(404).json({ success: false, error: 'Item not found' });
    }
    
    const currentStock = itemResult.rows[0].quantity_in_stock;
    if (currentStock < quantity) {
      client.release();
      return res.status(400).json({ success: false, error: 'Insufficient stock available' });
    }
    
    // Create assignment
    const assignmentResult = await client.query(
      'INSERT INTO inventory_assignments (item_id, client_id, quantity, installation_address, notes) VALUES ($1, $2, $3, $4, $5) RETURNING *',
      [item_id, client_id, quantity, installation_address, notes]
    );
    
    // Update stock
    await client.query('UPDATE inventory_items SET quantity_in_stock = quantity_in_stock - $1, updated_at = CURRENT_TIMESTAMP WHERE id = $2', [quantity, item_id]);
    
    // Record stock movement
    await client.query(
      'INSERT INTO stock_movements (item_id, movement_type, quantity, reason, reference_id, notes) VALUES ($1, $2, $3, $4, $5, $6)',
      [item_id, 'out', quantity, 'assignment', client_id, notes]
    );
    
    client.release();
    res.json({ success: true, assignment: assignmentResult.rows[0] });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Get stock movements
app.get('/api/inventory/movements', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT 
        m.*,
        i.name as item_name,
        i.sku as item_sku,
        c.name as client_name
      FROM stock_movements m
      JOIN inventory_items i ON m.item_id = i.id
      LEFT JOIN clients c ON m.reference_id = c.id AND m.reason = 'assignment'
      ORDER BY m.movement_date DESC
      LIMIT 500
    `);
    client.release();
    res.json({ success: true, movements: result.rows });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// MONITORING API ENDPOINTS

// Get monitoring dashboard data (using existing MikroTik settings)
app.get('/api/monitoring/dashboard', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    
    // Get active MikroTik settings
    const settingsResult = await client.query('SELECT * FROM mikrotik_settings WHERE is_active = true ORDER BY created_at DESC LIMIT 1');
    if (settingsResult.rows.length === 0) {
      client.release();
      return res.status(400).json({ error: 'No active MikroTik settings found. Please configure MikroTik settings first.' });
    }
    
    const settings = settingsResult.rows[0];
    
    // Connect to MikroTik and get data
    const connection = new RouterOSAPI({
      host: settings.host,
      user: settings.username,
      password: settings.password,
      port: settings.port
    });

    try {
      await connection.connect();
      
      // Get PPP accounts, active connections, and interfaces with statistics
      const [pppAccounts, pppActive, pppoeInterfaces] = await Promise.all([
        connection.write('/ppp/secret/print'),
        connection.write('/ppp/active/print'),
        connection.write('/interface/print', { '?type': 'pppoe-in', 'stats': '' })
      ]);
      
      connection.close();
      
      // Create a map of account profiles for quick lookup
      const accountProfileMap = {};
      pppAccounts.forEach(acc => {
        accountProfileMap[acc.name] = acc.profile;
      });
      
      // Merge profile information into active accounts
      const pppActiveWithProfiles = pppActive.map(activeAcc => ({
        ...activeAcc,
        profile: accountProfileMap[activeAcc.name] || '-'
      }));
      
      // Calculate statistics
      const stats = {
        total_accounts: pppAccounts.length,
        online_accounts: pppActive.length,
        offline_accounts: pppAccounts.length - pppActive.length,
        enabled_accounts: pppAccounts.filter(acc => acc.disabled !== 'true').length,
        disabled_accounts: pppAccounts.filter(acc => acc.disabled === 'true').length
      };
      
      client.release();
      res.json({
        success: true,
        aggregate_stats: stats,
        ppp_accounts: pppAccounts,
        ppp_active: pppActiveWithProfiles,
        pppoe_interfaces: pppoeInterfaces
      });
      
    } catch (mikrotikError) {
      client.release();
      res.status(500).json({ error: 'Failed to connect to MikroTik', details: mikrotikError.message });
    }
    
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Get PPP accounts summary (using existing MikroTik settings)
app.get('/api/monitoring/ppp_accounts_summary', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    
    // Get active MikroTik settings
    const settingsResult = await client.query('SELECT * FROM mikrotik_settings WHERE is_active = true ORDER BY created_at DESC LIMIT 1');
    if (settingsResult.rows.length === 0) {
      client.release();
      return res.status(400).json({ error: 'No active MikroTik settings found. Please configure MikroTik settings first.' });
    }
    
    const settings = settingsResult.rows[0];
    const connection = new RouterOSAPI({
      host: settings.host,
      user: settings.username,
      password: settings.password,
      port: settings.port
    });

    try {
      await connection.connect();
      const [pppAccounts, pppActive] = await Promise.all([
        connection.write('/ppp/secret/print'),
        connection.write('/ppp/active/print')
      ]);
      connection.close();
      
      const onlineAccountNames = new Set(pppActive.map(acc => acc.name));
      const offlineAccounts = pppAccounts.filter(acc => !onlineAccountNames.has(acc.name));
      
      const statistics = {
        total_accounts: pppAccounts.length,
        online_accounts: pppActive.length,
        offline_accounts: offlineAccounts.length,
        enabled_accounts: pppAccounts.filter(acc => acc.disabled !== 'true').length,
        disabled_accounts: pppAccounts.filter(acc => acc.disabled === 'true').length
      };
      
      client.release();
      res.json({
        success: true,
        statistics,
        offline_accounts: offlineAccounts.map(acc => ({
          name: acc.name,
          profile: acc.profile,
          status: acc.disabled === 'true' ? 'Disabled' : 'Offline',
          last_uptime: acc['last-logged-out'] || '-'
        }))
      });
      
    } catch (mikrotikError) {
      client.release();
      res.status(500).json({ error: 'Failed to connect to MikroTik', details: mikrotikError.message });
    }
    
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Get monitoring groups
app.get('/api/monitoring/groups', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    
    // Check if table exists
    const tableCheck = await client.query(`
      SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'monitoring_groups'
      );
    `);
    
    
    if (!tableCheck.rows[0].exists) {
      client.release();
      return res.status(500).json({ error: 'monitoring_groups table does not exist. Please run database initialization first.' });
    }
    
    const result = await client.query(`
      SELECT * FROM monitoring_groups 
      ORDER BY name ASC
    `);
    
    // Parse the JSONB accounts back to arrays (frontend will handle online status)
    const groupsWithParsedAccounts = result.rows.map(group => {
      let accounts = typeof group.accounts === 'string' ? JSON.parse(group.accounts) : group.accounts;
      
      // Ensure accounts is an array
      if (!Array.isArray(accounts)) {
        accounts = [];
      }

      return {
        ...group,
        accounts
      };
    });
    
    client.release();
    res.json({ success: true, groups: groupsWithParsedAccounts });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error', details: error.message });
  }
});

// Add or update monitoring group
app.post('/api/monitoring/groups', authenticateToken, async (req, res) => {
  try {
    const { name, description, max_members, accounts } = req.body;
    if (!name) {
      return res.status(400).json({ error: 'name is required' });
    }

    const client = await pool.connect();
    
    // Check if table exists
    const tableCheck = await client.query(`
      SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'monitoring_groups'
      );
    `);
    
    
    if (!tableCheck.rows[0].exists) {
      client.release();
      return res.status(500).json({ error: 'monitoring_groups table does not exist. Please ensure database is properly initialized.' });
    }

    // Double check the table schema to ensure accounts column is JSONB
    const schemaCheck = await client.query(`
      SELECT data_type, column_default
      FROM information_schema.columns 
      WHERE table_name = 'monitoring_groups' 
      AND column_name = 'accounts'
    `);
    
    
    if (!schemaCheck.rows[0] || schemaCheck.rows[0].data_type !== 'jsonb') {
      client.release();
      return res.status(500).json({ 
        error: 'monitoring_groups table has incorrect schema. Please ensure database is properly initialized.',
        details: `accounts column type: ${schemaCheck.rows[0]?.data_type || 'missing'}`
      });
    }
    
    const result = await client.query(`
      INSERT INTO monitoring_groups (name, description, max_members, accounts)
      VALUES ($1, $2, $3, $4)
      RETURNING *
    `, [name, description, max_members, JSON.stringify(accounts || [])]);
    
    const insertedGroup = {
      ...result.rows[0],
      accounts: typeof result.rows[0].accounts === 'string' ? JSON.parse(result.rows[0].accounts) : result.rows[0].accounts
    };
    
    client.release();
    res.json({ success: true, group: insertedGroup });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error', details: error.message });
  }
});

// Update monitoring group
app.put('/api/monitoring/groups', authenticateToken, async (req, res) => {
  try {
    const { id, name, description, max_members, accounts } = req.body;
    if (!id || !name) {
      return res.status(400).json({ error: 'id and name are required' });
    }

    const client = await pool.connect();
    await client.query(`
      UPDATE monitoring_groups 
      SET name = $1, description = $2, max_members = $3, accounts = $4, updated_at = CURRENT_TIMESTAMP
      WHERE id = $5
    `, [name, description, max_members, JSON.stringify(accounts || []), id]);
    
    client.release();
    res.json({ success: true, message: 'Group updated successfully' });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Delete monitoring group
app.delete('/api/monitoring/groups', authenticateToken, async (req, res) => {
  try {
    const { id } = req.query;
    if (!id) {
      return res.status(400).json({ error: 'id is required' });
    }

    const client = await pool.connect();
    await client.query('DELETE FROM monitoring_groups WHERE id = $1', [id]);
    client.release();
    res.json({ success: true, message: 'Group deleted successfully' });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});


// Get monitoring categories
app.get('/api/monitoring/categories', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT * FROM monitoring_categories 
      ORDER BY category_index, subcategory_index
    `);
    
    // Group by category
    const categories = {};
    result.rows.forEach(row => {
      if (!categories[row.category_name]) {
        categories[row.category_name] = {
          category: row.category_name,
          subcategories: []
        };
      }
      
      if (row.subcategory_name) {
        let groups = [];
        try {
          if (row.group_ids) {
            if (typeof row.group_ids === 'string') {
              groups = JSON.parse(row.group_ids);
            } else if (Array.isArray(row.group_ids)) {
              groups = row.group_ids;
            } else {
              groups = [];
            }
          }
        } catch (e) {
          groups = [];
        }
        
        categories[row.category_name].subcategories.push({
          subcategory: row.subcategory_name,
          groups: groups
        });
      }
    });
    
    client.release();
    res.json({ success: true, categories: Object.values(categories) });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Add monitoring category
app.post('/api/monitoring/categories', authenticateToken, async (req, res) => {
  try {
    const { categories } = req.body;
    if (!categories) {
      return res.status(400).json({ error: 'categories are required' });
    }

    const client = await pool.connect();
    
    // Clear existing categories
    await client.query('DELETE FROM monitoring_categories');
    
    // Insert new categories
    for (let catIndex = 0; catIndex < categories.length; catIndex++) {
      const category = categories[catIndex];
      
      if (category.subcategories && category.subcategories.length > 0) {
        for (let subIndex = 0; subIndex < category.subcategories.length; subIndex++) {
          const subcategory = category.subcategories[subIndex];
          await client.query(`
            INSERT INTO monitoring_categories (category_name, subcategory_name, group_ids, category_index, subcategory_index)
            VALUES ($1, $2, $3, $4, $5)
          `, [category.category, subcategory.subcategory, JSON.stringify(subcategory.groups || []), catIndex, subIndex]);
        }
      } else {
        await client.query(`
          INSERT INTO monitoring_categories (category_name, category_index)
          VALUES ($1, $2)
        `, [category.category, catIndex]);
      }
    }
    
    client.release();
    res.json({ success: true, message: 'Categories saved successfully' });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update category
app.put('/api/monitoring/categories', authenticateToken, async (req, res) => {
  try {
    const { id, name } = req.body;
    if (!id || !name) {
      return res.status(400).json({ error: 'id and name are required' });
    }
    const client = await pool.connect();
    const result = await client.query(`
      UPDATE monitoring_categories 
      SET category_name = $1, updated_at = CURRENT_TIMESTAMP
      WHERE id = $2
      RETURNING *
    `, [name, id]);
    client.release();
    
    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Category not found' });
    }
    
    res.json({ success: true, category: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Delete category
app.delete('/api/monitoring/categories', authenticateToken, async (req, res) => {
  try {
    const { id } = req.query;
    if (!id) {
      return res.status(400).json({ error: 'id is required' });
    }
    const client = await pool.connect();
    const result = await client.query(`
      DELETE FROM monitoring_categories 
      WHERE id = $1
      RETURNING *
    `, [id]);
    client.release();
    
    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Category not found' });
    }
    
    res.json({ success: true, message: 'Category deleted successfully' });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Add subcategory
app.post('/api/monitoring/categories/:categoryId/subcategories', authenticateToken, async (req, res) => {
  try {
    const { categoryId } = req.params;
    const { name } = req.body;
    if (!name) {
      return res.status(400).json({ error: 'name is required' });
    }
    const client = await pool.connect();
    
    // Get the category to validate it exists
    const categoryResult = await client.query(`
      SELECT * FROM monitoring_categories WHERE id = $1
    `, [categoryId]);
    
    if (categoryResult.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Category not found' });
    }
    
    const result = await client.query(`
      INSERT INTO monitoring_categories (category_name, subcategory_name, group_ids, category_index, subcategory_index)
      VALUES ($1, $2, $3, $4, $5)
      RETURNING *
    `, [categoryResult.rows[0].category_name, name, [], categoryResult.rows[0].category_index, 0]);
    
    client.release();
    res.json({ success: true, subcategory: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update subcategory
app.put('/api/monitoring/categories/:categoryId/subcategories/:subcategoryId', authenticateToken, async (req, res) => {
  try {
    const { categoryId, subcategoryId } = req.params;
    const { name } = req.body;
    if (!name) {
      return res.status(400).json({ error: 'name is required' });
    }
    const client = await pool.connect();
    const result = await client.query(`
      UPDATE monitoring_categories 
      SET subcategory_name = $1, updated_at = CURRENT_TIMESTAMP
      WHERE id = $2
      RETURNING *
    `, [name, subcategoryId]);
    client.release();
    
    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Subcategory not found' });
    }
    
    res.json({ success: true, subcategory: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Delete subcategory
app.delete('/api/monitoring/categories/:categoryId/subcategories/:subcategoryId', authenticateToken, async (req, res) => {
  try {
    const { categoryId, subcategoryId } = req.params;
    const client = await pool.connect();
    const result = await client.query(`
      DELETE FROM monitoring_categories 
      WHERE id = $1
      RETURNING *
    `, [subcategoryId]);
    client.release();
    
    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Subcategory not found' });
    }
    
    res.json({ success: true, message: 'Subcategory deleted successfully' });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update subcategory group assignments
app.put('/api/monitoring/categories/:categoryId/subcategories/:subcategoryId/groups', authenticateToken, async (req, res) => {
  try {
    const { categoryId, subcategoryId } = req.params;
    const { group_ids } = req.body;
    if (!Array.isArray(group_ids)) {
      return res.status(400).json({ error: 'group_ids must be an array' });
    }
    const client = await pool.connect();
    const result = await client.query(`
      UPDATE monitoring_categories 
      SET group_ids = $1, updated_at = CURRENT_TIMESTAMP
      WHERE id = $2
      RETURNING *
    `, [group_ids, subcategoryId]);
    client.release();
    
    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Subcategory not found' });
    }
    
    res.json({ success: true, subcategory: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Redirect root to login page
app.get('/', (req, res) => {
  res.redirect('/login.html');
});

// ===== TICKETING SYSTEM API ENDPOINTS =====

// Helper function to generate ticket number
function generateTicketNumber() {
  const timestamp = Date.now().toString(36);
  const random = Math.random().toString(36).substring(2, 5);
  return `TKT-${timestamp}-${random}`.toUpperCase();
}

// Get all tickets with filtering and pagination
app.get('/api/tickets', authenticateToken, async (req, res) => {
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
      whereClause += ` AND (t.title ILIKE $${paramCount} OR t.description ILIKE $${paramCount} OR t.ticket_number ILIKE $${paramCount})`;
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
        total,
        pages: Math.ceil(total / limit)
      }
    });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Get ticket statistics (must come before /:id route)
app.get('/api/tickets/stats', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    
    const stats = await client.query(`
      SELECT 
        COUNT(*) as total_tickets,
        COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tickets,
        COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_tickets,
        COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_tickets,
        COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent_tickets,
        COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority_tickets
      FROM tickets
    `);
    
    client.release();
    res.json({ success: true, stats: stats.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Get single ticket by ID
app.get('/api/tickets/:id', authenticateToken, async (req, res) => {
  try {
    const ticketId = req.params.id;
    const client = await pool.connect();
    
    const result = await client.query(`
      SELECT 
        t.*,
        c.name as client_name,
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
      return res.status(404).json({ error: 'Ticket not found' });
    }
    
    res.json({ success: true, ticket: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Create new ticket
app.post('/api/tickets', authenticateToken, async (req, res) => {
  try {
    const { 
      title, 
      description, 
      client_id, 
      priority = 'medium', 
      category = 'general',
      assigned_to,
      due_date
    } = req.body;

    if (!title) {
      return res.status(400).json({ error: 'Title is required' });
    }

    const ticketNumber = generateTicketNumber();
    const client = await pool.connect();

    const result = await client.query(
      `INSERT INTO tickets (ticket_number, title, description, client_id, priority, category, assigned_to, created_by, due_date) 
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9) RETURNING *`,
      [ticketNumber, title, description, client_id || null, priority, category, assigned_to || null, req.user.userId, due_date || null]
    );

    // Add to history
    await client.query(
      'INSERT INTO ticket_history (ticket_id, user_id, action, description) VALUES ($1, $2, $3, $4)',
      [result.rows[0].id, req.user.userId, 'created', `Ticket created: ${title}`]
    );

    client.release();
    res.json({ success: true, ticket: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update ticket
app.put('/api/tickets/:id', authenticateToken, async (req, res) => {
  try {
    const ticketId = req.params.id;
    const { 
      title, 
      description, 
      client_id, 
      priority, 
      category, 
      status,
      assigned_to,
      due_date,
      resolution
    } = req.body;

    const client = await pool.connect();

    // Get current ticket for history tracking
    const currentTicket = await client.query('SELECT * FROM tickets WHERE id = $1', [ticketId]);
    if (currentTicket.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Ticket not found' });
    }

    const current = currentTicket.rows[0];
    const resolved_at = status === 'resolved' && current.status !== 'resolved' ? new Date() : current.resolved_at;

    const result = await client.query(
      `UPDATE tickets SET 
        title = COALESCE($1, title),
        description = COALESCE($2, description),
        client_id = COALESCE($3, client_id),
        priority = COALESCE($4, priority),
        category = COALESCE($5, category),
        status = COALESCE($6, status),
        assigned_to = COALESCE($7, assigned_to),
        due_date = COALESCE($8, due_date),
        resolution = COALESCE($9, resolution),
        resolved_at = $10,
        updated_at = CURRENT_TIMESTAMP
       WHERE id = $11 RETURNING *`,
      [title, description, client_id, priority, category, status, assigned_to, due_date, resolution, resolved_at, ticketId]
    );

    // Track changes in history
    const changes = [];
    if (status && status !== current.status) changes.push(`Status changed from ${current.status} to ${status}`);
    if (priority && priority !== current.priority) changes.push(`Priority changed from ${current.priority} to ${priority}`);
    if (assigned_to && assigned_to !== current.assigned_to) changes.push(`Assignment changed`);

    if (changes.length > 0) {
      await client.query(
        'INSERT INTO ticket_history (ticket_id, user_id, action, description) VALUES ($1, $2, $3, $4)',
        [ticketId, req.user.userId, 'updated', changes.join('; ')]
      );
    }

    client.release();
    res.json({ success: true, ticket: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Delete ticket
app.delete('/api/tickets/:id', authenticateToken, async (req, res) => {
  try {
    const ticketId = req.params.id;
    const client = await pool.connect();
    
    const result = await client.query('DELETE FROM tickets WHERE id = $1 RETURNING *', [ticketId]);
    
    if (result.rowCount === 0) {
      client.release();
      return res.status(404).json({ error: 'Ticket not found' });
    }
    
    client.release();
    res.json({ success: true, deleted: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Get ticket comments
app.get('/api/tickets/:id/comments', authenticateToken, async (req, res) => {
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
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Add comment to ticket
app.post('/api/tickets/:id/comments', authenticateToken, async (req, res) => {
  try {
    const ticketId = req.params.id;
    const { comment, is_internal = false } = req.body;

    if (!comment) {
      return res.status(400).json({ error: 'Comment is required' });
    }

    const client = await pool.connect();

    const result = await client.query(
      'INSERT INTO ticket_comments (ticket_id, user_id, comment, is_internal) VALUES ($1, $2, $3, $4) RETURNING *',
      [ticketId, req.user.userId, comment, is_internal]
    );

    // Add to history
    await client.query(
      'INSERT INTO ticket_history (ticket_id, user_id, action, description) VALUES ($1, $2, $3, $4)',
      [ticketId, req.user.userId, 'commented', is_internal ? 'Added internal comment' : 'Added comment']
    );

    client.release();
    res.json({ success: true, comment: result.rows[0] });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Get ticket history
app.get('/api/tickets/:id/history', authenticateToken, async (req, res) => {
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
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Handle 404 for any non-API routes that don't match static files
app.use((req, res, next) => {
  if (!req.path.startsWith('/api/') && req.method === 'GET') {
    return res.status(404).sendFile(path.join(__dirname, '../public/login.html'));
  }
  next();
});

// Start server
app.listen(PORT, () => {
});
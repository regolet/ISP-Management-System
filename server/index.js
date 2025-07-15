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

// Test database connection
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
    console.error('Database connection error:', error);
    res.status(500).json({ 
      status: 'error', 
      message: 'Database connection failed',
      error: error.message 
    });
  }
});

// Initialize database tables
app.post('/api/init-database', async (req, res) => {
  try {
    await initializeDatabaseTables();
    
    // Insert sample data
    const client = await pool.connect();
    await insertSampleData(client);
    client.release();

    res.json({ success: true, message: 'Database initialized successfully' });
  } catch (error) {
    console.error('Database initialization error:', error);
    res.status(500).json({ success: false, message: 'Database initialization failed', error: error.message });
  }
});

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
    console.error('Error inserting sample billing data:', error);
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
        email VARCHAR(100),
        phone VARCHAR(20),
        address TEXT,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
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
        anchor_day INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(client_id, plan_id)
      )
    `);

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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create payments table
    await client.query(`
      CREATE TABLE IF NOT EXISTS payments (
        id SERIAL PRIMARY KEY,
        billing_id INTEGER REFERENCES billings(id) ON DELETE CASCADE,
        amount DECIMAL(10,2) NOT NULL,
        payment_date DATE NOT NULL DEFAULT CURRENT_DATE,
        method VARCHAR(50) DEFAULT 'cash',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

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
    console.error('Login error details:', {
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
    console.error('Get user error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// API routes for data
app.get('/api/clients', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT * FROM clients ORDER BY created_at DESC');
    client.release();
    res.json(result.rows);
  } catch (error) {
    console.error('Get clients error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Create new client
app.post('/api/clients', authenticateToken, async (req, res) => {
  try {
    const { name, email, phone, address, status = 'active' } = req.body;
    if (!name) {
      return res.status(400).json({ error: 'Name is required' });
    }
    const client = await pool.connect();
    const result = await client.query(
      'INSERT INTO clients (name, email, phone, address, status) VALUES ($1, $2, $3, $4, $5) RETURNING *',
      [name, email, phone, address, status]
    );
    client.release();
    res.json({ success: true, client: result.rows[0] });
  } catch (error) {
    console.error('Create client error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update client by ID
app.put('/api/clients/:id', authenticateToken, async (req, res) => {
  try {
    const clientId = req.params.id;
    const { name, email, phone, address, status } = req.body;
    const client = await pool.connect();
    const result = await client.query(
      'UPDATE clients SET name = $1, email = $2, phone = $3, address = $4, status = $5, updated_at = CURRENT_TIMESTAMP WHERE id = $6 RETURNING *',
      [name, email, phone, address, status, clientId]
    );
    client.release();
    if (result.rowCount === 0) {
      return res.status(404).json({ error: 'Client not found' });
    }
    res.json({ success: true, updated: result.rows[0] });
  } catch (error) {
    console.error('Update client error:', error);
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
    console.error('Delete client error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Repair database endpoint
app.post('/api/repair-database', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    
    // Drop existing tables in correct order (respecting foreign key constraints)
    const dropQueries = [
      'DROP TABLE IF EXISTS payments CASCADE',
      'DROP TABLE IF EXISTS billings CASCADE', 
      'DROP TABLE IF EXISTS client_plans CASCADE',
      'DROP TABLE IF EXISTS clients CASCADE',
      'DROP TABLE IF EXISTS plans CASCADE',
      'DROP TABLE IF EXISTS company_info CASCADE',
      'DROP TABLE IF EXISTS mikrotik_settings CASCADE',
      'DROP TABLE IF EXISTS monitoring_groups CASCADE',
      'DROP TABLE IF EXISTS monitoring_categories CASCADE',
      'DROP TABLE IF EXISTS users CASCADE'
    ];
    
    for (const query of dropQueries) {
      await client.query(query);
    }
    
    client.release();
    
    // Reinitialize database
    await initializeDatabaseTables();
    
    res.json({ success: true, message: 'Database repaired and reinitialized successfully' });
    
  } catch (error) {
    console.error('Database repair error:', error);
    res.status(500).json({ 
      success: false, 
      message: 'Database repair failed', 
      error: error.message 
    });
  }
});

// Reset database endpoint  
app.post('/api/reset-database', authenticateToken, async (req, res) => {
  try {
    const { confirm } = req.body;
    
    if (confirm !== 'RESET') {
      return res.status(400).json({ 
        success: false, 
        message: 'Reset confirmation required. Send {"confirm": "RESET"} to proceed.' 
      });
    }
    
    const client = await pool.connect();
    
    // Drop all tables and data
    const dropQueries = [
      'DROP TABLE IF EXISTS payments CASCADE',
      'DROP TABLE IF EXISTS billings CASCADE',
      'DROP TABLE IF EXISTS client_plans CASCADE', 
      'DROP TABLE IF EXISTS clients CASCADE',
      'DROP TABLE IF EXISTS plans CASCADE',
      'DROP TABLE IF EXISTS company_info CASCADE',
      'DROP TABLE IF EXISTS mikrotik_settings CASCADE',
      'DROP TABLE IF EXISTS monitoring_groups CASCADE',
      'DROP TABLE IF EXISTS monitoring_categories CASCADE',
      'DROP TABLE IF EXISTS users CASCADE'
    ];
    
    for (const query of dropQueries) {
      await client.query(query);
    }
    
    client.release();
    
    // Reinitialize database with fresh schema and default admin user
    await initializeDatabaseTables();
    
    res.json({ success: true, message: 'Database reset completed successfully. All data has been removed and default admin user restored.' });
    
  } catch (error) {
    console.error('Database reset error:', error);
    res.status(500).json({ 
      success: false, 
      message: 'Database reset failed', 
      error: error.message 
    });
  }
});

app.get('/api/plans', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT * FROM plans ORDER BY created_at DESC');
    client.release();
    res.json(result.rows);
  } catch (error) {
    console.error('Get plans error:', error);
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
    console.error('Create plan error:', error);
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
    console.error('Update plan error:', error);
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
    console.error('Delete plan error:', error);
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
    console.error('Get client plans error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

app.post('/api/client-plans', authenticateToken, async (req, res) => {
  try {
    const { client_id, plan_id, status = 'active', anchor_day } = req.body;
    
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
    
    // Use provided anchor_day or default to today
    let anchorDayToSet = anchor_day;
    if (!anchorDayToSet) {
      anchorDayToSet = new Date().getDate();
    }
    const result = await client.query(
      'INSERT INTO client_plans (client_id, plan_id, status, anchor_day) VALUES ($1, $2, $3, $4) RETURNING *',
      [client_id, plan_id, status, anchorDayToSet]
    );
    
    client.release();
    res.json({ success: true, clientPlan: result.rows[0] });
  } catch (error) {
    console.error('Create client plan error:', error);
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
    console.error('Delete client plan error:', error);
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
    console.error('Get client plans count error:', error);
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
    console.error('Get all client plans error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

app.get('/api/billings', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT 
        b.id, 
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
      ORDER BY b.created_at DESC
    `);
    client.release();
    res.json(result.rows);
  } catch (error) {
    console.error('Get billings error:', error);
    res.status(500).json({ error: 'Internal server error', details: error.message });
  }
});
app.post('/api/billings', authenticateToken, async (req, res) => {
  try {
    const { client_id, plan_id, amount, due_date, status = 'pending' } = req.body;
    if (!client_id || !plan_id || !amount || !due_date) {
      return res.status(400).json({ error: 'All fields are required' });
    }
    const client = await pool.connect();
    const result = await client.query(
      'INSERT INTO billings (client_id, plan_id, amount, due_date, status) VALUES ($1, $2, $3, $4, $5) RETURNING *',
      [client_id, plan_id, amount, due_date, status]
    );
    client.release();
    res.json({ success: true, billing: result.rows[0] });
  } catch (error) {
    console.error('Create billing error:', error);
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
    console.error('Get billing error:', error);
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
    client.release();
    if (result.rowCount === 0) {
      return res.status(404).json({ error: 'Billing not found' });
    }
    res.json({ success: true, billing: result.rows[0] });
  } catch (error) {
    console.error('Update billing error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});
app.delete('/api/billings/:id', authenticateToken, async (req, res) => {
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
    console.error('Delete billing error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// List all payments
app.get('/api/payments', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT p.*, b.client_id, b.plan_id, b.amount AS billing_amount, b.due_date, b.status
      FROM payments p
      LEFT JOIN billings b ON p.billing_id = b.id
      ORDER BY p.created_at DESC
    `);
    client.release();
    res.json(result.rows);
  } catch (error) {
    console.error('Get payments error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Helper to get next due date on the same day of month, or last day if not possible
function getNextDueDate(currentDueDate, anchorDay) {
  const date = new Date(currentDueDate);
  let year = date.getFullYear();
  let month = date.getMonth() + 1; // next month (0-based, so +1)
  if (month > 11) {
    month = 0;
    year += 1;
  }
  // Get the last day of the intended month
  const lastDayOfMonth = new Date(year, month + 1, 0).getDate();
  // Use anchorDay if it exists in the month, otherwise use last day
  const day = Math.min(anchorDay, lastDayOfMonth);
  const nextDueDate = new Date(year, month, day);
  // Format as YYYY-MM-DD
  const yyyy = nextDueDate.getFullYear();
  const mm = String(nextDueDate.getMonth() + 1).padStart(2, '0');
  const dd = String(nextDueDate.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}

app.post('/api/payments', authenticateToken, async (req, res) => {
  try {
    const { billing_id, amount, payment_date, method, notes } = req.body;
    const client = await pool.connect();
    // Insert payment
    const paymentResult = await client.query(
      'INSERT INTO payments (billing_id, amount, payment_date, method, notes) VALUES ($1, $2, $3, $4, $5) RETURNING *',
      [billing_id, amount, payment_date, method, notes]
    );
    // Get the billing info
    const billingResult = await client.query('SELECT * FROM billings WHERE id = $1', [billing_id]);
    const billing = billingResult.rows[0];
    // Mark billing as paid (do not change due date)
    await client.query(
      `UPDATE billings SET status = 'paid', updated_at = CURRENT_TIMESTAMP WHERE id = $1`,
      [billing_id]
    );
    // Get anchor_day from client_plans
    const clientPlanResult = await client.query(
      'SELECT anchor_day FROM client_plans WHERE client_id = $1 AND plan_id = $2',
      [billing.client_id, billing.plan_id]
    );
    let anchorDay = 1;
    if (clientPlanResult.rows.length > 0 && clientPlanResult.rows[0].anchor_day) {
      anchorDay = clientPlanResult.rows[0].anchor_day;
    } else {
      anchorDay = new Date(billing.due_date).getDate();
    }
    // Always create new billing for next month, using helper for due date
    const nextDueDateStr = getNextDueDate(billing.due_date, anchorDay);
    await client.query(
      'INSERT INTO billings (client_id, plan_id, amount, due_date, status) VALUES ($1, $2, $3, $4, $5)',
      [billing.client_id, billing.plan_id, billing.amount, nextDueDateStr, 'pending']
    );
    client.release();
    res.json({ success: true, payment: paymentResult.rows[0] });
  } catch (error) {
    console.error('Create payment error:', error);
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
    console.error('Get MikroTik settings error:', error);
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
    console.error('Save MikroTik settings error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

app.post('/api/mikrotik/test-connection', authenticateToken, async (req, res) => {
  try {
    const { host, username, password, port = 8728 } = req.body;
    
    if (!host || !username || !password) {
      return res.status(400).json({ error: 'Host, username, and password are required' });
    }

    const connection = new RouterOSAPI({
      host: host,
      user: username,
      password: password,
      port: port
    });

    connection.connect()
      .then(() => {
        connection.close();
        res.json({ success: true, message: 'Connection successful' });
      })
      .catch((error) => {
        console.error('MikroTik connection error:', error);
        res.status(500).json({ error: 'Connection failed', details: error.message });
      });

  } catch (error) {
    console.error('Test MikroTik connection error:', error);
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
        console.error('MikroTik PPP accounts error:', error);
        res.status(500).json({ error: 'Failed to fetch PPP accounts', details: error.message });
      });

  } catch (error) {
    console.error('Get PPP accounts error:', error);
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
    console.error('Import clients error:', error);
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
        console.error('MikroTik PPP profiles error:', error);
        res.status(500).json({ error: 'Failed to fetch PPP profiles', details: error.message });
      });
  } catch (error) {
    console.error('Get PPP profiles error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Add this endpoint after other API routes
app.get('/api/clients-with-plan', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT 
        c.id, c.name, c.email, c.phone, c.address, c.status, c.created_at, c.updated_at,
        cp.plan_id, p.name AS plan_name, p.price AS plan_price
      FROM clients c
      LEFT JOIN client_plans cp ON c.id = cp.client_id AND cp.status = 'active'
      LEFT JOIN plans p ON cp.plan_id = p.id
      ORDER BY c.name
    `);
    client.release();
    res.json(result.rows);
  } catch (error) {
    console.error('Get clients with plan error:', error);
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
    console.error('Get company info error:', error);
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
    console.error('Update company info error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update anchor_day for a client_plan
app.put('/api/client-plans/:id', authenticateToken, async (req, res) => {
  try {
    const { anchor_day } = req.body;
    const { id } = req.params;
    if (anchor_day === undefined) {
      return res.status(400).json({ error: 'anchor_day is required' });
    }
    const client = await pool.connect();
    await client.query(
      'UPDATE client_plans SET anchor_day = $1, updated_at = CURRENT_TIMESTAMP WHERE id = $2',
      [anchor_day, id]
    );
    client.release();
    res.json({ success: true });
  } catch (error) {
    console.error('Update client_plan anchor_day error:', error);
    res.status(500).json({ error: 'Internal server error' });
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
        ppp_active: pppActive,
        pppoe_interfaces: pppoeInterfaces
      });
      
    } catch (mikrotikError) {
      client.release();
      console.error('MikroTik connection error:', mikrotikError);
      res.status(500).json({ error: 'Failed to connect to MikroTik', details: mikrotikError.message });
    }
    
  } catch (error) {
    console.error('Get monitoring dashboard error:', error);
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
      console.error('MikroTik connection error:', mikrotikError);
      res.status(500).json({ error: 'Failed to connect to MikroTik', details: mikrotikError.message });
    }
    
  } catch (error) {
    console.error('Get PPP accounts summary error:', error);
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
    
    console.log('monitoring_groups table exists:', tableCheck.rows[0].exists);
    
    if (!tableCheck.rows[0].exists) {
      client.release();
      return res.status(500).json({ error: 'monitoring_groups table does not exist. Please run database initialization first.' });
    }
    
    const result = await client.query(`
      SELECT * FROM monitoring_groups 
      ORDER BY created_at DESC
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
    console.error('Get monitoring groups error:', error);
    console.error('Error details:', error.message, error.code);
    res.status(500).json({ error: 'Internal server error', details: error.message });
  }
});

// Add or update monitoring group
app.post('/api/monitoring/groups', authenticateToken, async (req, res) => {
  try {
    console.log('POST /api/monitoring/groups - Request body:', req.body);
    const { name, description, max_members, accounts } = req.body;
    if (!name) {
      return res.status(400).json({ error: 'name is required' });
    }

    const client = await pool.connect();
    console.log('Database connected, checking table existence...');
    
    // Check if table exists
    const tableCheck = await client.query(`
      SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'monitoring_groups'
      );
    `);
    
    console.log('monitoring_groups table exists:', tableCheck.rows[0].exists);
    
    if (!tableCheck.rows[0].exists) {
      client.release();
      return res.status(500).json({ error: 'monitoring_groups table does not exist. Please run database initialization first by calling POST /api/init-database' });
    }

    // Double check the table schema to ensure accounts column is JSONB
    const schemaCheck = await client.query(`
      SELECT data_type, column_default
      FROM information_schema.columns 
      WHERE table_name = 'monitoring_groups' 
      AND column_name = 'accounts'
    `);
    
    console.log('Table schema check:', schemaCheck.rows[0]);
    
    if (!schemaCheck.rows[0] || schemaCheck.rows[0].data_type !== 'jsonb') {
      client.release();
      return res.status(500).json({ 
        error: 'monitoring_groups table has incorrect schema. Please reinitialize database by calling POST /api/init-database',
        details: `accounts column type: ${schemaCheck.rows[0]?.data_type || 'missing'}`
      });
    }
    
    console.log('Inserting group...');
    const result = await client.query(`
      INSERT INTO monitoring_groups (name, description, max_members, accounts)
      VALUES ($1, $2, $3, $4)
      RETURNING *
    `, [name, description, max_members, JSON.stringify(accounts || [])]);
    
    const insertedGroup = {
      ...result.rows[0],
      accounts: typeof result.rows[0].accounts === 'string' ? JSON.parse(result.rows[0].accounts) : result.rows[0].accounts
    };
    
    console.log('Group inserted successfully:', insertedGroup);
    client.release();
    res.json({ success: true, group: insertedGroup });
  } catch (error) {
    console.error('Add monitoring group error:', error);
    console.error('Error details:', error.message, error.code);
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
    console.error('Update monitoring group error:', error);
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
    console.error('Delete monitoring group error:', error);
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
          console.error('Error parsing group_ids JSON:', e);
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
    console.error('Get monitoring categories error:', error);
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
    console.error('Add monitoring categories error:', error);
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
    console.error('Update monitoring category error:', error);
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
    console.error('Delete monitoring category error:', error);
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
    console.error('Add monitoring subcategory error:', error);
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
    console.error('Update monitoring subcategory error:', error);
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
    console.error('Delete monitoring subcategory error:', error);
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
    console.error('Update monitoring subcategory groups error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Redirect root to login page
app.get('/', (req, res) => {
  res.redirect('/login.html');
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
  console.log(`Server running on port ${PORT}`);
  console.log(`Health check: http://localhost:${PORT}/api/health`);
  console.log(`Database init: http://localhost:${PORT}/api/init-database`);
  console.log(`Frontend: http://localhost:${PORT}`);
});
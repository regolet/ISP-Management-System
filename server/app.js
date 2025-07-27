const express = require('express');
const cors = require('cors');
const path = require('path');
const { PORT } = require('./config/constants');
const { initializeDatabaseTables } = require('./utils/database');
const { initializeScheduler } = require('./utils/scheduler');

// Import routes
const healthRoutes = require('./routes/health');
const authRoutes = require('./routes/auth');
const clientRoutes = require('./routes/clients');
const planRoutes = require('./routes/plans');
const clientPlanRoutes = require('./routes/clientPlans');
const billingRoutes = require('./routes/billings');
const paymentRoutes = require('./routes/payments');
const mikrotikRoutes = require('./routes/mikrotik');
const pppRoutes = require('./routes/ppp');
const companyRoutes = require('./routes/company');
const inventoryRoutes = require('./routes/inventory');
const monitoringRoutes = require('./routes/monitoring');
const ticketRoutes = require('./routes/tickets');
const assetRoutes = require('./routes/assets');
const networkSummaryRoutes = require('./routes/network-summary');
const networkTotalsRoutes = require('./routes/network-totals');
const schedulerRoutes = require('./routes/scheduler');
const systemRoutes = require('./routes/system');
const databaseStatusRoutes = require('./routes/database-status');

const app = express();

// Middleware
app.use(cors());
app.use(express.json());

// Serve static files from the public directory
app.use(express.static(path.join(__dirname, '../public')));

// Initialize database endpoint
app.post('/api/init-database', async (req, res) => {
  try {
    await initializeDatabaseTables();
    res.json({ 
      success: true, 
      message: 'Database initialized successfully' 
    });
  } catch (error) {
    res.status(500).json({ 
      success: false, 
      message: 'Database initialization failed', 
      error: error.message 
    });
  }
});

// Routes
app.use('/api/health', healthRoutes);
app.use('/api/auth', authRoutes);
app.use('/api/clients', clientRoutes);
app.use('/api/plans', planRoutes);
app.use('/api/client-plans', clientPlanRoutes);
app.use('/api/billings', billingRoutes);
app.use('/api/payments', paymentRoutes);
app.use('/api/mikrotik', mikrotikRoutes);
app.use('/api/ppp', pppRoutes);
app.use('/api/company-info', companyRoutes);
app.use('/api/inventory', inventoryRoutes);
app.use('/api/monitoring', monitoringRoutes);
app.use('/api/tickets', ticketRoutes);
app.use('/api/assets', assetRoutes);
app.use('/api/network-summary', networkSummaryRoutes);
app.use('/api/network-totals', networkTotalsRoutes);
app.use('/api/scheduler', schedulerRoutes);
app.use('/api/system', systemRoutes);
app.use('/api/database', databaseStatusRoutes);

// Handle special routes that don't follow REST pattern
const pool = require('./config/database');
const { authenticateToken } = require('./middleware/auth');

// Client plans count route
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

// Client plans all route
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

// PPP accounts route (alias)
app.get('/api/ppp-accounts', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const settingsResult = await client.query('SELECT * FROM mikrotik_settings ORDER BY created_at DESC LIMIT 1');
    client.release();
    
    if (settingsResult.rows.length === 0) {
      return res.status(400).json({ error: 'MikroTik settings not configured' });
    }
    
    const settings = settingsResult.rows[0];
    const RouterOSAPI = require('routeros-api').RouterOSAPI;
    const conn = new RouterOSAPI({
      host: settings.host,
      user: settings.username,
      password: settings.password,
      port: settings.port || 8728
    });
    
    await conn.connect();
    const accounts = await conn.write('/ppp/secret/print');
    await conn.close();
    
    res.json({ success: true, accounts: Array.isArray(accounts) ? accounts : [] });
  } catch (error) {
    console.error('MikroTik connection error:', error);
    res.status(500).json({ error: 'Failed to connect to MikroTik', details: error.message });
  }
});

// PPP profiles route (alias)  
app.get('/api/ppp-profiles', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const settingsResult = await client.query('SELECT * FROM mikrotik_settings ORDER BY created_at DESC LIMIT 1');
    client.release();
    
    if (settingsResult.rows.length === 0) {
      return res.status(400).json({ error: 'MikroTik settings not configured' });
    }
    
    const settings = settingsResult.rows[0];
    const RouterOSAPI = require('routeros-api').RouterOSAPI;
    const conn = new RouterOSAPI({
      host: settings.host,
      user: settings.username,
      password: settings.password,
      port: settings.port || 8728
    });
    
    await conn.connect();
    const profiles = await conn.write('/ppp/profile/print');
    await conn.close();
    
    res.json({ success: true, profiles: Array.isArray(profiles) ? profiles : [] });
  } catch (error) {
    console.error('MikroTik connection error:', error);
    res.status(500).json({ error: 'Failed to connect to MikroTik', details: error.message });
  }
});

// Import clients route (alias)
app.post('/api/import-clients', authenticateToken, async (req, res) => {
  try {
    const { selectedAccounts } = req.body;
    
    if (!selectedAccounts || !Array.isArray(selectedAccounts)) {
      return res.status(400).json({ error: 'Selected accounts are required' });
    }
    
    const client = await pool.connect();
    let importedCount = 0;
    let skippedCount = 0;
    let errorCount = 0;
    const errors = [];
    
    for (const account of selectedAccounts) {
      try {
        // Validate account data
        if (!account.name || account.name.trim() === '') {
          errors.push(`Account with empty name skipped`);
          errorCount++;
          continue;
        }
        
        // Check if client already exists
        const existing = await client.query('SELECT id FROM clients WHERE name = $1', [account.name]);
        
        if (existing.rows.length === 0) {
          // Prepare client data
          const clientName = account.name.trim();
          const clientEmail = account.comment || `${clientName}@isp.local`;
          const clientAddress = account.comment && !account.comment.includes('@') ? 
            account.comment : 'Imported from MikroTik';
          const mikrotikProfile = account.profile || 'default';
          
          // Import as new client
          const clientResult = await client.query(
            'INSERT INTO clients (name, email, address, status) VALUES ($1, $2, $3, $4) RETURNING id',
            [clientName, clientEmail, clientAddress, 'active']
          );
          const newClientId = clientResult.rows[0].id;
          
          // Find or create a plan based on the MikroTik profile
          let planResult = await client.query(
            'SELECT id FROM plans WHERE name = $1',
            [mikrotikProfile]
          );
          
          let planId;
          if (planResult.rows.length === 0) {
            // Create a new plan based on the MikroTik profile
            const newPlanResult = await client.query(
              'INSERT INTO plans (name, description, price, status) VALUES ($1, $2, $3, $4) RETURNING id',
              [mikrotikProfile, `Imported from MikroTik profile: ${mikrotikProfile}`, 0.00, 'active']
            );
            planId = newPlanResult.rows[0].id;
          } else {
            planId = planResult.rows[0].id;
          }
          
          // Create client-plan relationship
          await client.query(
            'INSERT INTO client_plans (client_id, plan_id, status) VALUES ($1, $2, $3)',
            [newClientId, planId, 'active']
          );
          
          importedCount++;
        } else {
          skippedCount++;
        }
      } catch (error) {
        console.error(`Error importing client ${account.name}:`, error);
        errors.push(`Failed to import ${account.name}: ${error.message}`);
        errorCount++;
      }
    }
    
    client.release();
    
    // Prepare response with detailed results
    const response = {
      success: true,
      imported: importedCount,
      skipped: skippedCount,
      errors: errorCount,
      message: `Import completed: ${importedCount} clients imported with plans, ${skippedCount} skipped, ${errorCount} errors`
    };
    
    if (errors.length > 0 && errors.length <= 5) {
      response.errorDetails = errors;
    } else if (errors.length > 5) {
      response.errorDetails = errors.slice(0, 5);
      response.message += ` (showing first 5 errors)`;
    }
    
    res.json(response);
  } catch (error) {
    console.error('Import clients error:', error);
    res.status(500).json({ 
      success: false, 
      error: 'Internal server error', 
      details: error.message 
    });
  }
});

// Clients with plan route
app.get('/api/clients-with-plan', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT 
        c.id,
        c.name,
        c.email,
        c.phone,
        c.address,
        c.status,
        c.payment_status,
        c.balance,
        cp.plan_id,
        p.name as plan_name,
        p.price as plan_price
      FROM clients c
      LEFT JOIN client_plans cp ON c.id = cp.client_id AND cp.status = 'active'
      LEFT JOIN plans p ON cp.plan_id = p.id
      ORDER BY c.name
    `);
    client.release();
    res.json(result.rows);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// All routes have been successfully migrated to modular structure!

// Default route - redirect to login
app.get('/', (req, res) => {
  res.redirect('/login.html');
});

// Catch-all handler: send back React's index.html file for non-API routes
app.get('*', (req, res) => {
  // Check if it's an API route that doesn't exist
  if (req.path.startsWith('/api/')) {
    return res.status(404).json({ error: 'API endpoint not found' });
  }
  // For non-API routes, redirect to login
  res.redirect('/login.html');
});

// Start server
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
  
  // Initialize scheduled tasks
  initializeScheduler();
});

module.exports = app;
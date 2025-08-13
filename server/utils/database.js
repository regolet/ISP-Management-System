const bcrypt = require('bcryptjs');
const pool = require('../config/database');

// Create ticketing system tables (accepts client to use existing connection)
async function createTicketingTables(client) {
  // Create ticketing tables with SQLite-compatible syntax
  await client.query(`
    CREATE TABLE IF NOT EXISTS tickets (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      ticket_number VARCHAR(20) UNIQUE NOT NULL,
      title VARCHAR(255) NOT NULL,
      description TEXT,
      client_id INTEGER REFERENCES clients(id) ON DELETE SET NULL,
      priority VARCHAR(20) DEFAULT 'medium',
      category VARCHAR(50) DEFAULT 'general',
      status VARCHAR(20) DEFAULT 'open',
      assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
      created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
      resolution TEXT,
      resolved_at TIMESTAMP,
      due_date TIMESTAMP,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
  `);

  await client.query(`
    CREATE TABLE IF NOT EXISTS ticket_comments (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      ticket_id INTEGER REFERENCES tickets(id) ON DELETE CASCADE,
      user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
      comment TEXT NOT NULL,
      is_internal BOOLEAN DEFAULT false,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
  `);

  await client.query(`
    CREATE TABLE IF NOT EXISTS ticket_attachments (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      ticket_id INTEGER REFERENCES tickets(id) ON DELETE CASCADE,
      filename VARCHAR(255) NOT NULL,
      original_name VARCHAR(255) NOT NULL,
      file_size INTEGER,
      mime_type VARCHAR(100),
      uploaded_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
  `);

  await client.query(`
    CREATE TABLE IF NOT EXISTS ticket_history (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      ticket_id INTEGER REFERENCES tickets(id) ON DELETE CASCADE,
      user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
      action VARCHAR(50) NOT NULL,
      old_value TEXT,
      new_value TEXT,
      description TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
  `);
}

// insertSampleData function removed - no sample data needed for production

// Initialize database tables
async function initializeDatabaseTables() {
  console.log('Initializing database tables...');
  const client = await pool.connect();
  
  try {
    // Start transaction
    await client.query('BEGIN');

    // Create all main tables with SQLite-compatible syntax
    await client.query(`
      CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        role VARCHAR(20) DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Clients table
      CREATE TABLE IF NOT EXISTS clients (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        address TEXT,
        status VARCHAR(20) DEFAULT 'active',
        payment_status VARCHAR(20) DEFAULT 'paid',
        balance DECIMAL(10,2) DEFAULT 0.00,
        installation_date DATE,
        due_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Plans table
      CREATE TABLE IF NOT EXISTS plans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        speed VARCHAR(50),
        download_speed VARCHAR(50),
        upload_speed VARCHAR(50),
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Client Plans table (many-to-many relationship)
      CREATE TABLE IF NOT EXISTS client_plans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
        plan_id INTEGER REFERENCES plans(id) ON DELETE CASCADE,
        status VARCHAR(20) DEFAULT 'active',
        anchor_day INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Billings table
      CREATE TABLE IF NOT EXISTS billings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
        plan_id INTEGER REFERENCES plans(id) ON DELETE CASCADE,
        amount DECIMAL(10,2) NOT NULL,
        due_date DATE NOT NULL,
        billing_month DATE NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Payments table
      CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
        plan_id INTEGER REFERENCES plans(id) ON DELETE CASCADE,
        amount DECIMAL(10,2) NOT NULL,
        payment_date DATE NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'cash',
        reference_number VARCHAR(100),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- MikroTik Settings table
      CREATE TABLE IF NOT EXISTS mikrotik_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        host VARCHAR(255) NOT NULL,
        username VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        port INTEGER DEFAULT 8728,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Company Info table
      CREATE TABLE IF NOT EXISTS company_info (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        company_name VARCHAR(255),
        address TEXT,
        phone VARCHAR(50),
        email VARCHAR(100),
        website VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Inventory Categories table
      CREATE TABLE IF NOT EXISTS inventory_categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Inventory Suppliers table
      CREATE TABLE IF NOT EXISTS inventory_suppliers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        contact_person VARCHAR(100),
        phone VARCHAR(20),
        email VARCHAR(100),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Inventory Items table
      CREATE TABLE IF NOT EXISTS inventory_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        category_id INTEGER REFERENCES inventory_categories(id) ON DELETE SET NULL,
        supplier_id INTEGER REFERENCES inventory_suppliers(id) ON DELETE SET NULL,
        sku VARCHAR(50),
        unit_cost DECIMAL(10,2),
        selling_price DECIMAL(10,2),
        quantity_on_hand INTEGER DEFAULT 0,
        reorder_level INTEGER DEFAULT 0,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Inventory Assignments table
      CREATE TABLE IF NOT EXISTS inventory_assignments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
        item_id INTEGER REFERENCES inventory_items(id) ON DELETE CASCADE,
        quantity INTEGER NOT NULL,
        assigned_date DATE NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Inventory Movements table
      CREATE TABLE IF NOT EXISTS inventory_movements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        item_id INTEGER REFERENCES inventory_items(id) ON DELETE CASCADE,
        movement_type VARCHAR(20) NOT NULL,
        quantity INTEGER NOT NULL,
        reference_type VARCHAR(50),
        reference_id INTEGER,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Monitoring Groups table
      CREATE TABLE IF NOT EXISTS monitoring_groups (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        group_name VARCHAR(100) NOT NULL,
        group_data JSONB NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Monitoring Categories table
      CREATE TABLE IF NOT EXISTS monitoring_categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_name VARCHAR(100) NOT NULL,
        subcategory_name VARCHAR(100),
        group_ids JSONB,
        category_index INTEGER,
        subcategory_index INTEGER,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Assets table
      CREATE TABLE IF NOT EXISTS assets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        type VARCHAR(50) NOT NULL,
        description TEXT,
        location TEXT,
        deployment_date DATE,
        status VARCHAR(20) DEFAULT 'active',
        serial_number VARCHAR(100),
        model VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Asset Collections table
      CREATE TABLE IF NOT EXISTS asset_collections (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        asset_id INTEGER REFERENCES assets(id) ON DELETE CASCADE,
        collection_date DATE NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        collector_name VARCHAR(100),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Asset Subitems table (linking assets to inventory items)
      CREATE TABLE IF NOT EXISTS asset_subitems (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        asset_id INTEGER REFERENCES assets(id) ON DELETE CASCADE,
        inventory_item_id INTEGER REFERENCES inventory_items(id) ON DELETE CASCADE,
        quantity INTEGER NOT NULL DEFAULT 1,
        notes TEXT,
        deployment_date DATE,
        status VARCHAR(20) DEFAULT 'deployed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(asset_id, inventory_item_id)
      );

      -- Network Summary table for storing monitoring data
      CREATE TABLE IF NOT EXISTS network_summary (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        total_clients INTEGER DEFAULT 0,
        online_clients INTEGER DEFAULT 0,
        offline_clients INTEGER DEFAULT 0,
        total_bandwidth_usage BIGINT DEFAULT 0, -- total in bytes (upload + download)
        upload_bandwidth BIGINT DEFAULT 0, -- upload in bytes/sec
        download_bandwidth BIGINT DEFAULT 0, -- download in bytes/sec
        network_uptime_percentage DECIMAL(5,2) DEFAULT 100.00,
        active_connections INTEGER DEFAULT 0,
        failed_connections INTEGER DEFAULT 0,
        data_collected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Interface stats table for real-time bandwidth calculation
      CREATE TABLE IF NOT EXISTS interface_stats (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        interface_name VARCHAR(255) NOT NULL,
        rx_bytes BIGINT DEFAULT 0,
        tx_bytes BIGINT DEFAULT 0,
        collected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(interface_name, collected_at)
      );
      
      -- Create index for faster lookups
      CREATE INDEX IF NOT EXISTS idx_interface_stats_name_time 
      ON interface_stats(interface_name, collected_at DESC);

      -- Scheduler Settings table
      CREATE TABLE IF NOT EXISTS scheduler_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key VARCHAR(100) NOT NULL,
        setting_value VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Supabase Configuration table
      CREATE TABLE IF NOT EXISTS supabase_config (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        url VARCHAR(255) NOT NULL,
        anon_key TEXT NOT NULL,
        last_sync TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );
    `);

    // SQLite doesn't support DO blocks, so we'll skip these PostgreSQL-specific schema modifications
    // All necessary columns are already defined in the CREATE TABLE statements above

    // Create ticketing tables in the same transaction
    await createTicketingTables(client);

    // Create default admin user if not exists
    const hashedPassword = await bcrypt.hash('admin123', 10);
    await client.query(`
      INSERT INTO users (username, password, email, role) 
      VALUES ($1, $2, $3, $4) 
      ON CONFLICT (username) DO NOTHING
    `, ['admin', hashedPassword, 'admin@localhost', 'admin']);

    // Commit transaction
    await client.query('COMMIT');
    
  } catch (error) {
    // Rollback on error
    await client.query('ROLLBACK');
    throw error;
  } finally {
    client.release();
  }
}

module.exports = {
  createTicketingTables,
  initializeDatabaseTables
};
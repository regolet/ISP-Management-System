const bcrypt = require('bcryptjs');
const pool = require('../config/database');

// Create ticketing system tables (accepts client to use existing connection)
async function createTicketingTables(client) {
  // Create all ticketing tables in a single query
  await client.query(`
    -- Tickets table
    CREATE TABLE IF NOT EXISTS tickets (
      id SERIAL PRIMARY KEY,
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
    );

    -- Ticket comments table
    CREATE TABLE IF NOT EXISTS ticket_comments (
      id SERIAL PRIMARY KEY,
      ticket_id INTEGER REFERENCES tickets(id) ON DELETE CASCADE,
      user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
      comment TEXT NOT NULL,
      is_internal BOOLEAN DEFAULT false,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Ticket attachments table
    CREATE TABLE IF NOT EXISTS ticket_attachments (
      id SERIAL PRIMARY KEY,
      ticket_id INTEGER REFERENCES tickets(id) ON DELETE CASCADE,
      filename VARCHAR(255) NOT NULL,
      original_name VARCHAR(255) NOT NULL,
      file_size INTEGER,
      mime_type VARCHAR(100),
      uploaded_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Ticket history table
    CREATE TABLE IF NOT EXISTS ticket_history (
      id SERIAL PRIMARY KEY,
      ticket_id INTEGER REFERENCES tickets(id) ON DELETE CASCADE,
      user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
      action VARCHAR(50) NOT NULL,
      old_value TEXT,
      new_value TEXT,
      description TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
  `);
}

async function insertSampleData(client) {
  // Sample data insertion logic can be added here if needed
  try {
    // This function is currently empty - sample data has been removed
    // Add sample data logic here if needed in the future
  } catch (error) {
    console.error('Error inserting sample data:', error);
  }
}

// Initialize database tables
async function initializeDatabaseTables() {
  console.log('Initializing database tables...');
  const client = await pool.connect();
  
  try {
    // Start transaction
    await client.query('BEGIN');

    // Create all main tables in a single query
    await client.query(`
      -- Users table
      CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        role VARCHAR(20) DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Clients table
      CREATE TABLE IF NOT EXISTS clients (
        id SERIAL PRIMARY KEY,
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
        id SERIAL PRIMARY KEY,
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
        id SERIAL PRIMARY KEY,
        client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
        plan_id INTEGER REFERENCES plans(id) ON DELETE CASCADE,
        status VARCHAR(20) DEFAULT 'active',
        anchor_day INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Billings table
      CREATE TABLE IF NOT EXISTS billings (
        id SERIAL PRIMARY KEY,
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
        id SERIAL PRIMARY KEY,
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
        id SERIAL PRIMARY KEY,
        host VARCHAR(255) NOT NULL,
        username VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        port INTEGER DEFAULT 8728,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Company Info table
      CREATE TABLE IF NOT EXISTS company_info (
        id SERIAL PRIMARY KEY,
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
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Inventory Suppliers table
      CREATE TABLE IF NOT EXISTS inventory_suppliers (
        id SERIAL PRIMARY KEY,
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
        id SERIAL PRIMARY KEY,
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
        id SERIAL PRIMARY KEY,
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
        id SERIAL PRIMARY KEY,
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
        id SERIAL PRIMARY KEY,
        group_name VARCHAR(100) NOT NULL,
        group_data JSONB NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      -- Monitoring Categories table
      CREATE TABLE IF NOT EXISTS monitoring_categories (
        id SERIAL PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL,
        subcategory_name VARCHAR(100),
        group_ids JSONB,
        category_index INTEGER,
        subcategory_index INTEGER,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );
    `);

    // Add subcategory_name column if it doesn't exist
    await client.query(`
      DO $$ 
      BEGIN
        IF NOT EXISTS (
          SELECT 1 FROM information_schema.columns 
          WHERE table_name = 'monitoring_categories' 
          AND column_name = 'subcategory_name'
        ) THEN
          ALTER TABLE monitoring_categories ADD COLUMN subcategory_name VARCHAR(100);
        END IF;
      END $$;
    `);

    // Remove profile column from clients table if it exists (we use client_plans instead)
    await client.query(`
      DO $$ 
      BEGIN
        IF EXISTS (
          SELECT 1 FROM information_schema.columns 
          WHERE table_name = 'clients' 
          AND column_name = 'profile'
        ) THEN
          ALTER TABLE clients DROP COLUMN profile;
        END IF;
      END $$;
    `);

    // Apply schema modifications in a single PL/pgSQL block
    await client.query(`
      DO $$
      BEGIN
        -- Add columns if they don't exist
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='payments' AND column_name='billing_id') THEN
          ALTER TABLE payments ADD COLUMN billing_id INTEGER REFERENCES billings(id) ON DELETE SET NULL;
        END IF;
        
        -- Remove billing_id column if it exists (based on migration pattern)
        IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='payments' AND column_name='billing_id') THEN
          ALTER TABLE payments DROP COLUMN billing_id;
        END IF;
        
        -- Force fix monitoring_groups table schema
        -- First, drop the table completely if it exists with wrong schema
        IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name='monitoring_groups') AND 
           NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='monitoring_groups' AND column_name='group_name') THEN
          DROP TABLE IF EXISTS monitoring_groups CASCADE;
        END IF;
        
        -- Recreate monitoring_groups table if it doesn't exist or was dropped
        IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name='monitoring_groups') THEN
          CREATE TABLE monitoring_groups (
            id SERIAL PRIMARY KEY,
            group_name VARCHAR(100) NOT NULL,
            group_data JSONB NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
          );
        END IF;
      END $$;
    `);

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
  insertSampleData,
  initializeDatabaseTables
};
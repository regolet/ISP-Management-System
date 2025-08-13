const { createClient } = require('@supabase/supabase-js');
const pool = require('../config/database');

class SupabaseSync {
  constructor(url, anonKey) {
    if (url && anonKey) {
      this.supabase = createClient(url, anonKey);
      this.initialized = true;
    } else {
      this.initialized = false;
      this.supabase = null;
    }
  }

  async testConnection() {
    if (!this.initialized) {
      throw new Error('Supabase not configured');
    }
    
    try {
      // Test connection by checking if tables exist
      const { data, error } = await this.supabase
        .from('clients')
        .select('count')
        .limit(1);
      
      if (error && error.code === '42P01') {
        // Table doesn't exist - need to create schema
        return { needsSchema: true, connected: true };
      } else if (error) {
        throw error;
      }
      
      return { needsSchema: false, connected: true };
    } catch (error) {
      throw new Error(`Supabase connection failed: ${error.message}`);
    }
  }

  static getSchemaSQL() {
    // Static method - doesn't need instance or configuration
    // This returns the SQL that should be run in Supabase SQL editor
    return `
-- Users table
CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100),
  role VARCHAR(20) DEFAULT 'user',
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
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
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
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
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Client Plans table
CREATE TABLE IF NOT EXISTS client_plans (
  id SERIAL PRIMARY KEY,
  client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
  plan_id INTEGER REFERENCES plans(id) ON DELETE CASCADE,
  status VARCHAR(20) DEFAULT 'active',
  anchor_day INTEGER DEFAULT 1,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
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
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
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
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- MikroTik Settings table
CREATE TABLE IF NOT EXISTS mikrotik_settings (
  id SERIAL PRIMARY KEY,
  host VARCHAR(255) NOT NULL,
  username VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  port INTEGER DEFAULT 8728,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Company Info table
CREATE TABLE IF NOT EXISTS company_info (
  id SERIAL PRIMARY KEY,
  company_name VARCHAR(255),
  address TEXT,
  phone VARCHAR(50),
  email VARCHAR(100),
  website VARCHAR(255),
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Inventory Categories table
CREATE TABLE IF NOT EXISTS inventory_categories (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Inventory Suppliers table
CREATE TABLE IF NOT EXISTS inventory_suppliers (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  contact_person VARCHAR(100),
  phone VARCHAR(20),
  email VARCHAR(100),
  address TEXT,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
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
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Enable Row Level Security on all tables
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE clients ENABLE ROW LEVEL SECURITY;
ALTER TABLE plans ENABLE ROW LEVEL SECURITY;
ALTER TABLE client_plans ENABLE ROW LEVEL SECURITY;
ALTER TABLE billings ENABLE ROW LEVEL SECURITY;
ALTER TABLE payments ENABLE ROW LEVEL SECURITY;
ALTER TABLE mikrotik_settings ENABLE ROW LEVEL SECURITY;
ALTER TABLE company_info ENABLE ROW LEVEL SECURITY;
ALTER TABLE inventory_categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE inventory_suppliers ENABLE ROW LEVEL SECURITY;
ALTER TABLE inventory_items ENABLE ROW LEVEL SECURITY;

-- Create policies for anonymous access (adjust as needed for your security requirements)
CREATE POLICY "Enable read access for all users" ON clients FOR SELECT USING (true);
CREATE POLICY "Enable insert for all users" ON clients FOR INSERT WITH CHECK (true);
CREATE POLICY "Enable update for all users" ON clients FOR UPDATE USING (true);
CREATE POLICY "Enable delete for all users" ON clients FOR DELETE USING (true);

-- Repeat for other tables as needed
`;
  }

  async syncTable(tableName, data) {
    if (!this.initialized) {
      throw new Error('Supabase not configured');
    }

    if (!data || data.length === 0) {
      console.log(`No data to sync for table: ${tableName}`);
      return { success: true, count: 0 };
    }

    try {
      // Clear existing data in Supabase table (skip for users table to preserve auth)
      if (tableName !== 'users') {
        const { error: deleteError } = await this.supabase
          .from(tableName)
          .delete()
          .neq('id', 0); // Delete all rows

        if (deleteError && deleteError.code !== '42P01') {
          console.error(`Error clearing table ${tableName}:`, deleteError);
        }
      }

      // Clean and prepare data for Supabase
      const cleanedData = data.map(row => {
        const cleaned = { ...row };
        
        // Remove SQLite-specific fields
        delete cleaned.rowid;
        delete cleaned.oid;
        
        // Remove fields that might exist in SQLite but not in Supabase
        delete cleaned.last_modified;
        delete cleaned.is_active;
        delete cleaned.end_date;
        delete cleaned.logo_url;
        delete cleaned.sync_status;
        delete cleaned.start_date;
        
        // Convert null values and handle special cases
        Object.keys(cleaned).forEach(key => {
          // Handle dates
          if (key.includes('date') || key.includes('_at')) {
            if (cleaned[key] && cleaned[key] !== 'null') {
              // Ensure proper date format
              const date = new Date(cleaned[key]);
              if (!isNaN(date.getTime())) {
                cleaned[key] = date.toISOString();
              } else {
                cleaned[key] = null;
              }
            } else {
              cleaned[key] = null;
            }
          }
          
          // Handle numeric fields
          if (typeof cleaned[key] === 'string' && !isNaN(cleaned[key]) && cleaned[key] !== '') {
            if (key.includes('id') || key.includes('count') || key.includes('level') || 
                key.includes('quantity') || key.includes('day') || key.includes('port')) {
              cleaned[key] = parseInt(cleaned[key], 10);
            } else if (key.includes('price') || key.includes('cost') || key.includes('amount') || 
                       key.includes('balance') || key.includes('bandwidth') || key.includes('percentage')) {
              cleaned[key] = parseFloat(cleaned[key]);
            }
          }
          
          // Handle empty strings
          if (cleaned[key] === '') {
            cleaned[key] = null;
          }
        });
        
        return cleaned;
      });

      // Insert in batches to avoid size limits
      const batchSize = 100;
      let totalInserted = 0;
      
      for (let i = 0; i < cleanedData.length; i += batchSize) {
        const batch = cleanedData.slice(i, i + batchSize);
        
        const { data: insertedData, error: insertError } = await this.supabase
          .from(tableName)
          .insert(batch);

        if (insertError) {
          console.error(`Error inserting batch ${i / batchSize + 1} for table ${tableName}:`, insertError);
          throw insertError;
        }
        
        totalInserted += batch.length;
        console.log(`  Inserted batch ${i / batchSize + 1} (${batch.length} records) for ${tableName}`);
      }

      return { success: true, count: totalInserted };
    } catch (error) {
      console.error(`Error syncing table ${tableName}:`, error);
      throw error;
    }
  }

  async syncAllData() {
    if (!this.initialized) {
      throw new Error('Supabase not configured');
    }

    const results = {
      success: true,
      tables: {},
      errors: []
    };

    try {
      const client = await pool.connect();

      // Define tables to sync in order (considering foreign key dependencies)
      // Start with independent tables, then dependent ones
      const tablesToSync = [
        'company_info',
        'mikrotik_settings',
        'clients',
        'plans',
        'inventory_categories',
        'inventory_suppliers',
        'inventory_items',
        'client_plans',
        'billings',
        'payments'
        // Skip 'users' to avoid auth conflicts
      ];

      for (const table of tablesToSync) {
        try {
          console.log(`\nSyncing table: ${table}`);
          
          // Get data from local database with only standard columns
          let query = '';
          switch(table) {
            case 'company_info':
              query = `SELECT id, company_name, address, phone, email, website, created_at, updated_at FROM ${table}`;
              break;
            case 'mikrotik_settings':
              query = `SELECT id, host, username, password, port, created_at, updated_at FROM ${table}`;
              break;
            case 'clients':
              query = `SELECT id, name, email, phone, address, status, payment_status, balance, installation_date, due_date, created_at, updated_at FROM ${table}`;
              break;
            case 'plans':
              query = `SELECT id, name, description, price, speed, download_speed, upload_speed, status, created_at, updated_at FROM ${table}`;
              break;
            case 'client_plans':
              // Check what columns actually exist first
              try {
                const columnsResult = await client.query(`PRAGMA table_info(${table})`);
                const columns = columnsResult.rows.map(row => row.name);
                const standardColumns = ['id', 'client_id', 'plan_id', 'status', 'created_at', 'updated_at'];
                const existingColumns = standardColumns.filter(col => columns.includes(col));
                if (existingColumns.length > 0) {
                  query = `SELECT ${existingColumns.join(', ')} FROM ${table}`;
                } else {
                  // Fallback if no standard columns found
                  query = `SELECT * FROM ${table}`;
                }
              } catch (e) {
                query = `SELECT * FROM ${table}`;
              }
              break;
            default:
              query = `SELECT * FROM ${table}`;
          }
          
          const result = await client.query(query);
          const data = result.rows;
          console.log(`  Found ${data.length} records in local ${table} table`);

          // Sync to Supabase (data cleaning happens inside syncTable)
          const syncResult = await this.syncTable(table, data);
          results.tables[table] = syncResult;
          
          console.log(`✓ Synced ${table}: ${syncResult.count} records`);
        } catch (error) {
          console.error(`Error syncing ${table}:`, error);
          results.errors.push({
            table,
            error: error.message
          });
          results.success = false;
        }
      }

      client.release();
    } catch (error) {
      console.error('Database connection error:', error);
      results.errors.push({
        general: error.message
      });
      results.success = false;
    }

    return results;
  }

  async getLastSyncTime() {
    // This could be stored in local database or localStorage
    // For now, return current time
    return new Date().toISOString();
  }
}

module.exports = SupabaseSync;
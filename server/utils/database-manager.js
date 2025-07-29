const { Pool } = require('pg');
const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const fs = require('fs').promises;

class DatabaseManager {
  constructor() {
    // Start in offline mode if configured or if database is unreachable
    this.isOnline = process.env.START_OFFLINE === 'true' ? false : true;
    this.syncQueue = [];
    this.lastSyncAttempt = null;
    this.syncInterval = null;
    
    // PostgreSQL (Neon) connection - use same connection string as main database config
    this.pgPool = new Pool({
      connectionString: process.env.DATABASE_URL || "postgresql://neondb_owner:npg_4ZPlK1gJEbeo@ep-dark-brook-ae1ictl5-pooler.c-2.us-east-2.aws.neon.tech/neondb?sslmode=require&channel_binding=require",
      ssl: { rejectUnauthorized: false }
    });

    // SQLite local database
    this.sqliteDb = null;
    this.initializeOfflineDatabase();
    
    // Check initial connectivity
    this.checkInitialConnectivity();
    this.startConnectivityMonitoring();
  }

  async initializeOfflineDatabase() {
    try {
      const dbPath = path.join(process.cwd(), 'data', 'offline.db');
      
      // Ensure data directory exists
      const dataDir = path.dirname(dbPath);
      try {
        await fs.access(dataDir);
      } catch {
        await fs.mkdir(dataDir, { recursive: true });
      }

      // Close existing database if open
      if (this.sqliteDb) {
        this.sqliteDb.close();
      }

      // Check if database exists - if yes, repair schema instead of deleting
      let shouldRepairSchema = false;
      try {
        await fs.access(dbPath);
        shouldRepairSchema = true;
        console.log('[Database Manager] Existing offline database found - will repair schema');
      } catch {
        console.log('[Database Manager] Creating new offline database');
      }

      this.sqliteDb = new sqlite3.Database(dbPath);
      
      if (shouldRepairSchema) {
        // Repair existing database schema to match PostgreSQL
        await this.repairOfflineSchema();
      } else {
        // Create new tables matching Neon schema
        await this.createOfflineTables();
      }
      
      await this.initializeOfflineData();
      console.log('[Database Manager] Offline database initialized and schema synchronized');
      
    } catch (error) {
      console.error('[Database Manager] Error initializing offline database:', error);
      throw error;
    }
  }

  async repairOfflineSchema() {
    console.log('[Database Manager] Repairing offline database schema to match PostgreSQL...');
    
    try {
      // First create all tables if they don't exist
      await this.createOfflineTables();
      
      // Now add missing columns to existing tables
      const schemaRepairs = [
        // Users table repairs
        { table: 'users', column: 'updated_at', definition: 'DATETIME' },
        
        // Plans table repairs
        { table: 'plans', column: 'speed', definition: 'VARCHAR(50)' },
        { table: 'plans', column: 'download_speed', definition: 'VARCHAR(50)' },
        { table: 'plans', column: 'upload_speed', definition: 'VARCHAR(50)' },
        { table: 'plans', column: 'updated_at', definition: 'DATETIME' },
        
        // Client plans table repairs
        { table: 'client_plans', column: 'updated_at', definition: 'DATETIME' },
        
        // Billings table repairs
        { table: 'billings', column: 'updated_at', definition: 'DATETIME' },
        { table: 'billings', column: 'billing_date', definition: 'DATE' },
        
        // Payments table repairs
        { table: 'payments', column: 'plan_id', definition: 'INTEGER' },
        { table: 'payments', column: 'reference_number', definition: 'VARCHAR(100)' },
        { table: 'payments', column: 'updated_at', definition: 'DATETIME' },
        { table: 'payments', column: 'method', definition: 'VARCHAR(50)' },
        
        // Company info table repairs
        { table: 'company_info', column: 'company_name', definition: 'VARCHAR(255)' },
        { table: 'company_info', column: 'website', definition: 'VARCHAR(255)' },
        { table: 'company_info', column: 'updated_at', definition: 'DATETIME' },
        { table: 'company_info', column: 'logo_url', definition: 'VARCHAR(500)' },
        
        // MikroTik settings table repairs
        { table: 'mikrotik_settings', column: 'updated_at', definition: 'DATETIME' },
        { table: 'mikrotik_settings', column: 'is_active', definition: 'BOOLEAN DEFAULT 1' },
        
        // Network summary table repairs
        { table: 'network_summary', column: 'total_bandwidth_usage', definition: 'BIGINT DEFAULT 0' },
        { table: 'network_summary', column: 'upload_bandwidth', definition: 'BIGINT DEFAULT 0' },
        { table: 'network_summary', column: 'download_bandwidth', definition: 'BIGINT DEFAULT 0' },
        { table: 'network_summary', column: 'network_uptime_percentage', definition: 'DECIMAL(5,2) DEFAULT 100.00' },
        { table: 'network_summary', column: 'active_connections', definition: 'INTEGER DEFAULT 0' },
        { table: 'network_summary', column: 'failed_connections', definition: 'INTEGER DEFAULT 0' },
        
        // Billings table repairs
        { table: 'billings', column: 'balance', definition: 'DECIMAL(10,2) DEFAULT 0.00' },
        { table: 'billings', column: 'paid_amount', definition: 'DECIMAL(10,2) DEFAULT 0.00' },
        
        // Client plans table repairs
        { table: 'client_plans', column: 'start_date', definition: 'DATE' },
        { table: 'client_plans', column: 'end_date', definition: 'DATE' },
        
        // Inventory assignments table repairs
        { table: 'inventory_assignments', column: 'installation_address', definition: 'TEXT' },
        { table: 'inventory_assignments', column: 'status', definition: 'VARCHAR(20) DEFAULT "active"' },
        
        // Inventory items table repairs
        { table: 'inventory_items', column: 'unit_price', definition: 'DECIMAL(10,2)' },
        { table: 'inventory_items', column: 'quantity_in_stock', definition: 'INTEGER DEFAULT 0' },
        { table: 'inventory_items', column: 'minimum_stock_level', definition: 'INTEGER DEFAULT 0' },
        
        // Payments table repairs
        { table: 'payments', column: 'billing_id', definition: 'INTEGER' },
        
        // Company info table repairs
        { table: 'company_info', column: 'name', definition: 'VARCHAR(255)' }
      ];
      
      for (const repair of schemaRepairs) {
        try {
          // Check if column exists
          const hasColumn = await this.sqliteColumnExists(repair.table, repair.column);
          if (!hasColumn) {
            await this.sqliteQuery(`ALTER TABLE ${repair.table} ADD COLUMN ${repair.column} ${repair.definition}`);
            console.log(`[Database Manager] Added column ${repair.column} to ${repair.table}`);
          }
        } catch (error) {
          console.warn(`[Database Manager] Could not add column ${repair.column} to ${repair.table}:`, error.message);
        }
      }
      
      console.log('[Database Manager] Schema repair completed successfully');
      
    } catch (error) {
      console.error('[Database Manager] Error during schema repair:', error);
      throw error;
    }
  }

  async sqliteColumnExists(tableName, columnName) {
    try {
      const result = await this.sqliteQuery(`PRAGMA table_info(${tableName})`);
      return result.rows.some(row => row.name === columnName);
    } catch (error) {
      return false;
    }
  }

  async getSQLiteTableColumns(tableName) {
    try {
      const result = await this.sqliteQuery(`PRAGMA table_info(${tableName})`);
      return result.rows.map(row => row.name);
    } catch (error) {
      console.error(`[Database Manager] Error getting columns for ${tableName}:`, error);
      return [];
    }
  }
  
  async initializeOfflineData() {
    try {
      // Check if admin user exists
      const result = await this.sqliteQuery(
        "SELECT COUNT(*) as count FROM users WHERE username = 'admin'"
      );
      
      if (result.rows[0].count === 0) {
        // Create default admin user with bcrypt hashed password
        const bcrypt = require('bcryptjs');
        const hashedPassword = await bcrypt.hash('admin123', 10);
        
        await this.sqliteQuery(
          `INSERT INTO users (username, password, email, role) 
           VALUES ('admin', ?, 'admin@isp.local', 'admin')`,
          [hashedPassword]
        );
        
        console.log('[Database Manager] Created default admin user in offline database');
      }
    } catch (error) {
      console.error('[Database Manager] Error initializing offline data:', error);
    }
  }

  async createOfflineTables() {
    return new Promise((resolve, reject) => {
      // SQLite schema that exactly matches PostgreSQL schema structure
      const tables = `
        -- Users table (matches PostgreSQL schema)
        CREATE TABLE IF NOT EXISTS users (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          username VARCHAR(50) UNIQUE NOT NULL,
          password VARCHAR(255) NOT NULL,
          email VARCHAR(100),
          role VARCHAR(20) DEFAULT 'admin',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'synced',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Clients table (matches PostgreSQL schema)
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
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Plans table (matches PostgreSQL schema)
        CREATE TABLE IF NOT EXISTS plans (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          name VARCHAR(100) NOT NULL,
          description TEXT,
          price DECIMAL(10,2) NOT NULL,
          speed VARCHAR(50),
          download_speed VARCHAR(50),
          upload_speed VARCHAR(50),
          status VARCHAR(20) DEFAULT 'active',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Client Plans table (matches PostgreSQL schema)
        CREATE TABLE IF NOT EXISTS client_plans (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          client_id INTEGER NOT NULL,
          plan_id INTEGER NOT NULL,
          start_date DATE,
          end_date DATE,
          status VARCHAR(20) DEFAULT 'active',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Billings table (matches PostgreSQL schema)
        CREATE TABLE IF NOT EXISTS billings (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          client_id INTEGER NOT NULL,
          plan_id INTEGER NOT NULL,
          amount DECIMAL(10,2) NOT NULL,
          billing_date DATE,
          due_date DATE NOT NULL,
          status VARCHAR(20) DEFAULT 'pending',
          balance DECIMAL(10,2) DEFAULT 0.00,
          paid_amount DECIMAL(10,2) DEFAULT 0.00,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Payments table (matches PostgreSQL schema)
        CREATE TABLE IF NOT EXISTS payments (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          client_id INTEGER NOT NULL,
          amount DECIMAL(10,2) NOT NULL,
          payment_date DATE NOT NULL,
          method VARCHAR(50) DEFAULT 'cash',
          notes TEXT,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          billing_id INTEGER,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Sync log table
        CREATE TABLE IF NOT EXISTS sync_log (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          table_name VARCHAR(50) NOT NULL,
          record_id INTEGER NOT NULL,
          action VARCHAR(20) NOT NULL,
          data TEXT,
          sync_status VARCHAR(20) DEFAULT 'pending',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          synced_at DATETIME
        );

        -- Company info table (matches PostgreSQL schema)
        CREATE TABLE IF NOT EXISTS company_info (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          name VARCHAR(255),
          address TEXT,
          phone VARCHAR(50),
          email VARCHAR(100),
          logo_url VARCHAR(500),
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- MikroTik settings table (matches PostgreSQL schema)
        CREATE TABLE IF NOT EXISTS mikrotik_settings (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          host VARCHAR(255) NOT NULL,
          username VARCHAR(100) NOT NULL,
          password VARCHAR(255) NOT NULL,
          port INTEGER DEFAULT 8728,
          is_active BOOLEAN DEFAULT 1,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Network summary table (matches PostgreSQL structure)
        CREATE TABLE IF NOT EXISTS network_summary (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          total_clients INTEGER DEFAULT 0,
          online_clients INTEGER DEFAULT 0,
          offline_clients INTEGER DEFAULT 0,
          total_bandwidth_usage BIGINT DEFAULT 0,
          network_uptime_percentage DECIMAL(5,2) DEFAULT 100.00,
          active_connections INTEGER DEFAULT 0,
          failed_connections INTEGER DEFAULT 0,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          upload_bandwidth BIGINT DEFAULT 0,
          download_bandwidth BIGINT DEFAULT 0,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Inventory Categories table
        CREATE TABLE IF NOT EXISTS inventory_categories (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          name VARCHAR(100) NOT NULL,
          description TEXT,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Inventory Suppliers table
        CREATE TABLE IF NOT EXISTS inventory_suppliers (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          name VARCHAR(100) NOT NULL,
          contact_person VARCHAR(100),
          phone VARCHAR(20),
          email VARCHAR(100),
          address TEXT,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Inventory Items table
        CREATE TABLE IF NOT EXISTS inventory_items (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          name VARCHAR(100) NOT NULL,
          description TEXT,
          category_id INTEGER,
          supplier_id INTEGER,
          sku VARCHAR(50),
          unit_price DECIMAL(10,2),
          quantity_in_stock INTEGER DEFAULT 0,
          minimum_stock_level INTEGER DEFAULT 0,
          status VARCHAR(20) DEFAULT 'active',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Inventory Assignments table
        CREATE TABLE IF NOT EXISTS inventory_assignments (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          item_id INTEGER,
          client_id INTEGER,
          quantity INTEGER NOT NULL,
          assigned_date DATE NOT NULL,
          installation_address TEXT,
          notes TEXT,
          status VARCHAR(20) DEFAULT 'active',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Inventory Movements table
        CREATE TABLE IF NOT EXISTS inventory_movements (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          item_id INTEGER,
          movement_type VARCHAR(20) NOT NULL,
          quantity INTEGER NOT NULL,
          reference_type VARCHAR(50),
          reference_id INTEGER,
          notes TEXT,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Monitoring Groups table (updated structure)
        CREATE TABLE IF NOT EXISTS monitoring_groups (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          group_name VARCHAR(100) NOT NULL,
          group_data TEXT NOT NULL,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Monitoring Categories table (updated structure)
        CREATE TABLE IF NOT EXISTS monitoring_categories (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          category_name VARCHAR(100) NOT NULL,
          subcategory_name VARCHAR(100),
          group_ids TEXT,
          category_index INTEGER,
          subcategory_index INTEGER,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
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
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Asset Collections table
        CREATE TABLE IF NOT EXISTS asset_collections (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          asset_id INTEGER,
          collection_date DATE NOT NULL,
          amount DECIMAL(10,2) NOT NULL,
          collector_name VARCHAR(100),
          notes TEXT,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Asset Subitems table
        CREATE TABLE IF NOT EXISTS asset_subitems (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          asset_id INTEGER,
          inventory_item_id INTEGER,
          quantity INTEGER NOT NULL DEFAULT 1,
          notes TEXT,
          deployment_date DATE,
          status VARCHAR(20) DEFAULT 'deployed',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Interface stats table
        CREATE TABLE IF NOT EXISTS interface_stats (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          interface_name VARCHAR(255) NOT NULL,
          rx_bytes BIGINT DEFAULT 0,
          tx_bytes BIGINT DEFAULT 0,
          collected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Scheduler settings table
        CREATE TABLE IF NOT EXISTS scheduler_settings (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          setting_key VARCHAR(100) NOT NULL,
          setting_value VARCHAR(100) NOT NULL,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );
      `;

      this.sqliteDb.exec(tables, (error) => {
        if (error) {
          reject(error);
        } else {
          resolve();
        }
      });
    });
  }

  async checkInitialConnectivity() {
    if (process.env.START_OFFLINE === 'true') {
      console.log('[Database Manager] 📱 Starting in offline mode as configured');
      this.isOnline = false;
      return;
    }

    console.log('[Database Manager] 🔍 Checking initial database connectivity...');
    const isConnected = await this.testConnectivity();
    
    if (!isConnected) {
      console.log('[Database Manager] ⚠️ Cannot connect to Neon database - starting in offline mode');
      console.log('[Database Manager] 💾 All data will be saved locally until connection is restored');
      this.isOnline = false;
    } else {
      console.log('[Database Manager] 🌐 Connected to cloud database - starting in online mode');
      this.isOnline = true;
    }
  }

  async syncUserToOffline(username) {
    if (!username) return;
    
    try {
      // Get user from PostgreSQL
      const client = await this.pgPool.connect();
      const pgResult = await client.query(
        'SELECT * FROM users WHERE username = $1',
        [username]
      );
      client.release();
      
      if (pgResult.rows.length > 0) {
        const user = pgResult.rows[0];
        
        // Check if user exists in SQLite
        const sqliteResult = await this.sqliteQuery(
          'SELECT COUNT(*) as count FROM users WHERE username = ?',
          [username]
        );
        
        if (sqliteResult.rows[0].count === 0) {
          // Insert user into SQLite
          await this.sqliteQuery(
            `INSERT INTO users (username, password, email, role) 
             VALUES (?, ?, ?, ?)`,
            [user.username, user.password, user.email, user.role || 'admin']
          );
          console.log(`[Database Manager] Synced user '${username}' to offline database`);
        } else {
          // Update existing user
          await this.sqliteQuery(
            `UPDATE users SET password = ?, email = ?, role = ?
             WHERE username = ?`,
            [user.password, user.email, user.role || 'admin', username]
          );
          console.log(`[Database Manager] Updated user '${username}' in offline database`);
        }
      }
    } catch (error) {
      console.error('[Database Manager] Error syncing user to offline:', error);
      throw error;
    }
  }

  async testConnectivity() {
    try {
      const client = await this.pgPool.connect();
      await client.query('SELECT 1');
      client.release();
      console.log('[Database Manager] ✅ Neon database connection successful');
      return true;
    } catch (error) {
      console.error('[Database Manager] ❌ Neon database connection failed:', error.message);
      console.error('[Database Manager] Connection string:', this.pgPool.options.connectionString?.replace(/:[^@]+@/, ':****@'));
      return false;
    }
  }

  async startConnectivityMonitoring() {
    // Check connectivity every 30 seconds
    this.connectivityInterval = setInterval(async () => {
      const wasOnline = this.isOnline;
      this.isOnline = await this.testConnectivity();
      
      if (!wasOnline && this.isOnline) {
        console.log('[Database Manager] 🟢 Back online - Starting sync...');
        await this.syncOfflineData();
      } else if (wasOnline && !this.isOnline) {
        console.log('[Database Manager] 🔴 Offline mode activated');
      }
    }, 30000);

    // Initial connectivity check
    this.isOnline = await this.testConnectivity();
    console.log(`[Database Manager] Initial connectivity: ${this.isOnline ? 'Online' : 'Offline'}`);
  }

  async getConnection() {
    if (this.isOnline) {
      try {
        return await this.pgPool.connect();
      } catch (error) {
        console.warn('[Database Manager] PostgreSQL connection failed, switching to offline mode');
        this.isOnline = false;
        return this.sqliteDb;
      }
    }
    return this.sqliteDb;
  }

  async query(text, params = []) {
    if (this.isOnline) {
      try {
        const client = await this.pgPool.connect();
        const result = await client.query(text, params);
        client.release();
        return result;
      } catch (error) {
        console.warn('[Database Manager] PostgreSQL query failed, falling back to SQLite');
        this.isOnline = false;
        return this.sqliteQuery(text, params);
      }
    } else {
      return this.sqliteQuery(text, params);
    }
  }

  async sqliteQuery(text, params = []) {
    return new Promise((resolve, reject) => {
      // Convert PostgreSQL syntax to SQLite
      let sqliteText = text
        .replace(/\$(\d+)/g, '?')  // Replace $1, $2, etc. with ?
        .replace(/RETURNING \*/g, '')  // Remove RETURNING clause
        .replace(/SERIAL/g, 'INTEGER')  // Replace SERIAL with INTEGER
        .replace(/CURRENT_TIMESTAMP/g, 'datetime("now")')  // Fix timestamp function
        .replace(/NOW\(\)/g, 'datetime("now")');

      if (sqliteText.toUpperCase().includes('INSERT') || 
          sqliteText.toUpperCase().includes('UPDATE') || 
          sqliteText.toUpperCase().includes('DELETE')) {
        
        // For INSERT/UPDATE/DELETE, log for sync
        this.logForSync(sqliteText, params);
        
        this.sqliteDb.run(sqliteText, params, function(error) {
          if (error) {
            reject(error);
          } else {
            resolve({
              rows: [],
              rowCount: this.changes,
              insertId: this.lastID
            });
          }
        });
      } else {
        // For SELECT queries
        this.sqliteDb.all(sqliteText, params, (error, rows) => {
          if (error) {
            reject(error);
          } else {
            resolve({ rows: rows || [], rowCount: rows ? rows.length : 0 });
          }
        });
      }
    });
  }

  logForSync(query, params) {
    if (!this.isOnline) {
      // Add to sync queue for later processing
      this.syncQueue.push({
        query: query,
        params: params,
        timestamp: new Date().toISOString()
      });
    }
  }

  async syncOfflineData() {
    if (!this.isOnline || this.syncQueue.length === 0) {
      return;
    }

    console.log(`[Database Manager] Syncing ${this.syncQueue.length} offline operations...`);
    
    let syncedCount = 0;
    let errorCount = 0;

    for (const operation of this.syncQueue) {
      try {
        const client = await this.pgPool.connect();
        await client.query(operation.query, operation.params);
        client.release();
        syncedCount++;
      } catch (error) {
        console.error('[Database Manager] Sync error:', error);
        errorCount++;
      }
    }

    // Clear successfully synced operations
    this.syncQueue = this.syncQueue.slice(syncedCount);
    
    console.log(`[Database Manager] Sync completed: ${syncedCount} synced, ${errorCount} errors`);
    
    if (syncedCount > 0) {
      // Update sync status in SQLite
      await this.markAsSynced();
    }
  }

  async markAsSynced() {
    const tables = ['clients', 'plans', 'client_plans', 'billings', 'payments', 'company_info', 'mikrotik_settings'];
    
    for (const table of tables) {
      try {
        await this.sqliteQuery(
          `UPDATE ${table} SET sync_status = 'synced' WHERE sync_status = 'pending'`
        );
      } catch (error) {
        console.error(`[Database Manager] Error marking ${table} as synced:`, error);
      }
    }
  }

  getStatus() {
    return {
      isOnline: this.isOnline,
      syncQueueLength: this.syncQueue.length,
      lastSyncAttempt: this.lastSyncAttempt,
      databaseMode: this.isOnline ? 'PostgreSQL (Neon)' : 'SQLite (Offline)'
    };
  }

  async downloadDataToOffline() {
    console.log('[Database Manager] Starting data download from PostgreSQL to SQLite...');
    
    if (!this.isOnline) {
      throw new Error('Cannot download data while offline. Switch to online mode first.');
    }
    
    const tables = [
      'users', 'clients', 'plans', 'client_plans', 'billings', 'payments', 
      'company_info', 'mikrotik_settings', 'inventory_categories', 'inventory_suppliers',
      'inventory_items', 'inventory_assignments', 'inventory_movements', 
      'monitoring_groups', 'monitoring_categories', 'assets', 'asset_collections',
      'asset_subitems', 'network_summary', 'interface_stats', 'scheduler_settings'
    ];
    const downloadStats = {
      totalTables: tables.length,
      completedTables: 0,
      totalRecords: 0,
      downloadedRecords: 0,
      errors: []
    };
    
    try {
      // Get PostgreSQL connection
      const pgClient = await this.pgPool.connect();
      
      for (const table of tables) {
        try {
          console.log(`[Database Manager] Downloading table: ${table}`);
          
          // Get all records from PostgreSQL
          const pgResult = await pgClient.query(`SELECT * FROM ${table}`);
          downloadStats.totalRecords += pgResult.rows.length;
          
          if (pgResult.rows.length > 0) {
            // Clear existing data in SQLite for this table
            await this.sqliteQuery(`DELETE FROM ${table}`);
            console.log(`[Database Manager] Cleared existing data in ${table}`);
            
            // Insert each record into SQLite
            for (const row of pgResult.rows) {
              try {
                // Get SQLite table columns to filter out non-existent columns
                const sqliteColumns = await this.getSQLiteTableColumns(table);
                
                // Filter row data to only include columns that exist in SQLite
                const filteredRow = {};
                Object.keys(row).forEach(key => {
                  if (sqliteColumns.includes(key)) {
                    filteredRow[key] = row[key];
                  }
                });
                
                const columns = Object.keys(filteredRow).join(', ');
                const placeholders = Object.keys(filteredRow).map(() => '?').join(', ');
                const values = Object.values(filteredRow);
                
                // Convert PostgreSQL values to SQLite compatible values
                const sqliteValues = values.map(value => {
                  if (value instanceof Date) {
                    return value.toISOString();
                  }
                  if (typeof value === 'object' && value !== null) {
                    return JSON.stringify(value);
                  }
                  return value;
                });
                
                if (columns && sqliteValues.length > 0) {
                  await this.sqliteQuery(
                    `INSERT INTO ${table} (${columns}) VALUES (${placeholders})`,
                    sqliteValues
                  );
                  downloadStats.downloadedRecords++;
                }
              } catch (insertError) {
                console.error(`[Database Manager] Error inserting record in ${table}:`, insertError);
                downloadStats.errors.push(`${table}: ${insertError.message}`);
              }
            }
          }
          
          downloadStats.completedTables++;
          console.log(`[Database Manager] Downloaded ${pgResult.rows.length} records from ${table}`);
          
        } catch (tableError) {
          console.error(`[Database Manager] Error downloading table ${table}:`, tableError);
          downloadStats.errors.push(`${table}: ${tableError.message}`);
          downloadStats.completedTables++;
        }
      }
      
      pgClient.release();
      
      console.log(`[Database Manager] Download completed: ${downloadStats.downloadedRecords}/${downloadStats.totalRecords} records`);
      
      return {
        stats: downloadStats,
        summary: `Downloaded ${downloadStats.downloadedRecords} records from ${downloadStats.completedTables} tables`
      };
      
    } catch (error) {
      console.error('[Database Manager] Error during data download:', error);
      throw error;
    }
  }

  async close() {
    if (this.connectivityInterval) {
      clearInterval(this.connectivityInterval);
    }
    
    if (this.sqliteDb) {
      this.sqliteDb.close();
    }
    
    await this.pgPool.end();
  }
}

module.exports = new DatabaseManager();
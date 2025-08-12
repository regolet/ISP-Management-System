const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const fs = require('fs').promises;
const bcrypt = require('bcryptjs');

class SQLiteDatabaseManager {
  constructor() {
    this.sqliteDb = null;
    this.initializeSQLiteDatabase();
  }

  async initializeSQLiteDatabase() {
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

      // Check if database exists
      let shouldCreateTables = false;
      try {
        await fs.access(dbPath);
        console.log('[SQLite Manager] Existing database found - connecting...');
      } catch {
        console.log('[SQLite Manager] Creating new SQLite database...');
        shouldCreateTables = true;
      }

      this.sqliteDb = new sqlite3.Database(dbPath);
      
      if (shouldCreateTables) {
        await this.createSQLiteTables();
        await this.initializeDefaultData();
      }
      
      console.log('[SQLite Manager] SQLite database ready');
      
    } catch (error) {
      console.error('[SQLite Manager] Error initializing SQLite database:', error);
      throw error;
    }
  }

  async createSQLiteTables() {
    console.log('[SQLite Manager] Creating SQLite database tables...');
    
    const tables = [
      // Users table
      `CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        role VARCHAR(20) DEFAULT 'user',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`,

      // Clients table
      `CREATE TABLE IF NOT EXISTS clients (
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
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`,

      // Plans table
      `CREATE TABLE IF NOT EXISTS plans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        speed VARCHAR(50),
        download_speed VARCHAR(50),
        upload_speed VARCHAR(50),
        status VARCHAR(20) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`,

      // Client Plans table
      `CREATE TABLE IF NOT EXISTS client_plans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
        plan_id INTEGER REFERENCES plans(id) ON DELETE CASCADE,
        status VARCHAR(20) DEFAULT 'active',
        anchor_day INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`,

      // Billings table
      `CREATE TABLE IF NOT EXISTS billings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
        plan_id INTEGER REFERENCES plans(id) ON DELETE CASCADE,
        amount DECIMAL(10,2) NOT NULL,
        due_date DATE NOT NULL,
        billing_month DATE NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        balance DECIMAL(10,2) DEFAULT 0.00,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`,

      // Payments table
      `CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
        plan_id INTEGER REFERENCES plans(id) ON DELETE CASCADE,
        amount DECIMAL(10,2) NOT NULL,
        payment_date DATE NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'cash',
        reference_number VARCHAR(100),
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`,

      // MikroTik Settings table
      `CREATE TABLE IF NOT EXISTS mikrotik_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        host VARCHAR(255) NOT NULL,
        username VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        port INTEGER DEFAULT 8728,
        is_active INTEGER DEFAULT 1,
        sync_status VARCHAR(20) DEFAULT 'pending',
        last_modified DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`,

      // Company Info table
      `CREATE TABLE IF NOT EXISTS company_info (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        company_name VARCHAR(255),
        address TEXT,
        phone VARCHAR(50),
        email VARCHAR(100),
        website VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`,

      // Assets table
      `CREATE TABLE IF NOT EXISTS assets (
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
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`,

      // Asset Collections table
      `CREATE TABLE IF NOT EXISTS asset_collections (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        asset_id INTEGER REFERENCES assets(id) ON DELETE CASCADE,
        collection_date DATE NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        collector_name VARCHAR(100),
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`,

      // Network Summary table
      `CREATE TABLE IF NOT EXISTS network_summary (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        total_clients INTEGER DEFAULT 0,
        online_clients INTEGER DEFAULT 0,
        offline_clients INTEGER DEFAULT 0,
        total_bandwidth_usage BIGINT DEFAULT 0,
        upload_bandwidth BIGINT DEFAULT 0,
        download_bandwidth BIGINT DEFAULT 0,
        network_uptime_percentage DECIMAL(5,2) DEFAULT 100.00,
        active_connections INTEGER DEFAULT 0,
        failed_connections INTEGER DEFAULT 0,
        data_collected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`,

      // Interface stats table
      `CREATE TABLE IF NOT EXISTS interface_stats (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        interface_name VARCHAR(255) NOT NULL,
        rx_bytes BIGINT DEFAULT 0,
        tx_bytes BIGINT DEFAULT 0,
        collected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(interface_name, collected_at)
      )`,

      // Scheduler Settings table
      `CREATE TABLE IF NOT EXISTS scheduler_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key VARCHAR(100) NOT NULL,
        setting_value VARCHAR(100) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`,

      // Monitoring Groups table
      `CREATE TABLE IF NOT EXISTS monitoring_groups (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        group_name VARCHAR(100) NOT NULL,
        group_data TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`,

      // Monitoring Categories table
      `CREATE TABLE IF NOT EXISTS monitoring_categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_name VARCHAR(100) NOT NULL,
        subcategory_name VARCHAR(100),
        group_ids TEXT,
        category_index INTEGER,
        subcategory_index INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`,

      // Tickets table
      `CREATE TABLE IF NOT EXISTS tickets (
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
        resolved_at DATETIME,
        due_date DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`,

      // Ticket Comments table
      `CREATE TABLE IF NOT EXISTS ticket_comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ticket_id INTEGER REFERENCES tickets(id) ON DELETE CASCADE,
        user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
        comment TEXT NOT NULL,
        is_internal BOOLEAN DEFAULT false,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`,

      // Ticket History table
      `CREATE TABLE IF NOT EXISTS ticket_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ticket_id INTEGER REFERENCES tickets(id) ON DELETE CASCADE,
        user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
        action VARCHAR(50) NOT NULL,
        old_value TEXT,
        new_value TEXT,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )`
    ];

    // Create each table
    for (const tableSQL of tables) {
      await this.sqliteQuery(tableSQL);
    }
  }

  async initializeDefaultData() {
    console.log('[SQLite Manager] Inserting default data...');
    
    // Create default admin user
    const hashedPassword = await bcrypt.hash('admin123', 10);
    await this.sqliteQuery(
      'INSERT OR IGNORE INTO users (username, password, email, role) VALUES (?, ?, ?, ?)',
      ['admin', hashedPassword, 'admin@isp.local', 'admin']
    );

    // Insert MikroTik settings
    await this.sqliteQuery(
      'INSERT OR IGNORE INTO mikrotik_settings (id, host, username, password, port) VALUES (1, ?, ?, ?, ?)',
      ['192.168.3.2', 'admin', 'password123', 8728]
    );

    // Insert company info
    await this.sqliteQuery(
      'INSERT OR IGNORE INTO company_info (id, company_name, phone, email) VALUES (1, ?, ?, ?)',
      ['ISP Management System', '+1-555-0123', 'admin@isp.local']
    );

    // Insert scheduler settings
    const schedulerSettings = [
      ['collection_interval', '10'],
      ['timezone', 'Asia/Manila']
    ];
    
    for (const [key, value] of schedulerSettings) {
      await this.sqliteQuery(
        'INSERT OR IGNORE INTO scheduler_settings (setting_key, setting_value) VALUES (?, ?)',
        [key, value]
      );
    }

    console.log('[SQLite Manager] Default data initialized');
  }

  // Execute SQLite query with parameters
  async sqliteQuery(sql, params = []) {
    return new Promise((resolve, reject) => {
      if (!this.sqliteDb) {
        reject(new Error('SQLite database not initialized'));
        return;
      }

      // Handle SELECT queries
      if (sql.trim().toUpperCase().startsWith('SELECT')) {
        this.sqliteDb.all(sql, params, (err, rows) => {
          if (err) {
            reject(err);
          } else {
            resolve({ rows: rows || [] });
          }
        });
      } 
      // Handle INSERT/UPDATE/DELETE queries
      else {
        this.sqliteDb.run(sql, params, function(err) {
          if (err) {
            reject(err);
          } else {
            resolve({ 
              rows: [], 
              rowCount: this.changes,
              lastID: this.lastID 
            });
          }
        });
      }
    });
  }

  // Main query method used by the pool interface
  async query(text, params = []) {
    return await this.sqliteQuery(text, params);
  }

  // Get database status
  getStatus() {
    return {
      databaseMode: 'SQLite Only',
      isOnline: false,
      dbPath: path.join(process.cwd(), 'data', 'offline.db')
    };
  }

  // Close database connection
  close() {
    if (this.sqliteDb) {
      this.sqliteDb.close();
      console.log('[SQLite Manager] Database connection closed');
    }
  }
}

// Create and export singleton instance
const sqliteManager = new SQLiteDatabaseManager();
module.exports = sqliteManager;
const { Pool } = require('pg');
const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const fs = require('fs').promises;

class DatabaseManager {
  constructor() {
    this.isOnline = true;
    this.syncQueue = [];
    this.lastSyncAttempt = null;
    this.syncInterval = null;
    
    // PostgreSQL (Neon) connection
    this.pgPool = new Pool({
      connectionString: 'postgresql://neondb_owner:npg_WXhT8BM1xGNWJc0GdFAq0UL0VcKYP3BE@ep-broad-pine-a1e5ydc5.ap-southeast-1.aws.neon.tech/neondb?sslmode=require',
      ssl: { rejectUnauthorized: false }
    });

    // SQLite local database
    this.sqliteDb = null;
    this.initializeOfflineDatabase();
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

      this.sqliteDb = new sqlite3.Database(dbPath);
      
      // Create tables matching Neon schema
      await this.createOfflineTables();
      console.log('[Database Manager] Offline database initialized');
      
    } catch (error) {
      console.error('[Database Manager] Error initializing offline database:', error);
    }
  }

  async createOfflineTables() {
    return new Promise((resolve, reject) => {
      const tables = `
        -- Users table
        CREATE TABLE IF NOT EXISTS users (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          username VARCHAR(50) UNIQUE NOT NULL,
          password VARCHAR(255) NOT NULL,
          email VARCHAR(100),
          role VARCHAR(20) DEFAULT 'admin',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'synced',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Clients table
        CREATE TABLE IF NOT EXISTS clients (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          name VARCHAR(100) NOT NULL,
          email VARCHAR(100),
          phone VARCHAR(20),
          address TEXT,
          status VARCHAR(20) DEFAULT 'active',
          payment_status VARCHAR(20) DEFAULT 'current',
          balance DECIMAL(10,2) DEFAULT 0.00,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Plans table
        CREATE TABLE IF NOT EXISTS plans (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          name VARCHAR(100) NOT NULL,
          description TEXT,
          price DECIMAL(10,2) NOT NULL,
          speed_upload VARCHAR(20),
          speed_download VARCHAR(20),
          data_limit VARCHAR(50),
          status VARCHAR(20) DEFAULT 'active',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Client Plans table
        CREATE TABLE IF NOT EXISTS client_plans (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          client_id INTEGER NOT NULL,
          plan_id INTEGER NOT NULL,
          status VARCHAR(20) DEFAULT 'active',
          start_date DATE DEFAULT CURRENT_DATE,
          end_date DATE,
          anchor_day INTEGER DEFAULT 1,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Billings table
        CREATE TABLE IF NOT EXISTS billings (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          client_id INTEGER NOT NULL,
          plan_id INTEGER NOT NULL,
          amount DECIMAL(10,2) NOT NULL,
          billing_month VARCHAR(7) NOT NULL,
          due_date DATE NOT NULL,
          status VARCHAR(20) DEFAULT 'pending',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Payments table
        CREATE TABLE IF NOT EXISTS payments (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          client_id INTEGER NOT NULL,
          billing_id INTEGER,
          amount DECIMAL(10,2) NOT NULL,
          payment_method VARCHAR(50) DEFAULT 'cash',
          payment_date DATE DEFAULT CURRENT_DATE,
          notes TEXT,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
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

        -- Company info table
        CREATE TABLE IF NOT EXISTS company_info (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          name VARCHAR(255) NOT NULL,
          address TEXT,
          phone VARCHAR(50),
          email VARCHAR(100),
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- MikroTik settings table
        CREATE TABLE IF NOT EXISTS mikrotik_settings (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          host VARCHAR(100) NOT NULL,
          username VARCHAR(50) NOT NULL,
          password VARCHAR(255) NOT NULL,
          port INTEGER DEFAULT 8728,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending',
          last_modified DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Network summary table (for monitoring)
        CREATE TABLE IF NOT EXISTS network_summary (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          total_clients INTEGER DEFAULT 0,
          online_clients INTEGER DEFAULT 0,
          offline_clients INTEGER DEFAULT 0,
          avg_online_clients INTEGER DEFAULT 0,
          total_upload_mbps DECIMAL(10,2) DEFAULT 0.00,
          total_download_mbps DECIMAL(10,2) DEFAULT 0.00,
          avg_upload_mbps DECIMAL(10,2) DEFAULT 0.00,
          avg_download_mbps DECIMAL(10,2) DEFAULT 0.00,
          peak_upload_mbps DECIMAL(10,2) DEFAULT 0.00,
          peak_download_mbps DECIMAL(10,2) DEFAULT 0.00,
          collection_time DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending'
        );

        -- Scheduler settings table
        CREATE TABLE IF NOT EXISTS scheduler_settings (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          setting_key VARCHAR(100) NOT NULL,
          setting_value VARCHAR(100) NOT NULL,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          sync_status VARCHAR(20) DEFAULT 'pending'
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

  async testConnectivity() {
    try {
      const client = await this.pgPool.connect();
      await client.query('SELECT 1');
      client.release();
      return true;
    } catch (error) {
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
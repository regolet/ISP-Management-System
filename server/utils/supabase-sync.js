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
        'inventory_assignments',
        'inventory_movements',
        'client_plans',
        'billings',
        'payments',
        'monitoring_groups',
        'monitoring_categories',
        'tickets',
        'ticket_comments',
        'ticket_attachments',
        'ticket_history',
        'assets',
        'asset_collections',
        'asset_subitems',
        'network_summary',
        'interface_stats',
        'scheduler_settings'
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
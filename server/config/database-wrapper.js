const pool = require('./database');
const dbManager = require('../utils/database-manager');

// Create a wrapper that uses database manager for offline/online handling
const databaseWrapper = {
  // Wrap the connect method to use database manager
  async connect() {
    if (!dbManager.isOnline) {
      console.log('[Database Wrapper] Offline mode - using SQLite');
      // Return a mock client that uses database manager
      return {
        query: async (text, params) => {
          return await dbManager.query(text, params);
        },
        release: () => {
          // No-op for SQLite
        }
      };
    }
    
    // Online mode - use regular PostgreSQL pool
    return await pool.connect();
  },
  
  // Direct query method
  async query(text, params) {
    return await dbManager.query(text, params);
  }
};

module.exports = databaseWrapper;
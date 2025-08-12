// SQLite-only database configuration
const sqliteManager = require('../utils/sqlite-database-manager');

// Create a simple pool-like interface that always uses SQLite
const sqlitePool = {
  connect: async function() {
    console.log('[Database Pool] Using SQLite database');
    // Return a client interface that uses SQLite manager
    return {
      query: async (text, params) => {
        return await sqliteManager.query(text, params);
      },
      release: () => {
        // No-op for SQLite
      }
    };
  },
  
  query: async function(text, params) {
    return await sqliteManager.query(text, params);
  }
};

module.exports = sqlitePool;
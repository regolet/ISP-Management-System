const { Pool } = require('pg');

// Database configuration
const pool = new Pool({
  connectionString: process.env.DATABASE_URL || "postgresql://neondb_owner:npg_4ZPlK1gJEbeo@ep-dark-brook-ae1ictl5-pooler.c-2.us-east-2.aws.neon.tech/neondb?sslmode=require&channel_binding=require",
  ssl: {
    rejectUnauthorized: false
  }
});

// Store original methods
const originalConnect = pool.connect.bind(pool);
const originalQuery = pool.query.bind(pool);

// Override connect method to check offline/online status
pool.connect = async function() {
  // Lazy load to avoid circular dependency
  const dbManager = require('../utils/database-manager');
  
  if (!dbManager.isOnline) {
    console.log('[Database Pool] Intercepted - Using offline mode (SQLite)');
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
  
  // Online mode - use original connect
  return await originalConnect();
};

// Override query method for direct queries
pool.query = async function(text, params) {
  const dbManager = require('../utils/database-manager');
  return await dbManager.query(text, params);
};

module.exports = pool;
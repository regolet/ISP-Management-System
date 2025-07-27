const cron = require('node-cron');
const pool = require('../config/database');
const RouterOSAPI = require('routeros-api').RouterOSAPI;

// Helper function to get current Philippine time
function getPhilippineTime() {
  const philippineTime = new Date().toLocaleString("en-US", {timeZone: "Asia/Manila"});
  return new Date(philippineTime);
}

// Get scheduler interval from database or default
async function getSchedulerInterval() {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT setting_value FROM scheduler_settings 
      WHERE setting_key = 'collection_interval' 
      ORDER BY created_at DESC LIMIT 1
    `);
    client.release();
    
    if (result.rows.length > 0) {
      return result.rows[0].setting_value;
    }
  } catch (error) {
    console.log('[Scheduler] No scheduler settings table found, using default interval');
  }
  
  return '10s'; // Default fallback
}

// Convert interval setting to cron pattern
function intervalToCron(interval) {
  switch (interval) {
    case '10s': return '*/10 * * * * *';
    case '30s': return '*/30 * * * * *';
    case '1m': return '0 * * * * *';
    case '5m': return '0 */5 * * * *';
    case '15m': return '0 */15 * * * *';
    case '30m': return '0 */30 * * * *';
    default: return '*/10 * * * * *'; // Default to 10 seconds
  }
}

// Function to collect network data (same logic as in network-collector route)
async function collectNetworkData() {
  const startTime = getPhilippineTime();
  console.log(`[Scheduler] Starting automatic network data collection at ${startTime.toLocaleTimeString()} Philippine Time...`);
  
  try {
    console.log('[Scheduler] Connecting to database...');
    const client = await pool.connect();
    
    // Get active MikroTik settings
    console.log('[Scheduler] Checking MikroTik settings...');
    const settingsResult = await client.query('SELECT * FROM mikrotik_settings ORDER BY created_at DESC LIMIT 1');
    if (settingsResult.rows.length === 0) {
      console.log('[Scheduler] No MikroTik settings found. Using fallback data collection...');
      
      // Use fallback collection instead of skipping
      const clientStats = await client.query(`
        SELECT 
          COUNT(*) as total_clients,
          COUNT(CASE WHEN status = 'active' THEN 1 END) as online_clients
        FROM clients
      `);
      
      const stats = clientStats.rows[0];
      const totalClients = parseInt(stats.total_clients) || 0;
      const onlineClients = parseInt(stats.online_clients) || 0;
      const offlineClients = totalClients - onlineClients;
      
      const uploadBandwidth = onlineClients * 125000; // 1 Mbps upload per client
      const downloadBandwidth = onlineClients * 250000; // 2 Mbps download per client
      const totalBandwidthUsage = uploadBandwidth + downloadBandwidth;
      
      // Get current Philippine time
      const currentTime = getPhilippineTime();
      
      await client.query(`
        INSERT INTO network_summary (
          total_clients, online_clients, offline_clients,
          total_bandwidth_usage, upload_bandwidth, download_bandwidth,
          network_uptime_percentage, active_connections, failed_connections,
          data_collected_at
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
      `, [
        totalClients, onlineClients, offlineClients,
        totalBandwidthUsage, uploadBandwidth, downloadBandwidth,
        99.0, onlineClients, 0, currentTime
      ]);
      
      client.release();
      console.log(`[Scheduler] Fallback data collected - Clients: ${totalClients}, Online: ${onlineClients}, Upload: ${Math.round(uploadBandwidth/125000)}Mbps, Download: ${Math.round(downloadBandwidth/125000)}Mbps`);
      return;
    }
    
    const settings = settingsResult.rows[0];
    console.log(`[Scheduler] Connecting to MikroTik at ${settings.host}...`);
    
    // Connect to MikroTik with timeout
    const connection = new RouterOSAPI({
      host: settings.host,
      user: settings.username,
      password: settings.password,
      port: settings.port || 8728,
      timeout: 10000 // 10 second timeout
    });

    try {
      // Get basic client counts from MikroTik
      console.log('[Scheduler] Attempting MikroTik connection for client counts...');
      await Promise.race([
        connection.connect(),
        new Promise((_, reject) => setTimeout(() => reject(new Error('Connection timeout')), 10000))
      ]);
      
      console.log('[Scheduler] Connected! Fetching client data...');
      // Get PPP accounts and active connections for counts only
      const [pppAccounts, pppActive] = await Promise.all([
        connection.write('/ppp/secret/print'),
        connection.write('/ppp/active/print')
      ]);
      
      console.log(`[Scheduler] Data fetched - Accounts: ${pppAccounts?.length || 0}, Active: ${pppActive?.length || 0}`);
      connection.close();
      
      // Calculate basic statistics
      const totalClients = Array.isArray(pppAccounts) ? pppAccounts.length : 0;
      const onlineClients = Array.isArray(pppActive) ? pppActive.length : 0;
      const offlineClients = totalClients - onlineClients;
      
      // Get bandwidth totals from our new API endpoint (same logic as monitoring.html)
      console.log('[Scheduler] Calling bandwidth totals API...');
      const axios = require('axios');
      let uploadBandwidthBytes = 0;
      let downloadBandwidthBytes = 0;
      
      try {
        const response = await axios.get('http://localhost:3000/api/network-totals/bandwidth-totals', {
          timeout: 30000
        });
        
        if (response.data.success) {
          // Convert Mbps to bytes per second
          uploadBandwidthBytes = Math.round(response.data.totalUploadMbps * 125000); // Mbps to bytes/sec
          downloadBandwidthBytes = Math.round(response.data.totalDownloadMbps * 125000);
          console.log(`[Scheduler] API returned: ${response.data.totalUploadMbps} Mbps upload, ${response.data.totalDownloadMbps} Mbps download`);
        } else {
          console.log('[Scheduler] API failed, using fallback bandwidth calculation');
          throw new Error(response.data.error || 'API failed');
        }
      } catch (apiError) {
        console.error('[Scheduler] Bandwidth API error:', apiError.message);
        // Fallback to simple estimates
        uploadBandwidthBytes = Math.min(onlineClients * 125000, 1250000); // Cap at 10 Mbps total
        downloadBandwidthBytes = Math.min(onlineClients * 250000, 2500000); // Cap at 20 Mbps total
        console.log('[Scheduler] Using fallback bandwidth estimates');
      }
      
      let uploadBandwidth = uploadBandwidthBytes;
      let downloadBandwidth = downloadBandwidthBytes;
      let totalBandwidthUsage = uploadBandwidth + downloadBandwidth;
      
      console.log(`[Scheduler] Final bandwidth - Upload: ${Math.round(uploadBandwidth/125000)}Mbps, Download: ${Math.round(downloadBandwidth/125000)}Mbps`);
      
      // Get current Philippine time
      const currentTime = getPhilippineTime();
      
      // Insert data into network_summary table
      await client.query(`
        INSERT INTO network_summary (
          total_clients, online_clients, offline_clients,
          total_bandwidth_usage, upload_bandwidth, download_bandwidth,
          network_uptime_percentage, active_connections, failed_connections,
          data_collected_at
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
      `, [
        totalClients,
        onlineClients,
        offlineClients,
        totalBandwidthUsage,
        uploadBandwidth,
        downloadBandwidth,
        99.9,
        onlineClients,
        0,
        currentTime
      ]);
      
      client.release();
      const endTime = getPhilippineTime();
      const duration = endTime - startTime;
      console.log(`[Scheduler] ✅ Network data collected and SAVED to database in ${duration}ms`);
      console.log(`[Scheduler] SAVED Stats at ${currentTime.toLocaleTimeString()} Philippine Time: Clients: ${totalClients}, Online: ${onlineClients}, Upload: ${Math.round(uploadBandwidth/125000)}Mbps, Download: ${Math.round(downloadBandwidth/125000)}Mbps`);
      
    } catch (mikrotikError) {
      client.release();
      console.error('[Scheduler] MikroTik connection error:', mikrotikError.message);
      
      // Fallback to database estimates
      try {
        const fallbackClient = await pool.connect();
        
        const clientStats = await fallbackClient.query(`
          SELECT 
            COUNT(*) as total_clients,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as online_clients
          FROM clients
        `);
        
        const stats = clientStats.rows[0];
        const totalClients = parseInt(stats.total_clients) || 0;
        const onlineClients = parseInt(stats.online_clients) || 0;
        const offlineClients = totalClients - onlineClients;
        
        const uploadBandwidth = onlineClients * 125000; // 1 Mbps upload per client
        const downloadBandwidth = onlineClients * 250000; // 2 Mbps download per client
        const totalBandwidthUsage = uploadBandwidth + downloadBandwidth;
        
        // Get current Philippine time for fallback
        const currentTime = getPhilippineTime();
        
        await fallbackClient.query(`
          INSERT INTO network_summary (
            total_clients, online_clients, offline_clients,
            total_bandwidth_usage, upload_bandwidth, download_bandwidth,
            network_uptime_percentage, active_connections, failed_connections,
            data_collected_at
          ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
        `, [
          totalClients,
          onlineClients,
          offlineClients,
          totalBandwidthUsage,
          uploadBandwidth,
          downloadBandwidth,
          99.5,
          onlineClients,
          0,
          currentTime
        ]);
        
        fallbackClient.release();
        console.log(`[Scheduler] Network data collected (fallback) - Clients: ${totalClients}, Online: ${onlineClients}`);
      } catch (fallbackError) {
        console.error('[Scheduler] Fallback collection error:', fallbackError.message);
      }
    }
    
  } catch (error) {
    console.error('[Scheduler] Network collection error:', error.message);
  }
}

// Function to clean old data (keep only last 30 days)
async function cleanOldNetworkData() {
  console.log('[Scheduler] Cleaning old network data...');
  
  try {
    const client = await pool.connect();
    
    const result = await client.query(`
      DELETE FROM network_summary 
      WHERE data_collected_at < NOW() - INTERVAL '30 days'
    `);
    
    client.release();
    console.log(`[Scheduler] Cleaned ${result.rowCount} old network data records`);
  } catch (error) {
    console.error('[Scheduler] Error cleaning old data:', error.message);
  }
}

// Store scheduler tasks for management
let collectionTask = null;
let cleanupTask = null;

// Initialize scheduled tasks
async function initializeScheduler() {
  console.log('[Scheduler] Initializing scheduled tasks...');
  
  // Get current interval setting
  const interval = await getSchedulerInterval();
  const cronPattern = intervalToCron(interval);
  
  // Schedule network data collection with dynamic interval
  collectionTask = cron.schedule(cronPattern, () => {
    collectNetworkData().catch(err => 
      console.error('[Scheduler] Error in scheduled collection:', err)
    );
  });
  
  // Schedule cleanup daily at 2 AM
  cleanupTask = cron.schedule('0 2 * * *', () => {
    cleanOldNetworkData().catch(err => 
      console.error('[Scheduler] Error in scheduled cleanup:', err)
    );
  });
  
  console.log('[Scheduler] Scheduled tasks initialized:');
  console.log(`  - Network data collection: Every ${interval} (${cronPattern})`);
  console.log('  - Old data cleanup: Daily at 2:00 AM');
  
  // Collect initial data on startup
  console.log('[Scheduler] Triggering immediate data collection...');
  setTimeout(() => {
    collectNetworkData().catch(err => 
      console.error('[Scheduler] Error in initial collection:', err)
    );
  }, 2000); // Wait 2 seconds after server start
}

// Restart scheduler with new interval
async function restartScheduler(newInterval = null) {
  console.log('[Scheduler] Restarting scheduler...');
  
  // Stop existing tasks
  if (collectionTask) {
    collectionTask.stop();
    collectionTask = null;
  }
  
  // Get interval (use provided or fetch from database)
  const interval = newInterval || await getSchedulerInterval();
  const cronPattern = intervalToCron(interval);
  
  // Start new collection task
  collectionTask = cron.schedule(cronPattern, () => {
    collectNetworkData().catch(err => 
      console.error('[Scheduler] Error in scheduled collection:', err)
    );
  });
  
  console.log(`[Scheduler] ✅ Scheduler restarted with interval: ${interval} (${cronPattern})`);
  
  return { success: true, interval, cronPattern };
}

module.exports = {
  initializeScheduler,
  collectNetworkData,
  cleanOldNetworkData,
  restartScheduler,
  getSchedulerInterval,
  intervalToCron
};
const express = require('express');
const pool = require('../config/database');
const { authenticateToken } = require('../middleware/auth');

const router = express.Router();

// In-memory storage for previous interface stats (like monitoring.html uses localStorage)
const interfaceStatsCache = new Map();

// Helper to normalize names (copied from monitoring.html)
function normalizeName(name) {
  if (!name) return '';
  // Remove angle brackets and pppoe- prefix if present
  const match = name.match(/^<?pppoe-?([^>]+)>?$/i);
  if (match && match[1]) return match[1].toLowerCase();
  return name
    .replace(/[<>]/g, '')
    .replace(/^pppoe-/, '')
    .trim()
    .toLowerCase();
}

// Get total upload/download bandwidth (same logic as monitoring.html)
router.get('/bandwidth-totals', async (req, res) => {
  try {
    console.log('[Network Totals] Starting bandwidth calculation...');
    const client = await pool.connect();
    
    // Get MikroTik settings
    const settingsResult = await client.query('SELECT * FROM mikrotik_settings ORDER BY created_at DESC LIMIT 1');
    if (settingsResult.rows.length === 0) {
      client.release();
      return res.json({
        success: false,
        error: 'MikroTik settings not configured',
        totalUploadMbps: 0,
        totalDownloadMbps: 0
      });
    }
    
    const settings = settingsResult.rows[0];
    const RouterOSAPI = require('routeros-api').RouterOSAPI;
    const conn = new RouterOSAPI({
      host: settings.host,
      user: settings.username,
      password: settings.password,
      port: settings.port || 8728,
      timeout: 15000
    });
    
    try {
      await conn.connect();
      
      // Get online accounts and interfaces (same as monitoring.html)
      const [pppActive, interfaces] = await Promise.all([
        conn.write('/ppp/active/print'),
        conn.write('/interface/print')
      ]);
      
      await conn.close();
      
      // Filter PPPoE interfaces
      const pppoeInterfaces = (interfaces || []).filter(iface => 
        iface.type === 'pppoe-in' || (iface.name && iface.name.includes('<pppoe-'))
      );
      
      // Create set of online account names for filtering
      const onlineAccountNames = new Set((pppActive || []).map(acc => normalizeName(acc.name)));
      
      console.log(`[Network Totals] Processing ${pppoeInterfaces.length} interfaces, ${onlineAccountNames.size} online accounts`);
      
      // Calculate bandwidth using same logic as monitoring.html
      const now = Date.now();
      let totalUploadBits = 0;
      let totalDownloadBits = 0;
      
      pppoeInterfaces.forEach(iface => {
        const username = normalizeName(iface.name);
        const rxBytes = parseFloat(iface['rx-byte'] || 0);
        const txBytes = parseFloat(iface['tx-byte'] || 0);
        
        const storageKey = `pppoe_stats_${username}`;
        const prevStats = interfaceStatsCache.get(storageKey);
        
        let uploadMbps = 0;
        let downloadMbps = 0;
        
        if (prevStats) {
          const dt = (now - prevStats.t) / 1000; // Time difference in seconds
          
          if (dt > 0 && dt < 60) { // Only calculate if time difference is reasonable
            const rxDiff = rxBytes - prevStats.rx;
            const txDiff = txBytes - prevStats.tx;
            
            if (rxDiff >= 0 && txDiff >= 0) {
              // Upload = RX (data from client to router), Download = TX (data from router to client)
              uploadMbps = (rxDiff * 8) / dt / 1e6;
              downloadMbps = (txDiff * 8) / dt / 1e6;
              
              // Only add to totals if this interface corresponds to an online account
              if (onlineAccountNames.has(username)) {
                totalUploadBits += rxDiff > 0 ? (rxDiff * 8) / dt : 0;
                totalDownloadBits += txDiff > 0 ? (txDiff * 8) / dt : 0;
              }
            }
          }
        }
        
        // Store current stats for next calculation (like monitoring.html localStorage)
        interfaceStatsCache.set(storageKey, { rx: rxBytes, tx: txBytes, t: now });
      });
      
      // Convert to Mbps
      const totalUploadMbps = (totalUploadBits / 1e6).toFixed(2);
      const totalDownloadMbps = (totalDownloadBits / 1e6).toFixed(2);
      
      console.log(`[Network Totals] Calculated totals - Upload: ${totalUploadMbps} Mbps, Download: ${totalDownloadMbps} Mbps`);
      
      client.release();
      
      res.json({
        success: true,
        totalUploadMbps: parseFloat(totalUploadMbps),
        totalDownloadMbps: parseFloat(totalDownloadMbps),
        onlineAccounts: onlineAccountNames.size,
        totalInterfaces: pppoeInterfaces.length,
        timestamp: new Date().toISOString()
      });
      
    } catch (mikrotikError) {
      client.release();
      console.error('[Network Totals] MikroTik connection error:', mikrotikError.message);
      res.json({
        success: false,
        error: 'MikroTik connection failed',
        totalUploadMbps: 0,
        totalDownloadMbps: 0
      });
    }
    
  } catch (error) {
    console.error('[Network Totals] Error:', error);
    res.status(500).json({
      success: false,
      error: 'Internal server error',
      totalUploadMbps: 0,
      totalDownloadMbps: 0
    });
  }
});

module.exports = router;
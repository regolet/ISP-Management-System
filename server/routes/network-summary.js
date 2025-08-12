const express = require('express');
const pool = require('../config/database');
const { authenticateToken } = require('../middleware/auth');

const router = express.Router();

// Helper function to convert interval string to milliseconds
function getIntervalMs(timeRange) {
  const intervalMap = {
    '1 minute': 60 * 1000,
    '5 minutes': 5 * 60 * 1000,
    '30 minutes': 30 * 60 * 1000,
    '1 hour': 60 * 60 * 1000,
    '2 hours': 2 * 60 * 60 * 1000,
    '3 hours': 3 * 60 * 60 * 1000,
    '6 hours': 6 * 60 * 60 * 1000,
    '24 hours': 24 * 60 * 60 * 1000,
    '7 days': 7 * 24 * 60 * 60 * 1000,
    '30 days': 30 * 24 * 60 * 60 * 1000
  };
  return intervalMap[timeRange] || 24 * 60 * 60 * 1000; // Default to 24 hours
}

// Get network summary statistics
router.get('/stats', authenticateToken, async (req, res) => {
  try {
    const { date } = req.query; // Optional date parameter for specific day stats
    console.log('[Network Stats] Starting stats query for date:', date);
    const client = await pool.connect();
    
    // Get latest network summary data
    console.log('[Network Stats] Getting latest data...');
    const latestResult = await client.query(`
      SELECT * FROM network_summary 
      ORDER BY created_at DESC 
      LIMIT 1
    `);
    console.log('[Network Stats] Latest result:', latestResult.rows?.length || 0, 'rows');
    
    // Get hourly data for the last 24 hours (SQLite compatible)
    console.log('[Network Stats] Getting hourly data...');
    const hourlyResult = await client.query(`
      SELECT 
        strftime('%Y-%m-%d %H:00:00', created_at) as hour,
        AVG(online_clients) as avg_online_clients,
        AVG(total_bandwidth_usage) as avg_bandwidth_usage,
        AVG(network_uptime_percentage) as avg_uptime
      FROM network_summary 
      WHERE created_at >= datetime('now', '-24 hours')
      GROUP BY strftime('%Y-%m-%d %H:00:00', created_at)
      ORDER BY hour DESC
    `);
    console.log('[Network Stats] Hourly result:', hourlyResult.rows?.length || 0, 'rows');
    
    // Get daily data for the last 7 days (or specific date if provided)
    console.log('[Network Stats] Getting daily data...');
    let dailyQuery, dailyParams;
    if (date) {
      // Get peak bandwidth for specific date (SQLite compatible)
      console.log('[Network Stats] Using date filter:', date);
      dailyQuery = `
        SELECT 
          date(created_at, '+8 hours') as day,
          AVG(online_clients) as avg_online_clients,
          AVG(total_bandwidth_usage) as avg_bandwidth_usage,
          AVG(network_uptime_percentage) as avg_uptime,
          MAX(COALESCE(upload_bandwidth, 0) + COALESCE(download_bandwidth, 0)) as peak_bandwidth
        FROM network_summary 
        WHERE date(created_at, '+8 hours') = ?
        GROUP BY date(created_at, '+8 hours')
        ORDER BY day DESC
      `;
      dailyParams = [date];
    } else {
      // Get last 7 days (SQLite compatible)
      console.log('[Network Stats] Using 7 day range');
      dailyQuery = `
        SELECT 
          date(created_at) as day,
          AVG(online_clients) as avg_online_clients,
          AVG(total_bandwidth_usage) as avg_bandwidth_usage,
          AVG(network_uptime_percentage) as avg_uptime,
          MAX(COALESCE(upload_bandwidth, 0) + COALESCE(download_bandwidth, 0)) as peak_bandwidth
        FROM network_summary 
        WHERE created_at >= datetime('now', '-7 days')
        GROUP BY date(created_at)
        ORDER BY day DESC
      `;
      dailyParams = [];
    }
    
    const dailyResult = await client.query(dailyQuery, dailyParams);
    console.log('[Network Stats] Daily result:', dailyResult.rows?.length || 0, 'rows');
    
    client.release();
    
    res.json({
      success: true,
      latest: latestResult.rows[0] || null,
      hourly: hourlyResult.rows,
      daily: dailyResult.rows
    });
  } catch (error) {
    console.error('Error fetching network summary stats:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Add new network summary data
router.post('/', authenticateToken, async (req, res) => {
  try {
    const {
      total_clients,
      online_clients,
      offline_clients,
      total_bandwidth_usage,
      network_uptime_percentage,
      active_connections,
      failed_connections,
      upload_bandwidth,
      download_bandwidth
    } = req.body;
    
    const client = await pool.connect();
    
    const result = await client.query(`
      INSERT INTO network_summary (
        total_clients, online_clients, offline_clients,
        total_bandwidth_usage, network_uptime_percentage, 
        active_connections, failed_connections, upload_bandwidth, download_bandwidth
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    `, [
      total_clients || 0,
      online_clients || 0,
      offline_clients || 0,
      total_bandwidth_usage || 0,
      network_uptime_percentage || 100.00,
      active_connections || 0,
      failed_connections || 0,
      upload_bandwidth || 0,
      download_bandwidth || 0
    ]);
    
    client.release();
    
    res.json({ success: true, message: 'Network summary data added successfully' });
  } catch (error) {
    console.error('Error adding network summary data:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Get bandwidth usage over time
router.get('/bandwidth-history', authenticateToken, async (req, res) => {
  try {
    const { period = '24h', date } = req.query; // 24h, 7d, 30d, date (YYYY-MM-DD)
    
    let timeRange;
    switch (period) {
      case '1m':
        timeRange = '1 minute';
        break;
      case '5m':
        timeRange = '5 minutes';
        break;
      case '30m':
        timeRange = '30 minutes';
        break;
      case '1h':
        timeRange = '1 hour';
        break;
      case '2h':
        timeRange = '2 hours';
        break;
      case '3h':
        timeRange = '3 hours';
        break;
      case '6h':
        timeRange = '6 hours';
        break;
      case '7d':
        timeRange = '7 days';
        break;
      case '30d':
        timeRange = '30 days';
        break;
      default: // 24h
        timeRange = '24 hours';
    }
    
    const client = await pool.connect();
    
    // Get upload and download bandwidth data using created_at (UTC time)
    let query, params;
    
    if (date) {
      // Filter by specific date from 12:00 AM to 12:00 PM (noon) UTC
      const startDate = new Date(`${date}T00:00:00.000Z`);
      const endDate = new Date(`${date}T12:00:00.000Z`);
      
      
      query = `
        SELECT 
          created_at as time_period,
          upload_bandwidth,
          download_bandwidth,
          online_clients as avg_online_clients,
          total_clients
        FROM network_summary 
        WHERE created_at >= ? AND created_at < ?
        ORDER BY created_at ASC
      `;
      params = [startDate.toISOString(), endDate.toISOString()];
    } else {
      // Use time range as before
      const cutoffTime = new Date(Date.now() - getIntervalMs(timeRange)).toISOString();
      query = `
        SELECT 
          created_at as time_period,
          upload_bandwidth,
          download_bandwidth,
          online_clients as avg_online_clients,
          total_clients
        FROM network_summary 
        WHERE created_at >= ?
        ORDER BY created_at ASC
      `;
      params = [cutoffTime];
    }
    
    const result = await client.query(query, params);
    
    
    client.release();
    
    res.json({ success: true, data: result.rows });
  } catch (error) {
    console.error('Error fetching bandwidth history:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Get network uptime statistics
router.get('/uptime-stats', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    
    const result = await client.query(`
      SELECT 
        AVG(network_uptime_percentage) as avg_uptime_24h,
        MIN(network_uptime_percentage) as min_uptime_24h,
        COUNT(*) as total_measurements
      FROM network_summary 
      WHERE created_at >= datetime('now', '-24 hours')
    `);
    
    const weeklyResult = await client.query(`
      SELECT 
        AVG(network_uptime_percentage) as avg_uptime_7d
      FROM network_summary 
      WHERE created_at >= datetime('now', '-7 days')
    `);
    
    client.release();
    
    res.json({
      success: true,
      uptime_24h: result.rows[0]?.avg_uptime_24h || 100,
      min_uptime_24h: result.rows[0]?.min_uptime_24h || 100,
      uptime_7d: weeklyResult.rows[0]?.avg_uptime_7d || 100,
      measurements: result.rows[0]?.total_measurements || 0
    });
  } catch (error) {
    console.error('Error fetching uptime stats:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

module.exports = router;
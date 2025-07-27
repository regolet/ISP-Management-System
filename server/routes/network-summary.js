const express = require('express');
const pool = require('../config/database');
const { authenticateToken } = require('../middleware/auth');

const router = express.Router();

// Get network summary statistics
router.get('/stats', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    
    // Get latest network summary data
    const latestResult = await client.query(`
      SELECT * FROM network_summary 
      ORDER BY data_collected_at DESC 
      LIMIT 1
    `);
    
    // Get hourly data for the last 24 hours
    const hourlyResult = await client.query(`
      SELECT 
        DATE_TRUNC('hour', data_collected_at) as hour,
        AVG(online_clients) as avg_online_clients,
        AVG(total_bandwidth_usage) as avg_bandwidth_usage,
        AVG(network_uptime_percentage) as avg_uptime
      FROM network_summary 
      WHERE data_collected_at >= NOW() - INTERVAL '24 hours'
      GROUP BY DATE_TRUNC('hour', data_collected_at)
      ORDER BY hour DESC
    `);
    
    // Get daily data for the last 7 days
    const dailyResult = await client.query(`
      SELECT 
        DATE_TRUNC('day', data_collected_at) as day,
        AVG(online_clients) as avg_online_clients,
        AVG(total_bandwidth_usage) as avg_bandwidth_usage,
        AVG(network_uptime_percentage) as avg_uptime,
        MAX(upload_bandwidth + download_bandwidth) as peak_bandwidth
      FROM network_summary 
      WHERE data_collected_at >= NOW() - INTERVAL '7 days'
      GROUP BY DATE_TRUNC('day', data_collected_at)
      ORDER BY day DESC
    `);
    
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
      average_bandwidth_per_client,
      peak_bandwidth_usage,
      network_uptime_percentage,
      active_connections,
      failed_connections,
      data_collected_at
    } = req.body;
    
    const client = await pool.connect();
    
    const result = await client.query(`
      INSERT INTO network_summary (
        total_clients, online_clients, offline_clients,
        total_bandwidth_usage, average_bandwidth_per_client, peak_bandwidth_usage,
        network_uptime_percentage, active_connections, failed_connections,
        data_collected_at
      ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
      RETURNING *
    `, [
      total_clients || 0,
      online_clients || 0,
      offline_clients || 0,
      total_bandwidth_usage || 0,
      average_bandwidth_per_client || 0,
      peak_bandwidth_usage || 0,
      network_uptime_percentage || 100.00,
      active_connections || 0,
      failed_connections || 0,
      data_collected_at || new Date()
    ]);
    
    client.release();
    
    res.json({ success: true, data: result.rows[0] });
  } catch (error) {
    console.error('Error adding network summary data:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Get bandwidth usage over time
router.get('/bandwidth-history', authenticateToken, async (req, res) => {
  try {
    const { period = '24h' } = req.query; // 24h, 7d, 30d
    
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
    
    // Get upload and download bandwidth data
    const result = await client.query(`
      SELECT 
        data_collected_at as time_period,
        upload_bandwidth,
        download_bandwidth,
        online_clients as avg_online_clients,
        total_clients
      FROM network_summary 
      WHERE data_collected_at >= NOW() - INTERVAL '${timeRange}'
      ORDER BY data_collected_at ASC
    `);
    
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
      WHERE data_collected_at >= NOW() - INTERVAL '24 hours'
    `);
    
    const weeklyResult = await client.query(`
      SELECT 
        AVG(network_uptime_percentage) as avg_uptime_7d
      FROM network_summary 
      WHERE data_collected_at >= NOW() - INTERVAL '7 days'
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
const express = require('express');
const pool = require('../config/database');
const { authenticateToken } = require('../middleware/auth');
const { restartScheduler, getSchedulerInterval } = require('../utils/scheduler');

const router = express.Router();

// Get current scheduler settings
router.get('/settings', authenticateToken, async (req, res) => {
  try {
    const interval = await getSchedulerInterval();
    
    res.json({
      success: true,
      interval: interval,
      message: `Current collection interval: ${interval}`
    });
  } catch (error) {
    console.error('Error getting scheduler settings:', error);
    res.status(500).json({ success: false, error: 'Failed to get scheduler settings' });
  }
});

// Update scheduler interval
router.post('/interval', authenticateToken, async (req, res) => {
  try {
    const { interval } = req.body;
    
    if (!interval) {
      return res.status(400).json({ success: false, error: 'Interval is required' });
    }
    
    // Validate interval
    const validIntervals = ['10s', '30s', '1m', '5m', '15m', '30m'];
    if (!validIntervals.includes(interval)) {
      return res.status(400).json({ 
        success: false, 
        error: 'Invalid interval. Valid options: ' + validIntervals.join(', ')
      });
    }
    
    const client = await pool.connect();
    
    // Insert new setting (we keep history)
    await client.query(`
      INSERT INTO scheduler_settings (setting_key, setting_value)
      VALUES ('collection_interval', $1)
    `, [interval]);
    
    client.release();
    
    // Restart scheduler with new interval
    const result = await restartScheduler(interval);
    
    res.json({
      success: true,
      interval: interval,
      cronPattern: result.cronPattern,
      message: `Scheduler interval updated to ${interval} and restarted successfully`
    });
    
  } catch (error) {
    console.error('Error updating scheduler interval:', error);
    res.status(500).json({ success: false, error: 'Failed to update scheduler interval' });
  }
});

// Restart scheduler with current settings
router.post('/restart', authenticateToken, async (req, res) => {
  try {
    const result = await restartScheduler();
    
    res.json({
      success: true,
      interval: result.interval,
      cronPattern: result.cronPattern,
      message: `Scheduler restarted with interval: ${result.interval}`
    });
    
  } catch (error) {
    console.error('Error restarting scheduler:', error);
    res.status(500).json({ success: false, error: 'Failed to restart scheduler' });
  }
});

module.exports = router;
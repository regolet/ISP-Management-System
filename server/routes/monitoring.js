const express = require('express');
const pool = require('../config/database');
const { authenticateToken } = require('../middleware/auth');
const RouterOSAPI = require('routeros-api').RouterOSAPI;

const router = express.Router();

// Get monitoring dashboard data
router.get('/dashboard', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    
    // Get active MikroTik settings
    const settingsResult = await client.query('SELECT * FROM mikrotik_settings ORDER BY created_at DESC LIMIT 1');
    if (settingsResult.rows.length === 0) {
      client.release();
      return res.status(400).json({ error: 'No MikroTik settings found. Please configure MikroTik settings first.' });
    }
    
    const settings = settingsResult.rows[0];
    
    // Connect to MikroTik and get data
    const connection = new RouterOSAPI({
      host: settings.host,
      user: settings.username,
      password: settings.password,
      port: settings.port || 8728
    });

    try {
      await connection.connect();
      
      // Get PPP accounts, active connections, and interfaces with statistics
      const [pppAccounts, pppActive, pppoeInterfaces] = await Promise.all([
        connection.write('/ppp/secret/print'),
        connection.write('/ppp/active/print'),
        connection.write('/interface/print', { '?type': 'pppoe-in', 'stats': '' })
      ]);
      
      connection.close();
      
      // Create a map of account profiles for quick lookup
      const accountProfileMap = {};
      if (Array.isArray(pppAccounts)) {
        pppAccounts.forEach(acc => {
          accountProfileMap[acc.name] = acc.profile;
        });
      }
      
      // Merge profile information into active accounts
      const pppActiveWithProfiles = Array.isArray(pppActive) ? pppActive.map(activeAcc => ({
        ...activeAcc,
        profile: accountProfileMap[activeAcc.name] || '-'
      })) : [];
      
      // Calculate statistics
      const totalAccounts = Array.isArray(pppAccounts) ? pppAccounts.length : 0;
      const onlineAccounts = Array.isArray(pppActive) ? pppActive.length : 0;
      const enabledAccounts = Array.isArray(pppAccounts) ? pppAccounts.filter(acc => acc.disabled !== 'true').length : 0;
      const disabledAccounts = Array.isArray(pppAccounts) ? pppAccounts.filter(acc => acc.disabled === 'true').length : 0;
      
      const stats = {
        total_accounts: totalAccounts,
        online_accounts: onlineAccounts,
        offline_accounts: totalAccounts - onlineAccounts,
        enabled_accounts: enabledAccounts,
        disabled_accounts: disabledAccounts
      };
      
      client.release();
      res.json({
        success: true,
        aggregate_stats: stats,
        ppp_accounts: Array.isArray(pppAccounts) ? pppAccounts : [],
        ppp_active: pppActiveWithProfiles,
        pppoe_interfaces: Array.isArray(pppoeInterfaces) ? pppoeInterfaces : []
      });
      
    } catch (mikrotikError) {
      client.release();
      res.status(500).json({ error: 'Failed to connect to MikroTik', details: mikrotikError.message });
    }
    
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Get PPP accounts summary (using existing MikroTik settings)
router.get('/ppp_accounts_summary', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    
    // Get active MikroTik settings
    const settingsResult = await client.query('SELECT * FROM mikrotik_settings ORDER BY created_at DESC LIMIT 1');
    if (settingsResult.rows.length === 0) {
      client.release();
      return res.status(400).json({ error: 'No MikroTik settings found. Please configure MikroTik settings first.' });
    }
    
    const settings = settingsResult.rows[0];
    const connection = new RouterOSAPI({
      host: settings.host,
      user: settings.username,
      password: settings.password,
      port: settings.port || 8728
    });

    try {
      await connection.connect();
      const [pppAccounts, pppActive] = await Promise.all([
        connection.write('/ppp/secret/print'),
        connection.write('/ppp/active/print')
      ]);
      connection.close();

      // Create a map for quick lookup of active accounts
      const activeAccountsMap = {};
      if (Array.isArray(pppActive)) {
        pppActive.forEach(active => {
          activeAccountsMap[active.name] = {
            uptime: active.uptime,
            'caller-id': active['caller-id'],
            address: active.address
          };
        });
      }

      // Combine the data
      const combinedData = Array.isArray(pppAccounts) ? pppAccounts.map(account => ({
        name: account.name,
        profile: account.profile,
        disabled: account.disabled === 'true',
        comment: account.comment || '',
        isOnline: !!activeAccountsMap[account.name],
        uptime: activeAccountsMap[account.name]?.uptime || null,
        callerId: activeAccountsMap[account.name]?.['caller-id'] || null,
        address: activeAccountsMap[account.name]?.address || null
      })) : [];

      client.release();
      res.json({ 
        success: true, 
        accounts: combinedData,
        total: combinedData.length,
        online: combinedData.filter(acc => acc.isOnline).length,
        offline: combinedData.filter(acc => !acc.isOnline).length
      });
      
    } catch (mikrotikError) {
      client.release();
      res.status(500).json({ error: 'Failed to connect to MikroTik', details: mikrotikError.message });
    }
    
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// ========== GROUPS ==========

// Get all monitoring groups
router.get('/groups', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT * FROM monitoring_groups ORDER BY created_at DESC');
    
    // Parse JSONB group_data for each group
    const groups = result.rows.map(row => ({
      id: row.id,
      group_name: row.group_name,
      group_data: typeof row.group_data === 'string' ? JSON.parse(row.group_data) : row.group_data,
      created_at: row.created_at,
      updated_at: row.updated_at
    }));
    
    client.release();
    res.json({ success: true, groups });
  } catch (error) {
    console.error('Groups fetch error:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Create new monitoring group
router.post('/groups', authenticateToken, async (req, res) => {
  try {
    const { group_name, group_data } = req.body;
    
    if (!group_name || !group_data) {
      return res.status(400).json({ success: false, error: 'Group name and data are required' });
    }

    const client = await pool.connect();
    
    // Ensure group_data is properly formatted as JSON
    const groupDataJson = typeof group_data === 'string' ? group_data : JSON.stringify(group_data);
    
    const result = await client.query(
      'INSERT INTO monitoring_groups (group_name, group_data) VALUES ($1, $2) RETURNING *',
      [group_name, groupDataJson]
    );
    
    const newGroup = {
      id: result.rows[0].id,
      group_name: result.rows[0].group_name,
      group_data: typeof result.rows[0].group_data === 'string' ? JSON.parse(result.rows[0].group_data) : result.rows[0].group_data,
      created_at: result.rows[0].created_at,
      updated_at: result.rows[0].updated_at
    };
    
    client.release();
    res.json({ success: true, group: newGroup });
  } catch (error) {
    console.error('Group creation error:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Update monitoring group
router.put('/groups', authenticateToken, async (req, res) => {
  try {
    const { id, group_name, group_data } = req.body;
    
    if (!id || !group_name || !group_data) {
      return res.status(400).json({ success: false, error: 'ID, group name and data are required' });
    }

    const client = await pool.connect();
    
    // Ensure group_data is properly formatted as JSON
    const groupDataJson = typeof group_data === 'string' ? group_data : JSON.stringify(group_data);
    
    const result = await client.query(
      'UPDATE monitoring_groups SET group_name = $1, group_data = $2, updated_at = CURRENT_TIMESTAMP WHERE id = $3 RETURNING *',
      [group_name, groupDataJson, id]
    );
    
    if (result.rows.length === 0) {
      client.release();
      return res.status(404).json({ success: false, error: 'Group not found' });
    }
    
    client.release();
    res.json({ success: true, group: result.rows[0] });
  } catch (error) {
    console.error('Group update error:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Delete monitoring group
router.delete('/groups', authenticateToken, async (req, res) => {
  try {
    const { id } = req.body;
    
    if (!id) {
      return res.status(400).json({ success: false, error: 'Group ID is required' });
    }

    const client = await pool.connect();
    const result = await client.query('DELETE FROM monitoring_groups WHERE id = $1 RETURNING *', [id]);
    
    if (result.rows.length === 0) {
      client.release();
      return res.status(404).json({ success: false, error: 'Group not found' });
    }
    
    client.release();
    res.json({ success: true, deleted: result.rows[0] });
  } catch (error) {
    console.error('Group deletion error:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// ========== CATEGORIES ==========

// Get all monitoring categories
router.get('/categories', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT * FROM monitoring_categories ORDER BY category_index, subcategory_index');
    
    // Parse JSONB group_ids for each category
    const categories = result.rows.map(row => ({
      id: row.id,
      category_name: row.category_name,
      subcategory_name: row.subcategory_name,
      group_ids: typeof row.group_ids === 'string' ? JSON.parse(row.group_ids) : row.group_ids,
      category_index: row.category_index,
      subcategory_index: row.subcategory_index,
      created_at: row.created_at,
      updated_at: row.updated_at
    }));
    
    client.release();
    res.json({ success: true, categories });
  } catch (error) {
    console.error('Categories fetch error:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Create new monitoring category
router.post('/categories', authenticateToken, async (req, res) => {
  try {
    const { category_name, subcategory_name, group_ids, category_index, subcategory_index } = req.body;
    
    if (!category_name) {
      return res.status(400).json({ success: false, error: 'Category name is required' });
    }

    const client = await pool.connect();
    
    // Ensure group_ids is properly formatted as JSON
    const groupIdsJson = group_ids ? (typeof group_ids === 'string' ? group_ids : JSON.stringify(group_ids)) : '[]';
    
    const result = await client.query(
      'INSERT INTO monitoring_categories (category_name, subcategory_name, group_ids, category_index, subcategory_index) VALUES ($1, $2, $3, $4, $5) RETURNING *',
      [category_name, subcategory_name || null, groupIdsJson, category_index || 0, subcategory_index || 0]
    );
    
    client.release();
    res.json({ success: true, category: result.rows[0] });
  } catch (error) {
    console.error('Category creation error:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Update monitoring category
router.put('/categories', authenticateToken, async (req, res) => {
  try {
    const { id, category_name, subcategory_name, group_ids, category_index, subcategory_index } = req.body;
    
    if (!id || !category_name) {
      return res.status(400).json({ success: false, error: 'ID and category name are required' });
    }

    const client = await pool.connect();
    
    // Ensure group_ids is properly formatted as JSON
    const groupIdsJson = group_ids ? (typeof group_ids === 'string' ? group_ids : JSON.stringify(group_ids)) : '[]';
    
    const result = await client.query(
      'UPDATE monitoring_categories SET category_name = $1, subcategory_name = $2, group_ids = $3, category_index = $4, subcategory_index = $5, updated_at = CURRENT_TIMESTAMP WHERE id = $6 RETURNING *',
      [category_name, subcategory_name || null, groupIdsJson, category_index, subcategory_index, id]
    );
    
    if (result.rows.length === 0) {
      client.release();
      return res.status(404).json({ success: false, error: 'Category not found' });
    }
    
    client.release();
    res.json({ success: true, category: result.rows[0] });
  } catch (error) {
    console.error('Category update error:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Delete monitoring category
router.delete('/categories', authenticateToken, async (req, res) => {
  try {
    const { id } = req.body;
    
    if (!id) {
      return res.status(400).json({ success: false, error: 'Category ID is required' });
    }

    const client = await pool.connect();
    const result = await client.query('DELETE FROM monitoring_categories WHERE id = $1 RETURNING *', [id]);
    
    if (result.rows.length === 0) {
      client.release();
      return res.status(404).json({ success: false, error: 'Category not found' });
    }
    
    client.release();
    res.json({ success: true, deleted: result.rows[0] });
  } catch (error) {
    console.error('Category deletion error:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Create subcategory
router.post('/categories/:categoryId/subcategories', authenticateToken, async (req, res) => {
  try {
    const { categoryId } = req.params;
    const { subcategory_name, group_ids } = req.body;
    
    if (!subcategory_name) {
      return res.status(400).json({ success: false, error: 'Subcategory name is required' });
    }

    const client = await pool.connect();
    
    // Get the parent category to determine indices
    const parentResult = await client.query('SELECT * FROM monitoring_categories WHERE id = $1', [categoryId]);
    if (parentResult.rows.length === 0) {
      client.release();
      return res.status(404).json({ success: false, error: 'Parent category not found' });
    }
    
    const parentCategory = parentResult.rows[0];
    
    // Get the next subcategory index for this category
    const maxSubcategoryResult = await client.query(
      'SELECT COALESCE(MAX(subcategory_index), 0) + 1 as next_index FROM monitoring_categories WHERE category_index = $1',
      [parentCategory.category_index]
    );
    const nextSubcategoryIndex = maxSubcategoryResult.rows[0].next_index;
    
    // Ensure group_ids is properly formatted as JSON
    const groupIdsJson = group_ids ? (typeof group_ids === 'string' ? group_ids : JSON.stringify(group_ids)) : '[]';
    
    const result = await client.query(
      'INSERT INTO monitoring_categories (category_name, subcategory_name, group_ids, category_index, subcategory_index) VALUES ($1, $2, $3, $4, $5) RETURNING *',
      [parentCategory.category_name, subcategory_name, groupIdsJson, parentCategory.category_index, nextSubcategoryIndex]
    );
    
    client.release();
    res.json({ success: true, subcategory: result.rows[0] });
  } catch (error) {
    console.error('Subcategory creation error:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Update subcategory
router.put('/categories/:categoryId/subcategories/:subcategoryId', authenticateToken, async (req, res) => {
  try {
    const { subcategoryId } = req.params;
    const { subcategory_name, group_ids } = req.body;
    
    if (!subcategory_name) {
      return res.status(400).json({ success: false, error: 'Subcategory name is required' });
    }

    const client = await pool.connect();
    
    // Ensure group_ids is properly formatted as JSON
    const groupIdsJson = group_ids ? (typeof group_ids === 'string' ? group_ids : JSON.stringify(group_ids)) : '[]';
    
    // For subcategories, update subcategory_name and group_ids, not category_name (subcategories share parent's category_name)
    const result = await client.query(
      'UPDATE monitoring_categories SET subcategory_name = $1, group_ids = $2, updated_at = CURRENT_TIMESTAMP WHERE id = $3 RETURNING *',
      [subcategory_name, groupIdsJson, subcategoryId]
    );
    
    if (result.rows.length === 0) {
      client.release();
      return res.status(404).json({ success: false, error: 'Subcategory not found' });
    }
    
    client.release();
    res.json({ success: true, subcategory: result.rows[0] });
  } catch (error) {
    console.error('Subcategory update error:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Delete subcategory
router.delete('/categories/:categoryId/subcategories/:subcategoryId', authenticateToken, async (req, res) => {
  try {
    const { subcategoryId } = req.params;

    const client = await pool.connect();
    const result = await client.query('DELETE FROM monitoring_categories WHERE id = $1 RETURNING *', [subcategoryId]);
    
    if (result.rows.length === 0) {
      client.release();
      return res.status(404).json({ success: false, error: 'Subcategory not found' });
    }
    
    client.release();
    res.json({ success: true, deleted: result.rows[0] });
  } catch (error) {
    console.error('Subcategory deletion error:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Update subcategory groups
router.put('/categories/:categoryId/subcategories/:subcategoryId/groups', authenticateToken, async (req, res) => {
  try {
    const { subcategoryId } = req.params;
    const { group_ids } = req.body;

    const client = await pool.connect();
    
    // Ensure group_ids is properly formatted as JSON
    const groupIdsJson = group_ids ? (typeof group_ids === 'string' ? group_ids : JSON.stringify(group_ids)) : '[]';
    
    const result = await client.query(
      'UPDATE monitoring_categories SET group_ids = $1, updated_at = CURRENT_TIMESTAMP WHERE id = $2 RETURNING *',
      [groupIdsJson, subcategoryId]
    );
    
    if (result.rows.length === 0) {
      client.release();
      return res.status(404).json({ success: false, error: 'Subcategory not found' });
    }
    
    client.release();
    res.json({ success: true, subcategory: result.rows[0] });
  } catch (error) {
    console.error('Subcategory groups update error:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

module.exports = router;
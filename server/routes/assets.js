const express = require('express');
const router = express.Router();
const pool = require('../config/database');
const { authenticateToken } = require('../middleware/auth');

// Get all assets
router.get('/', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT a.*, 
        COALESCE(SUM(ac.amount), 0) as total_collections,
        COUNT(ac.id) as collection_count,
        MAX(ac.collection_date) as last_collection_date,
        COUNT(DISTINCT asu.id) as subitems_count
      FROM assets a
      LEFT JOIN asset_collections ac ON a.id = ac.asset_id
      LEFT JOIN asset_subitems asu ON a.id = asu.asset_id
      GROUP BY a.id
      ORDER BY a.created_at DESC
    `);
    client.release();
    res.json({ success: true, assets: result.rows });
  } catch (error) {
    console.error('Error fetching assets:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Get stats route (before /:id route)
router.get('/stats', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    
    // Get basic asset stats
    const assetStatsResult = await client.query(`
      SELECT 
        COUNT(*) as total_assets,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_assets,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_assets,
        COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance_assets
      FROM assets
    `);
    
    // Get collection stats - SQLite compatible with error handling
    let collectionStats = {
      total_collections: 0,
      this_month_collections: 0,
      total_collection_records: 0
    };
    
    try {
      const collectionStatsResult = await client.query(`
        SELECT 
          COALESCE(SUM(amount), 0) as total_collections,
          COALESCE(SUM(CASE 
            WHEN CAST(strftime('%m', collection_date) AS INTEGER) = CAST(strftime('%m', 'now') AS INTEGER)
            AND CAST(strftime('%Y', collection_date) AS INTEGER) = CAST(strftime('%Y', 'now') AS INTEGER)
            THEN amount 
            ELSE 0 
          END), 0) as this_month_collections,
          COUNT(CASE WHEN amount IS NOT NULL THEN 1 END) as total_collection_records
        FROM asset_collections
      `);
      collectionStats = collectionStatsResult.rows[0];
    } catch (collectionError) {
      // asset_collections table might not exist, use defaults
      console.log('Asset collections table not available, using default values');
    }
    
    client.release();
    
    const stats = {
      ...assetStatsResult.rows[0],
      ...collectionStats
    };
    
    res.json({ success: true, stats });
  } catch (error) {
    console.error('Error fetching asset stats:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Get available inventory items for adding to assets
router.get('/inventory-items', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    
    // Check if inventory_items table exists - SQLite compatible
    try {
      await client.query(`SELECT 1 FROM inventory_items LIMIT 1`);
    } catch (error) {
      // Table doesn't exist
      client.release();
      return res.json({ success: true, items: [] });
    }
    
    const result = await client.query(`
      SELECT 
        ii.*,
        COALESCE(ic.name, 'Uncategorized') as category_name
      FROM inventory_items ii
      LEFT JOIN inventory_categories ic ON ii.category_id = ic.id
      WHERE ii.status = 'active'
      ORDER BY ii.name
    `);
    
    client.release();
    res.json({ success: true, items: result.rows });
  } catch (error) {
    console.error('Error fetching inventory items:', error);
    res.status(500).json({ error: 'Internal server error', details: error.message });
  }
});

// Get subitems for an asset
router.get('/:id/subitems', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    
    // First check if asset exists
    const assetCheck = await client.query('SELECT id FROM assets WHERE id = $1', [req.params.id]);
    if (assetCheck.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Asset not found' });
    }
    
    // Check if inventory_items table exists
    const tableCheck = await client.query(`
      SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_name = 'inventory_items'
      );
    `);
    
    if (!tableCheck.rows[0].exists) {
      client.release();
      return res.json({ success: true, subitems: [] });
    }
    
    const result = await client.query(`
      SELECT 
        asu.*,
        COALESCE(ii.name, 'Unknown Item') as item_name,
        COALESCE(ii.description, '') as item_description,
        COALESCE(ii.sku, '') as sku,
        COALESCE(ic.name, 'Uncategorized') as category_name
      FROM asset_subitems asu
      LEFT JOIN inventory_items ii ON asu.inventory_item_id = ii.id
      LEFT JOIN inventory_categories ic ON ii.category_id = ic.id
      WHERE asu.asset_id = $1
      ORDER BY asu.created_at DESC
    `, [req.params.id]);
    
    client.release();
    res.json({ success: true, subitems: result.rows });
  } catch (error) {
    console.error('Error fetching asset subitems:', error);
    res.status(500).json({ error: 'Internal server error', details: error.message });
  }
});

// Get collections for an asset
router.get('/:id/collections', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(
      'SELECT * FROM asset_collections WHERE asset_id = $1 ORDER BY collection_date DESC',
      [req.params.id]
    );
    client.release();
    res.json({ success: true, collections: result.rows });
  } catch (error) {
    console.error('Error fetching collections:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Get single asset with collections (should be after specific routes)
router.get('/:id', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    
    // Get asset details
    const assetResult = await client.query(
      'SELECT * FROM assets WHERE id = $1',
      [req.params.id]
    );
    
    if (assetResult.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Asset not found' });
    }
    
    // Get collections for this asset
    const collectionsResult = await client.query(
      'SELECT * FROM asset_collections WHERE asset_id = $1 ORDER BY collection_date DESC',
      [req.params.id]
    );
    
    client.release();
    
    res.json({
      ...assetResult.rows[0],
      collections: collectionsResult.rows
    });
  } catch (error) {
    console.error('Error fetching asset:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Create new asset
router.post('/', authenticateToken, async (req, res) => {
  const { name, type, description, location, deployment_date, status, serial_number, model } = req.body;
  
  if (!name || !type) {
    return res.status(400).json({ error: 'Name and type are required' });
  }
  
  try {
    const client = await pool.connect();
    const result = await client.query(
      `INSERT INTO assets (name, type, description, location, deployment_date, status, serial_number, model) 
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8) 
       RETURNING *`,
      [name, type, description, location, deployment_date, status || 'active', serial_number, model]
    );
    client.release();
    res.status(201).json(result.rows[0]);
  } catch (error) {
    console.error('Error creating asset:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update asset
router.put('/:id', authenticateToken, async (req, res) => {
  const { name, type, description, location, deployment_date, status, serial_number, model } = req.body;
  
  if (!name || !type) {
    return res.status(400).json({ error: 'Name and type are required' });
  }
  
  try {
    const client = await pool.connect();
    const result = await client.query(
      `UPDATE assets 
       SET name = $1, type = $2, description = $3, location = $4, 
           deployment_date = $5, status = $6, serial_number = $7, model = $8,
           updated_at = CURRENT_TIMESTAMP
       WHERE id = $9 
       RETURNING *`,
      [name, type, description, location, deployment_date, status, serial_number, model, req.params.id]
    );
    
    if (result.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Asset not found' });
    }
    
    client.release();
    res.json(result.rows[0]);
  } catch (error) {
    console.error('Error updating asset:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Delete asset
router.delete('/:id', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(
      'DELETE FROM assets WHERE id = $1 RETURNING id',
      [req.params.id]
    );
    
    if (result.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Asset not found' });
    }
    
    client.release();
    res.json({ message: 'Asset deleted successfully' });
  } catch (error) {
    console.error('Error deleting asset:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Add collection for an asset
router.post('/:id/collections', authenticateToken, async (req, res) => {
  const { collection_date, amount, collector_name, notes } = req.body;
  
  if (!collection_date || !amount) {
    return res.status(400).json({ error: 'Collection date and amount are required' });
  }
  
  try {
    const client = await pool.connect();
    
    // Check if asset exists
    const assetCheck = await client.query('SELECT id FROM assets WHERE id = $1', [req.params.id]);
    if (assetCheck.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Asset not found' });
    }
    
    const result = await client.query(
      `INSERT INTO asset_collections (asset_id, collection_date, amount, collector_name, notes) 
       VALUES ($1, $2, $3, $4, $5) 
       RETURNING *`,
      [req.params.id, collection_date, amount, collector_name, notes]
    );
    client.release();
    res.status(201).json(result.rows[0]);
  } catch (error) {
    console.error('Error creating collection:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update collection
router.put('/collections/:collectionId', authenticateToken, async (req, res) => {
  const { collection_date, amount, collector_name, notes } = req.body;
  
  if (!collection_date || !amount) {
    return res.status(400).json({ error: 'Collection date and amount are required' });
  }
  
  try {
    const client = await pool.connect();
    const result = await client.query(
      `UPDATE asset_collections 
       SET collection_date = $1, amount = $2, collector_name = $3, notes = $4,
           updated_at = CURRENT_TIMESTAMP
       WHERE id = $5 
       RETURNING *`,
      [collection_date, amount, collector_name, notes, req.params.collectionId]
    );
    
    if (result.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Collection not found' });
    }
    
    client.release();
    res.json(result.rows[0]);
  } catch (error) {
    console.error('Error updating collection:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Delete collection
router.delete('/collections/:collectionId', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(
      'DELETE FROM asset_collections WHERE id = $1 RETURNING id',
      [req.params.collectionId]
    );
    
    if (result.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Collection not found' });
    }
    
    client.release();
    res.json({ message: 'Collection deleted successfully' });
  } catch (error) {
    console.error('Error deleting collection:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Add subitem to an asset
router.post('/:id/subitems', authenticateToken, async (req, res) => {
  const { inventory_item_id, quantity, notes, deployment_date, status } = req.body;
  
  if (!inventory_item_id || !quantity) {
    return res.status(400).json({ error: 'Inventory item ID and quantity are required' });
  }
  
  try {
    const client = await pool.connect();
    
    // Check if asset exists
    const assetCheck = await client.query('SELECT id FROM assets WHERE id = $1', [req.params.id]);
    if (assetCheck.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Asset not found' });
    }
    
    // Check if inventory_items table exists and if inventory item exists
    const tableCheck = await client.query(`
      SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_name = 'inventory_items'
      );
    `);
    
    if (!tableCheck.rows[0].exists) {
      client.release();
      return res.status(400).json({ error: 'Inventory system not available' });
    }
    
    const itemCheck = await client.query('SELECT id FROM inventory_items WHERE id = $1', [inventory_item_id]);
    if (itemCheck.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Inventory item not found' });
    }
    
    const result = await client.query(`
      INSERT INTO asset_subitems (asset_id, inventory_item_id, quantity, notes, deployment_date, status) 
      VALUES ($1, $2, $3, $4, $5, $6) 
      RETURNING *
    `, [req.params.id, inventory_item_id, quantity, notes, deployment_date, status || 'deployed']);
    
    client.release();
    res.status(201).json({ success: true, subitem: result.rows[0] });
  } catch (error) {
    console.error('Error creating asset subitem:', error);
    if (error.code === '23505') { // Unique constraint violation
      res.status(400).json({ error: 'This inventory item is already added to this asset' });
    } else {
      res.status(500).json({ error: 'Internal server error' });
    }
  }
});

// Update asset subitem
router.put('/subitems/:subitemId', authenticateToken, async (req, res) => {
  const { quantity, notes, deployment_date, status } = req.body;
  
  if (!quantity) {
    return res.status(400).json({ error: 'Quantity is required' });
  }
  
  try {
    const client = await pool.connect();
    const result = await client.query(`
      UPDATE asset_subitems 
      SET quantity = $1, notes = $2, deployment_date = $3, status = $4, updated_at = CURRENT_TIMESTAMP
      WHERE id = $5 
      RETURNING *
    `, [quantity, notes, deployment_date, status, req.params.subitemId]);
    
    if (result.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Asset subitem not found' });
    }
    
    client.release();
    res.json({ success: true, subitem: result.rows[0] });
  } catch (error) {
    console.error('Error updating asset subitem:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Delete asset subitem
router.delete('/subitems/:subitemId', authenticateToken, async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(
      'DELETE FROM asset_subitems WHERE id = $1 RETURNING id',
      [req.params.subitemId]
    );
    
    if (result.rows.length === 0) {
      client.release();
      return res.status(404).json({ error: 'Asset subitem not found' });
    }
    
    client.release();
    res.json({ success: true, message: 'Asset subitem deleted successfully' });
  } catch (error) {
    console.error('Error deleting asset subitem:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

module.exports = router;
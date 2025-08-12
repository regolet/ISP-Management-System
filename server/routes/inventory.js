const express = require('express');
const pool = require('../config/database');

const router = express.Router();

// ========== ROOT ENDPOINT FOR DASHBOARD ==========

// Get inventory summary for dashboard
router.get('/', async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT 
        i.*,
        c.name as category_name,
        s.name as supplier_name
      FROM inventory_items i
      LEFT JOIN inventory_categories c ON i.category_id = c.id
      LEFT JOIN inventory_suppliers s ON i.supplier_id = s.id
      ORDER BY i.name
    `);
    client.release();
    res.json({ success: true, items: result.rows });
  } catch (error) {
    console.error('Error fetching inventory:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// ========== CATEGORIES ==========

// Get all categories
router.get('/categories', async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT * FROM inventory_categories ORDER BY name');
    client.release();
    res.json({ success: true, categories: result.rows });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Add inventory category
router.post('/categories', async (req, res) => {
  try {
    const { name, description } = req.body;
    const client = await pool.connect();
    const result = await client.query(
      'INSERT INTO inventory_categories (name, description) VALUES ($1, $2) RETURNING *',
      [name, description]
    );
    client.release();
    res.json({ success: true, category: result.rows[0] });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Update inventory category 
router.put('/categories/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { name, description } = req.body;
    const client = await pool.connect();
    const result = await client.query(
      'UPDATE inventory_categories SET name = $1, description = $2, updated_at = CURRENT_TIMESTAMP WHERE id = $3 RETURNING *',
      [name, description, id]
    );
    client.release();
    res.json({ success: true, category: result.rows[0] });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Delete inventory category
router.delete('/categories/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const client = await pool.connect();
    await client.query('DELETE FROM inventory_categories WHERE id = $1', [id]);
    client.release();
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// ========== SUPPLIERS ==========

// Get all suppliers
router.get('/suppliers', async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query('SELECT * FROM inventory_suppliers ORDER BY name');
    client.release();
    res.json({ success: true, suppliers: result.rows });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Add supplier
router.post('/suppliers', async (req, res) => {
  try {
    const { name, contact_person, email, phone, address } = req.body;
    const client = await pool.connect();
    const result = await client.query(
      'INSERT INTO inventory_suppliers (name, contact_person, email, phone, address) VALUES ($1, $2, $3, $4, $5) RETURNING *',
      [name, contact_person, email, phone, address]
    );
    client.release();
    res.json({ success: true, supplier: result.rows[0] });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Update supplier
router.put('/suppliers/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { name, contact_person, email, phone, address } = req.body;
    const client = await pool.connect();
    const result = await client.query(
      'UPDATE inventory_suppliers SET name = $1, contact_person = $2, email = $3, phone = $4, address = $5, updated_at = CURRENT_TIMESTAMP WHERE id = $6 RETURNING *',
      [name, contact_person, email, phone, address, id]
    );
    client.release();
    res.json({ success: true, supplier: result.rows[0] });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Delete supplier
router.delete('/suppliers/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const client = await pool.connect();
    await client.query('DELETE FROM inventory_suppliers WHERE id = $1', [id]);
    client.release();
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// ========== ITEMS ==========

// Get all inventory items with joins
router.get('/items', async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT 
        i.*,
        c.name as category_name,
        s.name as supplier_name
      FROM inventory_items i
      LEFT JOIN inventory_categories c ON i.category_id = c.id
      LEFT JOIN inventory_suppliers s ON i.supplier_id = s.id
      ORDER BY i.name
    `);
    client.release();
    res.json({ success: true, items: result.rows });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Add inventory item
router.post('/items', async (req, res) => {
  try {
    const { name, description, category_id, supplier_id, sku, unit_cost, selling_price, quantity_on_hand, reorder_level } = req.body;
    const client = await pool.connect();
    
    const result = await client.query(
      'INSERT INTO inventory_items (name, description, category_id, supplier_id, sku, unit_price, quantity_in_stock, minimum_stock_level) VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING *',
      [name, description, category_id || null, supplier_id || null, sku, unit_cost || selling_price, quantity_on_hand || 0, reorder_level || 0]
    );
    
    // Record initial stock movement if quantity > 0
    if (quantity_on_hand > 0) {
      await client.query(
        'INSERT INTO inventory_movements (item_id, movement_type, quantity, reference_type, notes) VALUES ($1, $2, $3, $4, $5)',
        [result.rows[0].id, 'in', quantity_on_hand, 'initial_stock', 'Initial stock entry']
      );
    }
    
    client.release();
    res.json({ success: true, item: result.rows[0] });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Update inventory item
router.put('/items/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { name, description, category_id, supplier_id, sku, unit_cost, selling_price, quantity_on_hand, reorder_level, status } = req.body;
    
    if (!name) {
      return res.status(400).json({ success: false, error: 'Name is required' });
    }
    
    const client = await pool.connect();
    const result = await client.query(
      'UPDATE inventory_items SET name = $1, description = $2, category_id = $3, supplier_id = $4, sku = $5, unit_price = $6, quantity_in_stock = $7, minimum_stock_level = $8, status = $9, updated_at = CURRENT_TIMESTAMP WHERE id = $10 RETURNING *',
      [name, description, category_id, supplier_id, sku, unit_cost || selling_price, quantity_on_hand, reorder_level, status, id]
    );
    client.release();
    
    if (result.rows.length === 0) {
      return res.status(404).json({ success: false, error: 'Item not found' });
    }
    
    res.json({ success: true, item: result.rows[0] });
  } catch (error) {
    console.error('Update inventory item error:', error);
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Delete inventory item
router.delete('/items/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const client = await pool.connect();
    await client.query('DELETE FROM inventory_items WHERE id = $1', [id]);
    client.release();
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Adjust stock for item
router.post('/items/:id/adjust-stock', async (req, res) => {
  try {
    const { id } = req.params;
    const { adjustment_type, quantity, notes } = req.body;
    
    // Validation
    if (!adjustment_type) {
      return res.status(400).json({ success: false, error: 'Adjustment type is required' });
    }
    
    if (!['in', 'out', 'adjustment'].includes(adjustment_type)) {
      return res.status(400).json({ success: false, error: 'Invalid adjustment type. Must be: in, out, or adjustment' });
    }
    
    if (!quantity || isNaN(quantity) || quantity <= 0) {
      return res.status(400).json({ success: false, error: 'Valid quantity is required' });
    }
    
    const client = await pool.connect();
    
    // Get current item details
    console.log(`Fetching item with ID: ${id}`);
    const itemResult = await client.query('SELECT * FROM inventory_items WHERE id = $1', [id]);
    if (itemResult.rows.length === 0) {
      client.release();
      return res.status(404).json({ success: false, error: 'Item not found' });
    }
    
    const item = itemResult.rows[0];
    console.log(`Current item stock: ${item.quantity_in_stock}, adjustment_type: ${adjustment_type}, quantity: ${quantity}`);
    
    let newQuantity;
    
    if (adjustment_type === 'in') {
      newQuantity = (item.quantity_in_stock || 0) + parseInt(quantity);
    } else if (adjustment_type === 'out') {
      newQuantity = (item.quantity_in_stock || 0) - parseInt(quantity);
    } else { // adjustment
      newQuantity = parseInt(quantity);
    }
    
    console.log(`New quantity will be: ${newQuantity}`);
    
    // Prevent negative stock for 'out' adjustments (unless explicitly allowed)
    if (newQuantity < 0) {
      console.log(`Warning: Stock adjustment for item ${id} will result in negative quantity: ${newQuantity}`);
    }
    
    // Update item quantity
    console.log(`Updating item ${id} quantity to ${newQuantity}`);
    await client.query(
      'UPDATE inventory_items SET quantity_in_stock = $1, updated_at = CURRENT_TIMESTAMP WHERE id = $2',
      [newQuantity, id]
    );
    
    // Record movement
    console.log(`Recording movement: item_id=${id}, movement_type=${adjustment_type}, quantity=${quantity}, notes=${notes || ''}`);
    await client.query(
      'INSERT INTO inventory_movements (item_id, movement_type, quantity, reference_type, notes) VALUES ($1, $2, $3, $4, $5)',
      [id, adjustment_type, parseInt(quantity), 'manual_adjustment', notes || '']
    );
    
    client.release();
    res.json({ success: true, new_quantity: newQuantity, old_quantity: item.quantity_in_stock });
  } catch (error) {
    console.error('Stock adjustment error:', error);
    if (client) {
      client.release();
    }
    res.status(500).json({ success: false, error: 'Internal server error', details: error.message });
  }
});

// ========== ASSIGNMENTS ==========

// Get all assignments
router.get('/assignments', async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT 
        a.*,
        c.name as client_name,
        i.name as item_name,
        i.sku
      FROM inventory_assignments a
      LEFT JOIN clients c ON a.client_id = c.id
      LEFT JOIN inventory_items i ON a.item_id = i.id
      ORDER BY a.assigned_date DESC
    `);
    client.release();
    res.json({ success: true, assignments: result.rows });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// Create assignment
router.post('/assignments', async (req, res) => {
  try {
    const { client_id, item_id, quantity, assigned_date, notes } = req.body;
    
    if (!client_id || !item_id || !quantity || quantity <= 0) {
      return res.status(400).json({ success: false, error: 'Client, item, and valid quantity are required' });
    }
    
    const client = await pool.connect();
    
    // Check if item has sufficient stock
    const itemResult = await client.query('SELECT quantity_in_stock FROM inventory_items WHERE id = $1', [item_id]);
    if (itemResult.rows.length === 0) {
      client.release();
      return res.status(404).json({ success: false, error: 'Item not found' });
    }
    
    const currentStock = itemResult.rows[0].quantity_in_stock;
    if (currentStock < quantity) {
      client.release();
      return res.status(400).json({ success: false, error: 'Insufficient stock' });
    }
    
    // Create assignment
    const assignmentResult = await client.query(
      'INSERT INTO inventory_assignments (client_id, item_id, quantity, assigned_date, notes) VALUES ($1, $2, $3, $4, $5) RETURNING *',
      [client_id, item_id, quantity, assigned_date, notes]
    );
    
    // Update item stock
    await client.query(
      'UPDATE inventory_items SET quantity_in_stock = quantity_in_stock - $1, updated_at = CURRENT_TIMESTAMP WHERE id = $2',
      [quantity, item_id]
    );
    
    // Record movement
    await client.query(
      'INSERT INTO inventory_movements (item_id, movement_type, quantity, reference_type, reference_id, notes) VALUES ($1, $2, $3, $4, $5, $6)',
      [item_id, 'out', quantity, 'assignment', assignmentResult.rows[0].id, `Assigned to client: ${notes || ''}`]
    );
    
    client.release();
    res.json({ success: true, assignment: assignmentResult.rows[0] });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

// ========== MOVEMENTS ==========

// Get all movements
router.get('/movements', async (req, res) => {
  try {
    const client = await pool.connect();
    const result = await client.query(`
      SELECT 
        m.*,
        i.name as item_name,
        i.sku
      FROM inventory_movements m
      LEFT JOIN inventory_items i ON m.item_id = i.id
      ORDER BY m.created_at DESC
      LIMIT 1000
    `);
    client.release();
    res.json({ success: true, movements: result.rows });
  } catch (error) {
    res.status(500).json({ success: false, error: 'Internal server error' });
  }
});

module.exports = router;
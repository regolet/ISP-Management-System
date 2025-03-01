<?php
require_once '../config.php';
check_auth();

$page_title = "Billing Form";
include 'header.php';
include 'navbar.php';

$billing = [
    'id' => '',
    'customer_id' => '',
    'invoiceid' => 'INV-' . date('Ymd') . '-' . rand(1000, 9999),
    'amount' => '0.00',
    'status' => 'unpaid',
    'due_date' => date('Y-m-d', strtotime('+1 month')),
    'billtocustomer' => '',
    'billingaddress' => '',
    'discount' => '0.00',
    'companyname' => '',
    'companyaddress' => ''
];

$billing_items = [];
$is_edit = false;
$title = "Create New Bill";

// Get all active customers with their plan details
$customers_query = "SELECT c.*, p.name as plan_name, p.amount as plan_amount, p.description as plan_description 
                   FROM customers c 
                   LEFT JOIN plans p ON c.plan_id = p.id 
                   WHERE c.status != 'inactive' 
                   ORDER BY c.name";
$customers = $conn->query($customers_query);

if (isset($_GET['id'])) {
    $is_edit = true;
    $title = "Edit Bill";
    $id = clean_input($_GET['id']);
    
    // Get billing details
    $stmt = $conn->prepare("SELECT * FROM billing WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $billing = $result->fetch_assoc();
        
        // Get billing items
        $items_stmt = $conn->prepare("SELECT * FROM billingitems WHERE billingid = ?");
        $items_stmt->bind_param("i", $id);
        $items_stmt->execute();
        $billing_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $_SESSION['error'] = "Bill not found";
        header("Location: billing.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate required fields
    if (empty($_POST['customer_id'])) $errors[] = "Customer is required";
    if (empty($_POST['invoiceid'])) $errors[] = "Invoice ID is required";
    if (empty($_POST['due_date'])) $errors[] = "Due date is required";
    if (empty($_POST['billtocustomer'])) $errors[] = "Bill to customer is required";
    if (empty($_POST['billingaddress'])) $errors[] = "Billing address is required";
    
    // Validate items
    $hasValidItems = false;
    if (!empty($_POST['items'])) {
        foreach ($_POST['items'] as $item) {
            if (!empty($item['description']) && isset($item['qty']) && isset($item['price'])) {
                $hasValidItems = true;
                break;
            }
        }
    }
    if (!$hasValidItems) $errors[] = "At least one billing item is required";
    
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $customer_id = clean_input($_POST['customer_id']);
            $invoiceid = clean_input($_POST['invoiceid']);
            $amount = clean_input($_POST['amount']);
            $status = clean_input($_POST['status']);
            $due_date = clean_input($_POST['due_date']);
            $billtocustomer = clean_input($_POST['billtocustomer']);
            $billingaddress = clean_input($_POST['billingaddress']);
            $discount = clean_input($_POST['discount']) ?: 0;
            $companyname = clean_input($_POST['companyname']);
            $companyaddress = clean_input($_POST['companyaddress']);
            
            if ($is_edit) {
                // Update existing bill
                $stmt = $conn->prepare("UPDATE billing SET 
                    customer_id = ?, invoiceid = ?, amount = ?, status = ?, 
                    due_date = ?, billtocustomer = ?, billingaddress = ?, 
                    discount = ?, companyname = ?, companyaddress = ? 
                    WHERE id = ?");
                $stmt->bind_param("isdssssdssi", 
                    $customer_id, $invoiceid, $amount, $status, $due_date,
                    $billtocustomer, $billingaddress, $discount, 
                    $companyname, $companyaddress, $id
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Error updating bill: " . $conn->error);
                }
                
                // Delete existing items
                $stmt = $conn->prepare("DELETE FROM billingitems WHERE billingid = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                $billing_id = $id;
            } else {
                // Create new bill
                $stmt = $conn->prepare("INSERT INTO billing (
                    customer_id, invoiceid, amount, status, due_date,
                    billtocustomer, billingaddress, discount, companyname, companyaddress
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isdssssdss", 
                    $customer_id, $invoiceid, $amount, $status, $due_date,
                    $billtocustomer, $billingaddress, $discount,
                    $companyname, $companyaddress
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Error creating bill: " . $conn->error);
                }
                
                $billing_id = $conn->insert_id;
            }
            
            // Insert billing items
            if (!empty($_POST['items'])) {
                $stmt = $conn->prepare("INSERT INTO billingitems (
                    billingid, itemdescription, qty, price, totalprice
                ) VALUES (?, ?, ?, ?, ?)");
                
                foreach ($_POST['items'] as $item) {
                    if (empty($item['description']) || !isset($item['qty']) || !isset($item['price'])) {
                        continue;
                    }
                    
                    $description = clean_input($item['description']);
                    $qty = clean_input($item['qty']);
                    $price = clean_input($item['price']);
                    $total = $qty * $price;
                    
                    $stmt->bind_param("isidd", 
                        $billing_id, $description, $qty, $price, $total
                    );
                    if (!$stmt->execute()) {
                        throw new Exception("Error saving billing item: " . $conn->error);
                    }
                }
            }
            
            $conn->commit();
            $_SESSION['success'] = $is_edit ? "Bill updated successfully" : "Bill created successfully";
            header("Location: billing.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}
?>

<style>
/* Keyboard Shortcuts Help */
.keyboard-shortcuts-help {
    position: fixed;
    bottom: 1rem;
    right: 1rem;
    background: #fff;
    border: 1px solid #ddd;
    padding: 0.75rem;
    border-radius: 2px;
    font-size: 0.85rem;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.2s ease;
}

.keyboard-shortcuts-help.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.keyboard-shortcuts-help ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.keyboard-shortcuts-help li {
    margin-bottom: 0.25rem;
}

.keyboard-shortcuts-help kbd {
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 2px;
    padding: 0.1rem 0.3rem;
    font-size: 0.8rem;
}

/* Keyboard Shortcuts Toggle */
.keyboard-shortcuts-toggle {
    position: fixed;
    bottom: 1rem;
    right: 1rem;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    background: #fff;
    border: 1px solid #ddd;
    color: #333;
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.keyboard-shortcuts-toggle:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-color: #ccc;
}

.keyboard-shortcuts-toggle:active {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .keyboard-shortcuts-help,
    .keyboard-shortcuts-toggle {
        display: none;
    }
}
</style>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><?php echo htmlspecialchars($title); ?></h4>
            <a href="billing.php" class="btn btn-secondary" aria-label="Back to Billing List">
                <i class="bx bx-arrow-back"></i> Back
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert" aria-live="polite">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="billingForm" action="billing_handler.php" role="form" aria-label="Billing Form">
            <?php if ($is_edit): ?>
            <input type="hidden" id="billing_id" name="billing_id" value="<?php echo htmlspecialchars($billing['id']); ?>">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="customer_id" class="form-label">Customer</label>
                    <select class="form-select" id="customer_id" name="customer_id" required aria-label="Select Customer">
                        <option value="">Select a customer</option>
                        <?php while ($customer = $customers->fetch_assoc()): ?>
                            <option value="<?php echo $customer['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($customer['name']); ?>"
                                    data-address="<?php echo htmlspecialchars($customer['address']); ?>"
                                    data-plan="<?php echo htmlspecialchars($customer['plan_name'] ?? ''); ?>"
                                    data-plan-amount="<?php echo htmlspecialchars($customer['plan_amount'] ?? '0.00'); ?>"
                                    data-plan-description="<?php echo htmlspecialchars($customer['plan_description'] ?? ''); ?>"
                                    <?php echo ($customer['id'] == $billing['customer_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($customer['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="invoiceid" class="form-label">Invoice ID</label>
                    <input type="text" class="form-control" id="invoiceid" name="invoiceid" 
                           value="<?php echo htmlspecialchars($billing['invoiceid']); ?>" required
                           aria-label="Invoice ID">
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" required aria-label="Select Status">
                        <option value="unpaid" <?php echo ($billing['status'] == 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                        <option value="paid" <?php echo ($billing['status'] == 'paid') ? 'selected' : ''; ?>>Paid</option>
                        <option value="overdue" <?php echo ($billing['status'] == 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="due_date" class="form-label">Due Date</label>
                    <input type="date" class="form-control" id="due_date" name="due_date" 
                           value="<?php echo htmlspecialchars($billing['due_date']); ?>" required
                           aria-label="Due Date">
                </div>
                <div class="col-md-4">
                    <label for="discount" class="form-label">Discount Amount</label>
                    <div class="input-group">
                        <span class="input-group-text" aria-hidden="true">₱</span>
                        <input type="number" class="form-control" id="discount" name="discount" step="0.01"
                               value="<?php echo htmlspecialchars($billing['discount']); ?>" required
                               aria-label="Discount Amount">
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="billtocustomer" class="form-label">Bill To</label>
                    <input type="text" class="form-control" id="billtocustomer" name="billtocustomer" 
                           value="<?php echo htmlspecialchars($billing['billtocustomer']); ?>" required
                           aria-label="Bill To Customer Name">
                </div>
                <div class="col-md-6">
                    <label for="billingaddress" class="form-label">Billing Address</label>
                    <input type="text" class="form-control" id="billingaddress" name="billingaddress" 
                           value="<?php echo htmlspecialchars($billing['billingaddress']); ?>" required
                           aria-label="Billing Address">
                </div>
                <div class="col-md-6">
                    <label for="companyname" class="form-label">Company Name</label>
                    <input type="text" class="form-control" id="companyname" name="companyname" 
                           value="<?php echo htmlspecialchars($billing['companyname']); ?>"
                           aria-label="Company Name">
                </div>
                <div class="col-md-6">
                    <label for="companyaddress" class="form-label">Company Address</label>
                    <input type="text" class="form-control" id="companyaddress" name="companyaddress"
                           value="<?php echo htmlspecialchars($billing['companyaddress']); ?>"
                           aria-label="Company Address">
                </div>
            </div>

            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="mb-0">Items</label>
                    <button type="button" class="btn btn-primary" id="addItem" aria-label="Add New Item">
                        <i class="bx bx-plus" aria-hidden="true"></i> Add Item
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table" id="itemsTable" role="grid" aria-label="Billing Items">
                        <thead>
                            <tr>
                                <th scope="col">Description</th>
                                <th scope="col" style="width: 120px;">Quantity</th>
                                <th scope="col" style="width: 150px;">Price</th>
                                <th scope="col" style="width: 150px;">Total</th>
                                <th scope="col" style="width: 50px;"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($billing_items)): ?>
                            <tr>
                                <td>
                                    <input type="text" class="form-control item-description" 
                                           name="items[][description]" required aria-label="Item Description">
                                </td>
                                <td>
                                    <input type="number" class="form-control item-qty" 
                                           name="items[][qty]" min="1" value="1" required aria-label="Item Quantity">
                                </td>
                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text" aria-hidden="true">₱</span>
                                        <input type="number" class="form-control item-price" 
                                               name="items[][price]" step="0.01" value="0.00" required aria-label="Item Price">
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <span class="input-group-text" aria-hidden="true">₱</span>
                                        <input type="number" class="form-control item-total" 
                                               step="0.01" value="0.00" readonly aria-label="Item Total">
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm remove-item" aria-label="Remove Item">
                                        <i class="bx bx-trash" aria-hidden="true"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($billing_items as $item): ?>
                                <tr>
                                    <td>
                                        <input type="text" class="form-control item-description" 
                                               name="items[][description]" required aria-label="Item Description"
                                               value="<?php echo htmlspecialchars($item['itemdescription']); ?>">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control item-qty" 
                                               name="items[][qty]" min="1" required aria-label="Item Quantity"
                                               value="<?php echo htmlspecialchars($item['qty']); ?>">
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text" aria-hidden="true">₱</span>
                                            <input type="number" class="form-control item-price" 
                                                   name="items[][price]" step="0.01" required aria-label="Item Price"
                                                   value="<?php echo htmlspecialchars($item['price']); ?>">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text" aria-hidden="true">₱</span>
                                            <input type="number" class="form-control item-total" 
                                                   step="0.01" readonly aria-label="Item Total"
                                                   value="<?php echo htmlspecialchars($item['totalprice']); ?>">
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm remove-item" aria-label="Remove Item">
                                            <i class="bx bx-trash" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="totals-section">
                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            <div class="mb-2">
                                <label for="subtotal" class="form-label">Subtotal</label>
                                <div class="input-group">
                                    <span class="input-group-text" aria-hidden="true">₱</span>
                                    <input type="text" class="form-control" id="subtotal" readonly aria-label="Subtotal Amount">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label for="discountDisplay" class="form-label">Discount</label>
                                <div class="input-group">
                                    <span class="input-group-text" aria-hidden="true">₱</span>
                                    <input type="text" class="form-control" id="discountDisplay" readonly aria-label="Discount Amount">
                                </div>
                            </div>
                            <div>
                                <label for="totalAmount" class="form-label">Total Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text" aria-hidden="true">₱</span>
                                    <input type="text" class="form-control" id="totalAmount" name="amount" readonly aria-label="Total Amount">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end mt-4">
                <button type="button" class="btn btn-secondary me-2" onclick="window.location.href='billing.php'" aria-label="Cancel">Cancel</button>
                <button type="submit" class="btn btn-primary" aria-label="<?php echo $is_edit ? 'Update' : 'Create'; ?> Bill">
                    <?php echo $is_edit ? 'Update' : 'Create'; ?> Bill
                </button>
            </div>
        </form>

        <!-- Keyboard Shortcuts Help -->
        <div class="keyboard-shortcuts-help" role="complementary" aria-label="Keyboard shortcuts">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Keyboard Shortcuts</h6>
                <button type="button" class="btn btn-sm btn-link p-0" id="hideShortcuts" aria-label="Hide keyboard shortcuts">
                    <i class="bx bx-x"></i>
                </button>
            </div>
            <ul>
                <li><kbd>Alt</kbd> + <kbd>N</kbd> Add new item</li>
                <li><kbd>Alt</kbd> + <kbd>S</kbd> Submit form</li>
                <li><kbd>Tab</kbd> Navigate fields</li>
                <li><kbd>Enter</kbd> Next field</li>
                <li><kbd>?</kbd> Show/hide shortcuts</li>
            </ul>
        </div>

        <!-- Keyboard Shortcuts Toggle Button -->
        <button type="button" class="btn keyboard-shortcuts-toggle" id="showShortcuts" aria-label="Show keyboard shortcuts">
            <i class="bx bx-keyboard"></i>
        </button>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-populate customer details and plan
    $('#customer_id').change(function() {
        var $selected = $(this).find('option:selected');
        if ($selected.val()) {
            $('#billtocustomer').val($selected.data('name'));
            $('#billingaddress').val($selected.data('address'));

            if (!$('#billing_id').val()) {
                $('#itemsTable tbody').empty();
            }

            var planName = $selected.data('plan');
            var planAmount = $selected.data('plan-amount');
            var planDescription = $selected.data('plan-description');
            
            if (planName && planAmount) {
                var description = planDescription ? planName + ' - ' + planDescription : planName;
                var newRow = `
                    <tr>
                        <td><input type="text" class="form-control item-description" name="items[][description]" required value="${description}" aria-label="Item Description"></td>
                        <td><input type="number" class="form-control item-qty" name="items[][qty]" min="1" value="1" required aria-label="Item Quantity"></td>
                        <td>
                            <div class="input-group">
                                <span class="input-group-text" aria-hidden="true">₱</span>
                                <input type="number" class="form-control item-price" name="items[][price]" step="0.01" value="${planAmount}" required aria-label="Item Price">
                            </div>
                        </td>
                        <td>
                            <div class="input-group">
                                <span class="input-group-text" aria-hidden="true">₱</span>
                                <input type="number" class="form-control item-total" step="0.01" value="${planAmount}" readonly aria-label="Item Total">
                            </div>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm remove-item" aria-label="Remove Item">
                                <i class="bx bx-trash" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                `;
                if ($('#itemsTable tbody tr').length === 0) {
                    $('#itemsTable tbody').append(newRow);
                } else {
                    $('#itemsTable tbody tr:first').replaceWith(newRow);
                }
                calculateTotals();
            }
        }
    });

    // Add new item row
    $('#addItem').click(function() {
        var newRow = `
            <tr>
                <td><input type="text" class="form-control item-description" name="items[][description]" required aria-label="Item Description"></td>
                <td><input type="number" class="form-control item-qty" name="items[][qty]" min="1" value="1" required aria-label="Item Quantity"></td>
                <td>
                    <div class="input-group">
                        <span class="input-group-text" aria-hidden="true">₱</span>
                        <input type="number" class="form-control item-price" name="items[][price]" step="0.01" value="0.00" required aria-label="Item Price">
                    </div>
                </td>
                <td>
                    <div class="input-group">
                        <span class="input-group-text" aria-hidden="true">₱</span>
                        <input type="number" class="form-control item-total" step="0.01" value="0.00" readonly aria-label="Item Total">
                    </div>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm remove-item" aria-label="Remove Item">
                        <i class="bx bx-trash" aria-hidden="true"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#itemsTable tbody').append(newRow);
        $('#itemsTable tbody tr:last input:first').focus();
        calculateTotals();
        announceChange('New item row added');
    });

    // Remove item row
    $(document).on('click', '.remove-item', function() {
        if ($('#itemsTable tbody tr').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
            announceChange('Item removed');
        } else {
            alert('At least one item is required.');
        }
    });

    // Calculate row total when quantity or price changes
    $(document).on('input', '.item-qty, .item-price', function() {
        calculateRowTotal($(this).closest('tr'));
        calculateTotals();
    });

    // Calculate single row total
    function calculateRowTotal($row) {
        var qty = parseFloat($row.find('.item-qty').val()) || 0;
        var price = parseFloat($row.find('.item-price').val()) || 0;
        var total = qty * price;
        $row.find('.item-total').val(total.toFixed(2));
    }

    // Calculate all totals
    function calculateTotals() {
        var subtotal = 0;
        $('#itemsTable tbody tr').each(function() {
            calculateRowTotal($(this));
            var total = parseFloat($(this).find('.item-total').val()) || 0;
            subtotal += total;
        });
        
        var discount = parseFloat($('#discount').val()) || 0;
        $('#subtotal').val(subtotal.toFixed(2));
        $('#discountDisplay').val(discount.toFixed(2));
        var total = subtotal - discount;
        $('#totalAmount').val(total.toFixed(2));
        announceChange('Total updated: ₱' + total.toFixed(2));
    }

    // Update totals when discount changes
    $('#discount').on('input', calculateTotals);

    // Calculate initial totals
    calculateTotals();

    // Enhanced form validation
    $('#billingForm').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var isValid = true;
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        $('.alert-danger').remove();
        
        // Check items table
        var $items = $('#itemsTable tbody tr');
        
        var hasValidItems = false;
        var itemsData = [];
        
        $items.each(function(index) {
            var $row = $(this);
            var description = $row.find('.item-description').val();
            var qty = parseFloat($row.find('.item-qty').val()) || 0;
            var price = parseFloat($row.find('.item-price').val()) || 0;
            
            // Store the values in proper array format
            $row.find('.item-description').attr('name', `items[${index}][description]`);
            $row.find('.item-qty').attr('name', `items[${index}][qty]`);
            $row.find('.item-price').attr('name', `items[${index}][price]`);
            
            if (description && qty > 0 && price > 0) {
                hasValidItems = true;
                itemsData.push({description, qty, price});
            }
        });
        
        console.log('Items data:', itemsData);
        
        if (!hasValidItems) {
            isValid = false;
            $('#itemsTable').before('<div class="alert alert-danger">Please add at least one valid billing item with description, quantity, and price greater than 0.</div>');
        }
        
        // Check other required fields
        $form.find('[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                $(this).after('<div class="invalid-feedback">This field is required</div>');
                isValid = false;
            }
        });
        
        if (isValid) {
            console.log('Form is valid, submitting...');
            
            // Add loading state to submit button
            var $submitBtn = $form.find('button[type="submit"]');
            $submitBtn.prop('disabled', true)
                     .html('<i class="bx bx-loader bx-spin"></i> Processing...')
                     .attr('aria-label', 'Processing, please wait...');
                     
            this.submit();
        } else {
            console.log('Form validation failed');
            announceChange('Form validation failed. Please check the highlighted fields.');
            var $firstError = $('.is-invalid, .alert-danger').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 200);
            }
        }
    });

    // Real-time validation
    $('[required]').on('input', function() {
        var $field = $(this);
        if ($field.val()) {
            $field.removeClass('is-invalid');
            $field.next('.invalid-feedback').remove();
        }
    });

    // Enhanced keyboard navigation
    $(document).on('keydown', 'input, select', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            var $inputs = $('input:visible, select:visible');
            var nextIndex = $inputs.index(this) + 1;
            if (nextIndex < $inputs.length) {
                $inputs.eq(nextIndex).focus();
            }
        }
    });

    // Focus first input on page load
    $('#customer_id').focus();

    // Handle tab key in items table
    $('#itemsTable').on('keydown', 'tr:last input:last', function(e) {
        if (e.key === 'Tab' && !e.shiftKey) {
            e.preventDefault();
            $('#addItem').click();
        }
    });

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Announce changes to screen readers
    function announceChange(message) {
        var $announcer = $('#aria-announcer');
        if (!$announcer.length) {
            $announcer = $('<div id="aria-announcer" class="visually-hidden" role="status" aria-live="polite"></div>');
            $('body').append($announcer);
        }
        $announcer.text(message);
    }

    // Keyboard shortcuts panel toggle with animations
    $('#showShortcuts').click(function() {
        var $help = $('.keyboard-shortcuts-help');
        var $toggle = $(this);
        
        $help.addClass('show');
        $toggle.css({
            'transform': 'scale(0)',
            'opacity': '0'
        });
        
        setTimeout(function() {
            $toggle.hide();
            $toggle.css({
                'transform': '',
                'opacity': ''
            });
        }, 200);
        
        announceChange('Keyboard shortcuts panel opened');
    });

    $('#hideShortcuts').click(function() {
        var $help = $('.keyboard-shortcuts-help');
        var $toggle = $('#showShortcuts');
        
        $help.removeClass('show');
        $toggle.show().css({
            'transform': 'scale(0)',
            'opacity': '0'
        });
        
        setTimeout(function() {
            $toggle.css({
                'transform': 'scale(1)',
                'opacity': '1'
            });
        }, 10);
        
        announceChange('Keyboard shortcuts panel closed');
    });

    // Question mark shortcut to toggle shortcuts panel
    $(document).on('keydown', function(e) {
        if (e.key === '?' && !$(e.target).is('input, textarea, select')) {
            e.preventDefault();
            if ($('.keyboard-shortcuts-help').hasClass('show')) {
                $('#hideShortcuts').click();
            } else {
                $('#showShortcuts').click();
            }
        }
    });

    // Hide shortcuts panel on escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('.keyboard-shortcuts-help').hasClass('show')) {
            $('#hideShortcuts').click();
        }
    });

    // Focus trap for keyboard shortcuts panel
    function setupFocusTrap() {
        var $panel = $('.keyboard-shortcuts-help');
        var $focusableElements = $panel.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        var $firstFocusable = $focusableElements.first();
        var $lastFocusable = $focusableElements.last();
        var lastFocusedElement = null;

        $panel.on('keydown', function(e) {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === $firstFocusable[0]) {
                        e.preventDefault();
                        $lastFocusable.focus();
                    }
                } else {
                    if (document.activeElement === $lastFocusable[0]) {
                        e.preventDefault();
                        $firstFocusable.focus();
                    }
                }
            }
        });

        $('#showShortcuts').click(function() {
            lastFocusedElement = document.activeElement;
            setTimeout(function() {
                $firstFocusable.focus();
            }, 200);
        });

        $('#hideShortcuts').click(function() {
            if (lastFocusedElement) {
                setTimeout(function() {
                    lastFocusedElement.focus();
                }, 200);
            }
        });
    }

    // Initialize focus trap
    setupFocusTrap();
});
</script>

<?php include 'footer.php'; ?>

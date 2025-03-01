<?php
session_start();
require_once '../config.php';
check_auth();
$page_title = 'Inventory';
$_SESSION['active_menu'] = 'inventory';
include 'header.php';
include 'navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <?php include 'alerts.php'; ?>
        
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="h2">Inventory Management</h1>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <!-- Total Products -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 h-100" style="background: rgba(13, 110, 253, 0.1);">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-primary text-white rounded-3" style="width: 48px; height: 48px;">
                                <i class="bx bx-box fs-3"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-primary fw-bold mb-1">Total Products</h6>
                                <h3 class="card-title mb-1">0</h3>
                                <small class="text-muted">Available products</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Stock -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 h-100" style="background: rgba(255, 193, 7, 0.1);">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-warning text-white rounded-3" style="width: 48px; height: 48px;">
                                <i class="bx bx-error fs-3"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-warning fw-bold mb-1">Low Stock</h6>
                                <h3 class="card-title mb-1">0</h3>
                                <small class="text-muted">Items below threshold</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock In -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 h-100" style="background: rgba(25, 135, 84, 0.1);">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-success text-white rounded-3" style="width: 48px; height: 48px;">
                                <i class="bx bx-import fs-3"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-success fw-bold mb-1">Stock In</h6>
                                <h3 class="card-title mb-1">0</h3>
                                <small class="text-muted">This month</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Out -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 h-100" style="background: rgba(220, 53, 69, 0.1);">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-danger text-white rounded-3" style="width: 48px; height: 48px;">
                                <i class="bx bx-export fs-3"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-danger fw-bold mb-1">Stock Out</h6>
                                <h3 class="card-title mb-1">0</h3>
                                <small class="text-muted">This month</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Product Code</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Table content will be dynamically loaded -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Buttons -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1030;">
    <div class="d-flex flex-column gap-2">
        <!-- Stock Out Button -->
        <button type="button" class="btn btn-danger floating-action-button" data-bs-toggle="modal" data-bs-target="#stockOutModal">
            <i class="bx bx-export"></i>
            <span class="fab-label">Stock Out</span>
        </button>
        
        <!-- Stock In Button -->
        <button type="button" class="btn btn-success floating-action-button" data-bs-toggle="modal" data-bs-target="#stockInModal">
            <i class="bx bx-import"></i>
            <span class="fab-label">Stock In</span>
        </button>
        
        <!-- Add Product Button -->
        <button type="button" class="btn btn-primary floating-action-button" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bx bx-plus"></i>
            <span class="fab-label">Add Product</span>
        </button>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="productForm" method="POST" action="product_save.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="product_code" class="form-label">Product Code</label>
                        <input type="text" class="form-control" id="product_code" name="product_code" required>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="hardware">Hardware</option>
                            <option value="accessories">Accessories</option>
                            <option value="tools">Tools</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="unit" class="form-label">Unit</label>
                        <input type="text" class="form-control" id="unit" name="unit" required>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="stock_threshold" class="form-label">Low Stock Threshold</label>
                        <input type="number" class="form-control" id="stock_threshold" name="stock_threshold" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stock In Modal -->
<div class="modal fade" id="stockInModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Stock In</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="stockInForm" method="POST" action="stock_in_save.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Product</label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value="">Select Product</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" required>
                    </div>
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save Stock In</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stock Out Modal -->
<div class="modal fade" id="stockOutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Stock Out</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="stockOutForm" method="POST" action="stock_out_save.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Product</label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value="">Select Product</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" required>
                    </div>
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Save Stock Out</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.floating-action-button {
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
}

.floating-action-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.35);
}

.floating-action-button i {
    font-size: 1.25rem;
}

@media (max-width: 768px) {
    .floating-action-button {
        width: 48px;
        height: 48px;
        padding: 0;
        justify-content: center;
        border-radius: 50%;
    }
    
    .floating-action-button .fab-label {
        display: none;
    }
}
</style>

<?php include 'footer.php'; ?>
$(document).ready(function() {
    /**
     * View invoice details
     */
    function viewInvoice(invoiceId) {
        // Navigate to detailed invoice view
        window.location.href = 'billing.php?invoice=' + invoiceId;
    }

    /**
     * Format currency amount
     */
    function formatCurrency(amount) {
        return '$' + parseFloat(amount).toFixed(2);
    }

    /**
     * Format date in a user-friendly way
     */
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    // Function to record payment
    window.recordPayment = function(invoiceId) {
        $('#billingId').val(invoiceId);
        $('#paymentModal').modal('show');
    };

    // Function to save payment
    window.savePayment = function() {
        var formData = $('#paymentForm').serialize();
        $.ajax({
            url: 'api/payments.php',
            method: 'POST',
            data: formData,
            success: function(response) {
                alert('Payment recorded successfully.');
                location.reload();
            },
            error: function() {
                alert('Error recording payment.');
            }
        });
    };

    // Function to edit invoice
    window.editInvoice = function(invoiceId) {
        $.ajax({
            url: 'api/billing.php?id=' + invoiceId,
            method: 'GET',
            success: function(data) {
                // Assuming data contains the invoice details
                $('#invoiceId').val(data.id);
                $('#amount').val(data.total_amount);
                $('#dueDate').val(data.due_date);
                $('#status').val(data.status);
                $('#editInvoiceModal').modal('show');
            },
            error: function() {
                alert('Error fetching invoice details for editing.');
            }
        });
    };

    // Function to delete invoice
    window.deleteInvoice = function(invoiceId) {
        if (confirm('Are you sure you want to delete this invoice?')) {
            $.ajax({
                url: 'api/billing.php',
                method: 'DELETE',
                data: { id: invoiceId },
                success: function(response) {
                    alert('Invoice deleted successfully.');
                    location.reload();
                },
                error: function() {
                    alert('Error deleting invoice.');
                }
            });
        }
    };

    // Function to clear search input
    window.clearSearch = function() {
        $('#search').val('');
        $('#filterForm').submit();
    };

    // Function to generate invoices
    window.generateInvoices = function() {
        $.ajax({
            url: '/api/billing/generate',
            method: 'POST',
            success: function(response) {
                alert('Invoices generated successfully.');
                location.reload();
            },
            error: function() {
                alert('Error generating invoices.');
            }
        });
    };
});

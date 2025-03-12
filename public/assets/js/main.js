$(document).ready(function() {
    // Function to view invoice details
    window.viewInvoice = function(invoiceId) {
        $.ajax({
            url: 'api/billing.php?id=' + invoiceId,
            method: 'GET',
            success: function(data) {
                $('#invoiceDetails').html(data);
                $('#viewInvoiceModal').modal('show');
            },
            error: function() {
                alert('Error fetching invoice details.');
            }
        });
    };

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
        // Logic to fetch invoice data and populate the edit form
        // This will require an additional AJAX call to get the invoice details
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
            url: 'scripts/generate_invoices.php',
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

<?php
/**
 * Coinbase Payment Return Handler
 *
 * Handles the redirect back from Coinbase after a successful payment.
 * Sets invoice status to "Payment Pending" so the user sees confirmation
 * instead of "Unpaid" while the async webhook is still in transit.
 */

require_once __DIR__ . '/../../../init.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Get and validate invoice ID
$invoiceId = isset($_GET['invoice_id']) ? (int) $_GET['invoice_id'] : 0;
if (!$invoiceId) {
    die('Invalid invoice ID');
}

// Get invoice details
$invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
if (!$invoice) {
    die('Invoice not found');
}

// Verify the logged-in user owns this invoice
session_start();
$loggedInUserId = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : 0;
if ($loggedInUserId !== (int) $invoice->userid) {
    die('Unauthorized access to invoice');
}

// If the invoice is still "Unpaid", mark it as "Payment Pending"
// Skip if already "Paid" (webhook arrived before the redirect)
if ($invoice->status === 'Unpaid') {
    Capsule::table('tblinvoices')
        ->where('id', $invoiceId)
        ->update(['status' => 'Payment Pending']);
}

// Build redirect URL to the invoice view page
$systemUrl = Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value');
header('Location: ' . $systemUrl . 'viewinvoice.php?id=' . $invoiceId);
exit;

<?php
/**
 * Coinbase Payment Link Redirect Handler
 *
 * Creates a payment link only when the user clicks "Pay Now" and redirects to Coinbase.
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/const.php';
require_once __DIR__ . '/PaymentLinkClient.php';
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Verify this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method');
}

// Get and validate invoice ID
$invoiceId = isset($_POST['invoice_id']) ? (int) $_POST['invoice_id'] : 0;
if (!$invoiceId) {
    die('Invalid invoice ID');
}

// Get gateway configuration
$gatewayModuleName = 'coinbase';
$gatewayParams = getGatewayVariables($gatewayModuleName);

if (!$gatewayParams['type']) {
    die('Coinbase gateway is not activated');
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

// Get invoice description from first line item
$description = '';
try {
    $description = Capsule::table('tblinvoiceitems')
        ->where('invoiceid', '=', $invoiceId)
        ->value('description');
    $description = (strlen($description) > 200) ? substr($description, 0, 197) . '...' : $description;
} catch (Exception $e) {
    $description = "Invoice #$invoiceId";
}

// Get client details
$client = Capsule::table('tblclients')->where('id', $invoice->userid)->first();

// Build return URL
$systemUrl = Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value');
$returnUrl = $systemUrl . 'viewinvoice.php?id=' . $invoiceId;

try {
    $paymentClient = new PaymentLinkClient(
        $gatewayParams['cdpKeyName'],
        $gatewayParams['cdpPrivateKey']
    );

    $paymentLinkData = [
        'amount' => $invoice->total,
        'description' => $description ?: "Invoice #$invoiceId",
        'metadata' => [
            METADATA_SOURCE_PARAM => METADATA_SOURCE_VALUE,
            METADATA_INVOICE_PARAM => (string) $invoiceId,
            METADATA_CLIENT_PARAM => (string) $invoice->userid,
            'firstName' => $client->firstname ?? null,
            'lastName' => $client->lastname ?? null,
            'email' => $client->email ?? null,
        ],
        'successUrl' => $returnUrl . '&paymentsuccess=true',
        'failUrl' => $returnUrl . '&paymentfailed=true',
    ];

    $response = $paymentClient->createPaymentLink($paymentLinkData);

    // Redirect to Coinbase payment page
    header('Location: ' . $response->url);
    exit;

} catch (Exception $e) {
    logTransaction($gatewayModuleName, ['error' => $e->getMessage()], 'Payment Link Creation Failed');
    die('Unable to process payment. Please try again or contact support.');
}

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

// Determine API path based on sandbox mode
$apiPath = (!empty($gatewayParams['sandboxMode']) && $gatewayParams['sandboxMode'] === 'on')
    ? PAYMENT_LINK_API_PATH_SANDBOX
    : PAYMENT_LINK_API_PATH;

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

$description = "Invoice - $invoiceId";

// Get client details
$client = Capsule::table('tblclients')->where('id', $invoice->userid)->first();

// Build return URLs
$systemUrl = Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value');
$successUrl = $systemUrl . 'modules/gateways/coinbase/return.php?invoice_id=' . $invoiceId;
$failUrl = $systemUrl . 'viewinvoice.php?id=' . $invoiceId . '&paymentfailed=true';

try {
    $paymentClient = new PaymentLinkClient(
        $gatewayParams['cdpKeyName'],
        $gatewayParams['cdpPrivateKey'],
        $apiPath
    );

    $paymentLinkData = [
        'amount' => $invoice->total,
        'description' => $description,
        'metadata' => [
            METADATA_SOURCE_PARAM => METADATA_SOURCE_VALUE,
            METADATA_INVOICE_PARAM => (string) $invoiceId,
            METADATA_CLIENT_PARAM => (string) $invoice->userid,
            'firstName' => $client->firstname ?? null,
            'lastName' => $client->lastname ?? null,
            'email' => $client->email ?? null,
        ],
        'successUrl' => $successUrl,
        'failUrl' => $failUrl,
    ];

    $response = $paymentClient->createPaymentLink($paymentLinkData);

    // Redirect to Coinbase payment page
    header('Location: ' . $response->url);
    exit;

} catch (Exception $e) {
    logTransaction($gatewayModuleName, ['error' => $e->getMessage()], 'Payment Link Creation Failed');
    die('Unable to process payment. Please try again or contact support.');
}

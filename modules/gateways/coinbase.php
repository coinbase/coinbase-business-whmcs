<?php
require_once __DIR__ . '/Coinbase/vendor/autoload.php';
require_once __DIR__ . '/Coinbase/const.php';
require_once __DIR__ . '/Coinbase/PaymentLinkClient.php';

use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function coinbase_MetaData()
{
    return array(
        'DisplayName' => 'Coinbase Business',
        'APIVersion' => '1.1',
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false
    );
}

function coinbase_config()
{
    // Global variable required
    global $customadminpath;

    // Build callback URL.
    $isHttps = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https');

    $protocol = $isHttps ? "https://" : "http://";
    $url = $protocol . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
    $url = substr($url, 0, strpos($url, $customadminpath));
    $callbackUrl = $url . "modules/gateways/callback/coinbase.php";

    $webhookDescription = "<p>Register this webhook URL in the <a href=\"https://portal.cdp.coinbase.com/\" target=\"_blank\">Coinbase Developer Platform</a>:</p>";
    $webhookDescription .= "<p><code>$callbackUrl</code></p>";

    if (!$isHttps) {
        $webhookDescription .= '<p style="color:red;">HTTPS is required for webhook notifications!</p>';
    }

    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Coinbase Business <a href="https://docs.cdp.coinbase.com/coinbase-business/payment-link-apis/overview" target="_blank" rel="noopener">(Learn more)</a>'
        ),
        'cdpKeyName' => array(
            'FriendlyName' => 'CDP API Key Name',
            'Description' => 'Your CDP API key name from <a href="https://portal.cdp.coinbase.com/projects/api-keys" target="_blank">Coinbase Developer Platform</a>',
            'Type' => 'text'
        ),
        'cdpPrivateKey' => array(
            'FriendlyName' => 'CDP Private Key',
            'Description' => 'EC Private Key in PEM format (starts with -----BEGIN EC PRIVATE KEY-----)',
            'Type' => 'textarea',
            'Rows' => '5'
        ),
        'webhookSecret' => array(
            'FriendlyName' => 'Webhook Secret',
            'Description' => 'Secret from your webhook subscription for signature verification',
            'Type' => 'password'
        ),
        'webhookUrl' => array(
            'FriendlyName' => 'Webhook URL',
            'Type' => '',
            'Size' => '',
            'Default' => '',
            'Description' => $webhookDescription
        ),
        'currencyNotice' => array(
            'FriendlyName' => '',
            'Type' => '',
            'Size' => '',
            'Default' => '',
            'Description' => '<p style="color:#0066cc;"><strong>Note:</strong> This gateway accepts <strong>USDC on Base network</strong> only. Invoice amounts will be charged in USDC.</p>'
        )
    );
}

function coinbase_link($params)
{
    if (!isset($params) || empty($params)) {
        die('Missing or invalid $params data.');
    }

    // Get invoice description from first line item
    $description = '';
    try {
        $description = Capsule::table('tblinvoiceitems')
            ->where("invoiceid", "=", $params['invoiceid'])
            ->value('description');
        // Truncate to fit API limit (500 chars, but keep it reasonable)
        $description = (strlen($description) > 200) ? substr($description, 0, 197) . '...' : $description;
    } catch (Exception $e) {
        $description = $params['description'];
    }

    try {
        $client = new PaymentLinkClient(
            $params['cdpKeyName'],
            $params['cdpPrivateKey']
        );

        $paymentLinkData = [
            'amount' => $params['amount'],
            'description' => empty($description) ? $params['description'] : $description,
            'metadata' => [
                METADATA_SOURCE_PARAM => METADATA_SOURCE_VALUE,
                METADATA_INVOICE_PARAM => (string) $params['invoiceid'],
                METADATA_CLIENT_PARAM => (string) $params['clientdetails']['userid'],
                'firstName' => $params['clientdetails']['firstname'] ?? null,
                'lastName' => $params['clientdetails']['lastname'] ?? null,
                'email' => $params['clientdetails']['email'] ?? null,
            ],
            'successUrl' => $params['returnurl'] . "&paymentsuccess=true",
            'failUrl' => $params['returnurl'] . "&paymentfailed=true",
        ];

        $response = $client->createPaymentLink($paymentLinkData);

        $form = '<form action="' . htmlspecialchars($response->url) . '" method="GET">';
        $form .= '<input type="submit" value="' . htmlspecialchars($params['langpaynow']) . '" />';
        $form .= '</form>';

        return $form;

    } catch (Exception $e) {
        logTransaction('coinbase', ['error' => $e->getMessage()], 'Payment Link Creation Failed');
        return '<p style="color: red;">Unable to process payment at this time. Please try again later or contact support.</p>';
    }
}

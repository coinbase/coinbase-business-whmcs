<?php
/**
 * Coinbase Business Webhook Handler
 *
 * Processes Payment Link API webhook events for payment status updates.
 */

require_once __DIR__ . '/../Coinbase/vendor/autoload.php';
require_once __DIR__ . '/../Coinbase/const.php';
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

class Webhook
{
    /**
     * @var string Gateway module name
     */
    private $gatewayModuleName;

    /**
     * @var array Gateway configuration parameters
     */
    private $gatewayParams;

    public function __construct()
    {
        $this->init();
    }

    private function init()
    {
        $this->gatewayModuleName = basename(__FILE__, '.php');
        $this->gatewayParams = getGatewayVariables($this->gatewayModuleName);
        $this->checkIsModuleActivated();
    }

    private function getModuleParam($paramName)
    {
        return array_key_exists($paramName, $this->gatewayParams) ? $this->gatewayParams[$paramName] : null;
    }

    private function checkIsModuleActivated()
    {
        if (!$this->getModuleParam('type')) {
            $this->failProcess('Coinbase Business module not activated');
        }
    }

    private function failProcess($errorMessage)
    {
        $this->log($errorMessage);
        http_response_code(500);
        die();
    }

    private function log($message)
    {
        logTransaction($this->gatewayModuleName, $_POST, $message);
    }

    /**
     * Main webhook processing entry point
     */
    public function process()
    {
        $payload = $this->getValidatedPayload();

        // Extract event data
        $eventType = $payload->eventType ?? null;
        $metadata = $payload->metadata ?? new stdClass();

        // Validate metadata source
        $source = $metadata->{METADATA_SOURCE_PARAM} ?? null;
        if ($source !== METADATA_SOURCE_VALUE) {
            $this->failProcess('Not a WHMCS payment - source mismatch');
        }

        // Get order info from metadata
        $orderId = $metadata->{METADATA_INVOICE_PARAM} ?? null;
        $userId = $metadata->{METADATA_CLIENT_PARAM} ?? null;

        if (!$orderId || !$userId) {
            $this->failProcess('Invoice ID or client ID was not found in payload');
        }

        // Verify order exists and belongs to user
        $this->verifyOrder($orderId, $userId);

        // Handle event types
        switch ($eventType) {
            case EVENT_PAYMENT_SUCCESS:
                $this->handlePaymentSuccess($orderId, $payload);
                break;

            case EVENT_PAYMENT_FAILED:
                $this->log(sprintf('Payment failed for invoice %s', $orderId));
                break;

            case EVENT_PAYMENT_EXPIRED:
                $this->log(sprintf('Payment expired for invoice %s', $orderId));
                break;

            default:
                $this->log(sprintf('Unknown event type: %s', $eventType));
        }

        // Return 200 OK to acknowledge receipt
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSuccess($orderId, $payload)
    {
        // Extract transaction details from payload
        $paymentLinkId = $payload->id ?? null;
        $settlement = $payload->settlement ?? new stdClass();

        // Use netAmount (after fees) or fall back to total amount
        $amount = $settlement->netAmount ?? $payload->amount ?? '0';
        $fee = $settlement->feeAmount ?? '0';

        // Use payment link ID as transaction ID
        $transactionId = $paymentLinkId;

        if (!$transactionId) {
            $this->failProcess('No transaction ID found in payload');
        }

        // Check for duplicate transaction
        checkCbTransID($transactionId);

        // Record the payment in WHMCS
        addInvoicePayment(
            $orderId,
            $transactionId,
            $amount,
            $fee,
            $this->gatewayModuleName
        );

        $this->log(sprintf('Payment successful for invoice %s. Transaction: %s, Amount: %s', $orderId, $transactionId, $amount));
    }

    /**
     * Validate webhook signature and return parsed payload
     */
    private function getValidatedPayload()
    {
        $webhookSecret = $this->getModuleParam('webhookSecret');
        $headers = array_change_key_case(getallheaders());
        $signatureHeader = $headers[SIGNATURE_HEADER] ?? null;
        $rawPayload = trim(file_get_contents('php://input'));

        if (!$signatureHeader) {
            $this->failProcess('Missing signature header');
        }

        if (!$this->verifySignature($rawPayload, $signatureHeader, $webhookSecret)) {
            $this->failProcess('Invalid webhook signature');
        }

        $payload = json_decode($rawPayload);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->failProcess('Invalid JSON payload');
        }

        return $payload;
    }

    /**
     * Verify X-Hook0-Signature header
     *
     * Format: t=timestamp;h=headers;v1=signature
     *
     * @param string $payload Raw request body
     * @param string $signatureHeader Header value
     * @param string $secret Webhook subscription secret
     * @return bool True if signature is valid
     */
    private function verifySignature(string $payload, string $signatureHeader, string $secret): bool
    {
        // Parse the signature header
        $parts = [];
        foreach (explode(';', $signatureHeader) as $part) {
            $split = explode('=', $part, 2);
            if (count($split) === 2) {
                $parts[$split[0]] = $split[1];
            }
        }

        $timestamp = $parts['t'] ?? null;
        $signature = $parts['v1'] ?? null;

        if (!$timestamp || !$signature) {
            return false;
        }

        // Check timestamp to prevent replay attacks (5 minute tolerance)
        $currentTime = time();
        if (abs($currentTime - (int)$timestamp) > 300) {
            return false;
        }

        // Build the signed payload: timestamp.payload
        $signedPayload = $timestamp . '.' . $payload;

        // Calculate expected signature using HMAC-SHA256
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        // Constant-time comparison to prevent timing attacks
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify order exists and belongs to user
     */
    private function verifyOrder($id, $userId)
    {
        $orderData = \Illuminate\Database\Capsule\Manager::table('tblinvoices')
            ->where('id', $id)
            ->where('userid', $userId)
            ->get();

        if (!$orderData || !isset($orderData[0]->id)) {
            $this->failProcess(sprintf('Order with ID "%s" does not exist', $id));
        }

        checkCbInvoiceID($id, $this->gatewayModuleName);

        return reset($orderData);
    }
}

$webhook = new Webhook();
$webhook->process();

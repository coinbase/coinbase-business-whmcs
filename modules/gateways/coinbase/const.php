<?php
// Metadata constants for order tracking
defined('METADATA_CLIENT_PARAM') or define('METADATA_CLIENT_PARAM', 'clientid');
defined('METADATA_INVOICE_PARAM') or define('METADATA_INVOICE_PARAM', 'invoiceid');
defined('METADATA_SOURCE_PARAM') or define('METADATA_SOURCE_PARAM', 'source');
defined('METADATA_SOURCE_VALUE') or define('METADATA_SOURCE_VALUE', 'whmcs');

// Checkout API configuration
defined('CHECKOUT_API_BASE') or define('CHECKOUT_API_BASE', 'https://business.coinbase.com');
defined('CHECKOUT_API_PATH') or define('CHECKOUT_API_PATH', '/api/v1/checkouts');
defined('CHECKOUT_API_PATH_SANDBOX') or define('CHECKOUT_API_PATH_SANDBOX', '/sandbox/api/v1/checkouts');

// JWT authentication
defined('JWT_ISSUER') or define('JWT_ISSUER', 'cdp');
defined('JWT_EXPIRY_SECONDS') or define('JWT_EXPIRY_SECONDS', 120);

// Webhook configuration
defined('SIGNATURE_HEADER') or define('SIGNATURE_HEADER', 'x-hook0-signature');

// Webhook event types
defined('EVENT_PAYMENT_SUCCESS') or define('EVENT_PAYMENT_SUCCESS', 'checkout.payment.success');
defined('EVENT_PAYMENT_FAILED') or define('EVENT_PAYMENT_FAILED', 'checkout.payment.failed');
defined('EVENT_PAYMENT_EXPIRED') or define('EVENT_PAYMENT_EXPIRED', 'checkout.payment.expired');

// Fixed currency and network
defined('PAYMENT_CURRENCY') or define('PAYMENT_CURRENCY', 'USDC');
defined('PAYMENT_NETWORK') or define('PAYMENT_NETWORK', 'base');


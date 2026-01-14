<?php
// Metadata constants for order tracking
defined('METADATA_CLIENT_PARAM') or define('METADATA_CLIENT_PARAM', 'clientid');
defined('METADATA_INVOICE_PARAM') or define('METADATA_INVOICE_PARAM', 'invoiceid');
defined('METADATA_SOURCE_PARAM') or define('METADATA_SOURCE_PARAM', 'source');
defined('METADATA_SOURCE_VALUE') or define('METADATA_SOURCE_VALUE', 'whmcs');

// Payment Link API configuration
defined('PAYMENT_LINK_API_BASE') or define('PAYMENT_LINK_API_BASE', 'https://business.coinbase.com');
defined('PAYMENT_LINK_API_PATH') or define('PAYMENT_LINK_API_PATH', '/api/v1/payment-links');

// JWT authentication
defined('JWT_ISSUER') or define('JWT_ISSUER', 'cdp');
defined('JWT_EXPIRY_SECONDS') or define('JWT_EXPIRY_SECONDS', 120);

// Webhook configuration
defined('SIGNATURE_HEADER') or define('SIGNATURE_HEADER', 'x-hook0-signature');

// Webhook event types
defined('EVENT_PAYMENT_SUCCESS') or define('EVENT_PAYMENT_SUCCESS', 'payment_link.payment.success');
defined('EVENT_PAYMENT_FAILED') or define('EVENT_PAYMENT_FAILED', 'payment_link.payment.failed');
defined('EVENT_PAYMENT_EXPIRED') or define('EVENT_PAYMENT_EXPIRED', 'payment_link.payment.expired');

// Fixed currency and network (Payment Link API only supports USDC on Base)
defined('PAYMENT_CURRENCY') or define('PAYMENT_CURRENCY', 'USDC');
defined('PAYMENT_NETWORK') or define('PAYMENT_NETWORK', 'base');


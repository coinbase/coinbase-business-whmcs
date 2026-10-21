## Coinbase Business Payment Gateway for WHMCS

Accept USDC payments on Base network through Coinbase Business Checkouts.

### About

This module integrates the [Coinbase Business Checkout API](https://docs.cdp.coinbase.com/coinbase-business/checkout-apis/overview) with WHMCS, allowing merchants to accept cryptocurrency payments directly to their Coinbase Business account.

**Note:** This gateway accepts **USDC on Base network** only.

### Requirements

- A WHMCS installation compatible with PHP 8.2 or 8.3; check the requirements for your [WHMCS version](https://docs.whmcs.com/9-0/installation-guide/system-requirements/)
- PHP **8.2.35 or later in the 8.2 branch**, or **8.3.36 or later in the 8.3 branch**, with the OpenSSL extension
- A matching ionCube Loader: **13.0.2+ for PHP 8.2** or **14.4.0+ for PHP 8.3**
- Coinbase Business account with CDP API access

### PHP Security Update — October 21, 2026

PHP 8.2.35 and 8.3.36 include the fixes for **CVE-2026-109618** and **CVE-2026-109619**. Follow the [PHP security advisory](https://www.php.net/archive/2026.php#2026-10-21-5) and your hosting provider's instructions to update the PHP runtime serving WHMCS. Installing this module or updating Composer dependencies does not update PHP itself.

Before installing or updating the module:

1. Confirm that your WHMCS version, ionCube Loader, and other modules support the PHP branch you plan to use. Follow the [WHMCS PHP upgrade procedure](https://docs.whmcs.com/9-0/system/php/php-upgrades/).
2. Upgrade the merchant-managed PHP packages or image to a fixed release in a supported branch, then restart all PHP workers serving WHMCS, including its payment and webhook endpoints. Check the PHP runtime used by the WHMCS cron separately.
3. Verify the actual web runtime in **Utilities > System > PHP Info** in WHMCS. A local `php -v` checks the CLI executable only and may report a different version from the web server. After the restart, confirm that the web runtime is PHP 8.2.35+ in the 8.2 branch or 8.3.36+ in the 8.3 branch, and that the cron uses a compatible, patched runtime.

The Composer `config.platform.php` value of `8.2.35` is a dependency-resolution baseline, not an installed interpreter. From the repository root, `composer check-platform-reqs --lock --no-dev` checks the actual CLI PHP against the full requirements, ignoring that simulated platform. For release ZIP or folder-copy installations, verify the web runtime directly: the bundled Composer autoloader checks only the lowest PHP version and does not validate every branch's security patch level.

Some hosting vendors backport security fixes without changing the upstream PHP version number. Obtain confirmation that both CVEs are fixed from your provider; the version number alone cannot establish that. This module's Composer requirements use the upstream fixed PHP 8.2/8.3 version ranges, so older vendor-backported versions outside those ranges do not satisfy its installation requirements.

### Installation

1. Clone this repository and run `composer install`, or download the build from the [releases page](https://github.com/coinbase/coinbase-commerce-whmcs/releases)

2. Copy the `modules` folder to the root of your WHMCS installation

3. Activate the gateway in WHMCS admin:
   - Go to **Setup > Payments > Payment Gateways > All Payment Gateways**
   - Click **Coinbase Business**

### Configuration

#### 1. Create CDP API Key

1. Log into the [Coinbase Developer Platform](https://portal.cdp.coinbase.com/)
2. Navigate to **API Keys** and create a new key
3. Copy the **Key Name** and **Private Key** (EC PEM format)

#### 2. Set Up Webhook

1. In the Coinbase Developer Platform, create a webhook subscription
2. Set the endpoint URL to your WHMCS callback (shown in the gateway settings)
3. Subscribe to Checkout events
4. Copy the **Webhook Secret** from the subscription

#### 3. Configure WHMCS

In the gateway settings, enter:

| Field | Value |
|-------|-------|
| CDP API Key Name | Your API key name/identifier |
| CDP Private Key | EC Private Key in PEM format |
| Webhook Secret | Secret from webhook subscription |

### How It Works

1. Customer clicks "Pay Now" on an invoice
2. A Checkout is created via the Coinbase API
3. Customer is redirected to the Coinbase payment page
4. Customer pays with USDC on Base network
5. Webhook notifies WHMCS when payment completes
6. Invoice is automatically marked as paid

### Support

- [Checkout API Documentation](https://docs.cdp.coinbase.com/coinbase-business/checkout-apis/overview)
- [Coinbase Developer Platform](https://portal.cdp.coinbase.com/)

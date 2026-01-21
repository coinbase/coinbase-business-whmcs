## Coinbase Business Payment Gateway for WHMCS

Accept USDC payments on Base network through Coinbase Business Payment Links.

### About

This module integrates the [Coinbase Business Payment Link API](https://docs.cdp.coinbase.com/coinbase-business/payment-link-apis/overview) with WHMCS, allowing merchants to accept cryptocurrency payments directly to their Coinbase Business account.

**Note:** This gateway accepts **USDC on Base network** only.

### Requirements

- WHMCS installation
- PHP 7.4+ with OpenSSL extension
- Coinbase Business account with CDP API access

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
3. Subscribe to Payment Link events
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
2. A Payment Link is created via the Coinbase API
3. Customer is redirected to the Coinbase payment page
4. Customer pays with USDC on Base network
5. Webhook notifies WHMCS when payment completes
6. Invoice is automatically marked as paid

### Support

- [Payment Link API Documentation](https://docs.cdp.coinbase.com/coinbase-business/payment-link-apis/overview)
- [Coinbase Developer Platform](https://portal.cdp.coinbase.com/)

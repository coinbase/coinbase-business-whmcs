# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Coinbase Business payment gateway module for WHMCS. Accepts USDC payments on Base network via the Coinbase Checkout API. **Note: This repository is not actively maintained.**

## Build Commands

```bash
# Install dependencies (from repo root)
composer install -n --prefer-dist

# Dependencies install to modules/gateways/Coinbase/vendor/
```

There are no test or lint commands configured for this project.

## GitHub CLI

`gh` commands require the `GH_HOST` environment variable:

```bash
GH_HOST=github.com gh pr create ...
```

## Architecture

### Payment Flow

1. **Invoice page** (`coinbase.php`): `coinbase_link()` renders a "Pay Now" form that POSTs to `redirect.php`
2. **Redirect** (`Coinbase/redirect.php`): Creates a Checkout via the Coinbase API and redirects the user to the Coinbase hosted payment page
3. **Return** (`Coinbase/return.php`): Handles the redirect back from Coinbase after successful payment. Sets invoice status to "Payment Pending" so the user doesn't see "Unpaid" while the async webhook is in transit
4. **Webhook** (`callback/coinbase.php`): Receives async POST notifications from Coinbase when payment status changes, validates the signature, and records the payment via `addInvoicePayment()`

### Key Components

All files live under `modules/gateways/`:

- **`coinbase.php`** — Main WHMCS gateway module (config UI, payment button generation). This is the only file that uses the `if (!defined("WHMCS"))` access guard
- **`Coinbase/redirect.php`** — Standalone entry point (POST). Creates Checkout and redirects to Coinbase
- **`Coinbase/return.php`** — Standalone entry point (GET). Handles return redirect, sets "Payment Pending" status
- **`callback/coinbase.php`** — Standalone entry point (POST). Webhook handler with `Webhook` class
- **`Coinbase/CheckoutClient.php`** — HTTP client for the Checkout API (create/get checkouts via Guzzle)
- **`Coinbase/JwtAuth.php`** — ES256 JWT token generation for CDP API authentication (uses `firebase/php-jwt`)
- **`Coinbase/const.php`** — Constants: metadata field names, API paths, webhook event types, signature header

### WHMCS Conventions

- Gateway module functions are prefixed with the gateway name: `coinbase_MetaData()`, `coinbase_config()`, `coinbase_link()`
- Uses Illuminate Database Capsule ORM (`Capsule::table(...)`) for all database access
- Invoice payments recorded via `addInvoicePayment()` helper; invoice validation via `checkCbInvoiceID()` and `checkCbTransID()`

### Webhook Security

The webhook handler (`callback/coinbase.php`) validates:
1. **Signature** — HMAC-SHA256 via `x-hook0-signature` header (format: `t=timestamp,h=headers,v1=signature`)
2. **Replay protection** — Timestamp must be within 5 minutes
3. **Source check** — Metadata `source` field must equal `whmcs`
4. **Ownership** — Invoice must belong to the user ID in metadata

### Webhook Event Types

Defined in `const.php`, handled in the `Webhook::process()` switch:
- `checkout.payment.success` — Records payment via `addInvoicePayment()`
- `checkout.payment.failed` — Reverts "Payment Pending" to "Unpaid", logs failure
- `checkout.payment.expired` — Reverts "Payment Pending" to "Unpaid", logs expiry

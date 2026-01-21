# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Coinbase Commerce payment gateway module for WHMCS. Allows merchants to accept cryptocurrency payments via Coinbase Commerce API. **Note: This repository is not actively maintained.**

## Build Commands

```bash
# Install dependencies (from repo root)
composer install -n --prefer-dist

# Dependencies install to modules/gateways/Coinbase/vendor/
```

There are no test or lint commands configured for this project.

## Architecture

### Payment Flow

1. **Checkout** (`modules/gateways/coinbase.php`): `coinbase_link()` creates a Coinbase Commerce charge with invoice metadata and redirects customer to hosted payment page
2. **Webhook** (`modules/gateways/callback/coinbase.php`): Receives POST notifications from Coinbase when payment status changes, validates signature, and updates WHMCS invoice

### Key Components

- **`modules/gateways/coinbase.php`** - Main gateway module with WHMCS integration (config UI, payment link generation)
- **`modules/gateways/callback/coinbase.php`** - Webhook handler processing charge state changes (RESOLVED, COMPLETED, PENDING, UNRESOLVED, CANCELED, EXPIRED)
- **`modules/gateways/Coinbase/const.php`** - Metadata field constants and webhook signature header name

### WHMCS Conventions

- All files must check `if (!defined("WHMCS"))` for access protection
- Functions prefixed with gateway name: `coinbase_MetaData()`, `coinbase_config()`, `coinbase_link()`
- Uses Illuminate Database Capsule ORM for database access
- Invoice payments recorded via `addInvoicePayment()` helper

### Webhook Security

Webhook validation requires:
1. Signature verification via `x-cc-webhook-signature` header
2. Metadata source check (`source === 'whmcs'`)
3. Invoice ownership verification (user ID matching)

## CI/CD

CircleCI builds on master branch only:
1. Installs composer dependencies
2. Creates zip of `modules/` directory
3. Uploads to GitHub releases

Version is tracked in `.circleci/params.ini`.

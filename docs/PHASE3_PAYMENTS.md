# Phase 3 - Payments & Invoicing

This phase adds a structured billing layer to MediSphere with invoices, transactions, and refund workflows.

## Implemented in this phase
- Patient-facing **Payments & Invoices** dashboard
- Invoice generation from appointment consultation fees
- Printable invoice page for browser PDF export
- Payment history with transaction references
- Provider selection for:
  - Mock Sandbox
  - Stripe
  - PayPal
  - JazzCash
  - EasyPaisa
- Payment gateway abstraction layer (`PaymentManager` + gateway classes)
- Mock sandbox payments that complete instantly for local/demo use
- Provider integration scaffolds for real gateways
- Refund request submission by patients
- Refund management panel for admins
- Revenue/payment visibility for doctors
- Payment analytics surfaced for admins

## Database additions
Added tables:
- `invoices`
- `payments`
- `refund_requests`

Migration file:
- `database/migrations/phase3_payments.sql`

## Local setup
If you already imported the old schema, run:
- `database/migrations/phase3_payments.sql`

If this is a fresh setup, just import:
- `database/schema.sql`

## Provider configuration
Set credentials in:
- `config/config.php`

Available keys:
- Stripe public/secret keys
- PayPal client ID/secret
- JazzCash merchant credentials
- EasyPaisa merchant credentials

## Current provider behavior
- **Mock Sandbox**: fully functional for local testing
- **Stripe / PayPal / JazzCash / EasyPaisa**: architecture ready, credentials supported in config, live provider SDK/API capture still needs final provider-specific request signing/callback implementation

## Recommended next phase
- PHPMailer + Twilio notifications tied to payments and appointments
- or WebRTC live consultation

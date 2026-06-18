# Project Memory

## Product
- App name: `Safentra Tax`
- Stack: Laravel 13, MySQL/PostgreSQL support, Redis, Bootstrap/AdminLTE-style UI
- Primary domain: Pakistan FBR digital invoicing workflow

## Current FBR Status
- The app currently supports a local mock FBR API for development.
- Production rollout must **not** use the mock defaults from `.env.example`.
- `Validate with FBR` and `Submit to FBR` architecture is in place, but payload compliance against the official FBR PDF is **not yet signed off as complete**.
- `FBR_VERIFY_URL` currently points to the app’s local/public verification page pattern:
  - `/invoices/verify/{fbr_invoice_id}`

## Invoice Workflow
- Invoice statuses used:
  - `draft`
  - `validated`
  - `submitted`
  - `failed`
  - `editable`
  - `locked`
  - `cancelled`
- Submitted invoices are editable for `72 hours`, then locked.
- Lock enforcement exists in both:
  - scheduler/command path
  - backend update protection

## Important Invoice Calculation Rules
- `Per Unit Price` is treated as tax-inclusive in current calculations.
- `Value Excl. Tax` is derived from gross value minus sales tax.
- `Discount` reduces `Total Value`, not `Sales Tax` or `Value Excl. Tax`.
- `Fixed/Notified Value` is a tax-basis override only:
  - it changes tax calculation
  - it does **not** change per-unit price
- Summary totals are aggregated from invoice item stored values.

## Validation Rules Added
- Reusable helper:
  - `App\Support\PakistanTaxHelper`
- Rule classes:
  - `App\Rules\PakistanCnicRule`
  - `App\Rules\PakistanNtnRule`
- String validator extensions registered in `AppServiceProvider`:
  - `cnic`
  - `ntn`
- These are now wired into live requests:
  - `CustomerRequest`
  - `CompanyProfileRequest`
  - `InvoiceRequest`

## NTN/CNIC Behavior
- CNIC accepts:
  - `3520212345671`
  - `35202-1234567-1`
- NTN accepts:
  - 13-digit individual format
  - 8-digit company/AOP format
  - dashed 8-digit form like `4174941-3`
- Stored values are normalized before validation/persistence.

## Province Data
- Province duplication issue was caused by loose FBR sync rows like:
  - `PUNJAB`
  - `SINDH`
  - `BALOCHISTAN`
  - `ISLAMABAD`
- Canonical province rows were normalized and deduplicated.
- Company/customer/invoice forms should use the canonical province list only.

## Printable Invoice
- PDF invoice was redesigned to look closer to the provided FBR-style references.
- QR appears in the footer verification area only.
- Footer uses the extracted FBR digital invoicing logo asset:
  - `public/assets/img/fbr-digital-logo.png`
- QR verification is tied to `fbr_invoice_id`.

## Deployment Notes
- Current repo includes Docker for local/dev use only.
- User stated production deployment will be **non-Docker**.
- If no queue worker is used in production, set:
  - `QUEUE_CONNECTION=sync`
- Cron still needs to run:
  - `php artisan schedule:run`

## Migration Notes
- `hs_codes.description` should be `text` from initial table creation.
- The old expansion migration was patched to skip if `hs_codes` does not exist.
- Province dedupe migration includes SQLite-safe index detection for tests.

## Tests Added Recently
- Invoice show page render test
- Invoice PDF QR render test
- Invoice verification page test
- Pakistan tax helper/rule tests
- Customer form NTN validation integration test

## Known Caution Areas
- Do not assume FBR production readiness until payload mapping is verified field-by-field against the official guide.
- Do not deploy `.env.example` values unchanged.
- Existing stored PDFs may become stale when PDF template changes; current download path regenerates output from the current template.

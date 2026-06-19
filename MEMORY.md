# Project Memory

## Product
- App name: `Safentra Tax`
- Stack: Laravel 13, MySQL/PostgreSQL support, Redis, Bootstrap/AdminLTE-style UI
- Primary domain: Pakistan FBR digital invoicing workflow

## Current FBR Status
- The app currently supports a local mock FBR API for development.
- Sandbox invoice validation/submission is configured to use:
  - Base URL: `https://gw.fbr.gov.pk`
  - Submit endpoint: `/di_data/v1/di/postinvoicedata_sb`
  - Validate endpoint: `/di_data/v1/di/validateinvoicedata_sb`
  - Sandbox token: `N/A` / blank
- Production invoice endpoints use the same gateway host with non-sandbox paths:
  - Submit endpoint: `/di_data/v1/di/postinvoicedata`
  - Validate endpoint: `/di_data/v1/di/validateinvoicedata`
- Placeholder token values `N/A`, `NA`, `null`, and blank are treated as no bearer token.
- FBR API bearer token is read from the company profile `fbr_token` saved in the portal; `.env` `FBR_SECURITY_TOKEN` is only a fallback when the portal token is blank or placeholder.
- Company profile includes sandbox `Business Nature`; for `Distributor`, mock FBR validation should assume all distributor sectors and allow the union of their sandbox scenarios/sale types, with `.env` mock allowlists only as fallback when no profile rule applies.
- FBR invoice request payload should follow the official JSON shape with top-level seller/buyer fields and `items`; absent string fields should be sent as empty strings rather than `null`, while documented decimal fields such as `extraTax` stay numeric.
- STATL uses `GET /dist/v1/statl` with `regno` and `date`; registration type uses `GET /dist/v1/Get_Reg_Type` with `Registration_No`.
- Production rollout must **not** use the mock defaults from `.env.example`.
- `Validate with FBR` and `Submit to FBR` architecture is in place, but payload compliance against the official FBR PDF is **not yet signed off as complete**.
- `FBR_VERIFY_URL` currently points to the app’s local/public verification page pattern:
  - `/invoices/verify/{fbr_invoice_id}`

## Official FBR References
- Use this customs tariff PDF as the HS-code reference source:
  - `https://download1.fbr.gov.pk/Docs/2017112112112348253CustomsTariff2017-18Ch1-99.pdf`
- Use this DI API technical documentation for endpoint, payload, reference API, scenario, error-code, auth, and QR/logo rules:
  - `https://download1.fbr.gov.pk/Docs/20257301172130815TechnicalDocumentationforDIAPIV1.12.pdf`
- Use this DI user manual for portal/user workflow behavior:
  - `https://download1.fbr.gov.pk/Docs/20257301171649798DIUserManualV1.5.pdf`
- Use this FBR FAQ page for operational FAQ guidance:
  - `https://fbr.gov.pk/faqs/173967/173969`
- When changing payload mapping, mock validation, scenarios, HS/UOM behavior, or production-readiness checks, verify against these references first.

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

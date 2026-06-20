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
- Current FBR environment is read from the company profile `fbr_environment`, not `.env`, for UI badges and runtime invoice behavior.
- Invoices store an `environment` value; invoice lists, dashboards, mock console logs, imports, demo invoices, validation/submission jobs, and invoice route access should be scoped to the current company profile environment.
- Existing invoices introduced before environment scoping are treated/backfilled as `sandbox`.
- Company profile includes sandbox `Business Nature`; for `Distributor`, mock FBR validation assumes all distributor sectors and allows the union of their sandbox scenarios/sale types. The current Distributor union covers `SN001` through `SN028`, with `.env` mock allowlists only as fallback when no profile rule applies.
- Demo sandbox scenario fixtures exist for all Distributor scenarios `SN001` through `SN028`; registered-only scenarios use a registered buyer, while `SN002` and retailer/end-consumer scenarios `SN026`-`SN028` use an unregistered buyer.
- Real FBR sandbox attempts for the current company profile have submitted these scenarios successfully: `SN001`, `SN002`, `SN003`, `SN004`, `SN005`, `SN006`, `SN007`, `SN008`, `SN009`, `SN010`, `SN012`, `SN013`, `SN015`, `SN017`, `SN018`, `SN020`, `SN021`, `SN022`, `SN023`, `SN024`, `SN025`, `SN026`, `SN027`, `SN028`.
- Current confirmed `SN001` real sandbox submission for seller `3530176754447` uses registered buyer NTN `2046004` / `FERTILIZER MANUFAC IRS NEW`, HS code `0101.2100`, UOM `Numbers, pieces, units`, rate `18%`, sale type `Goods at standard rate (default)`. Successful FBR invoice number: `3530176754447DIM9EF1X733235`.
- Current confirmed `SN003` real sandbox submission uses buyer `3730128493065`, HS code `7214.9990`, description `Other bars and rods of iron or non-alloy steel`, UOM `MT`, rate `18%`, sale type `Steel Melting and re-rolling`, `valueSalesExcludingST` `206170`, `salesTaxApplicable` `37110.60`, and `totalValues` `243280.60`. Successful FBR invoice number: `3530176754447DIM9LZAX545875`.
- Current confirmed `SN004` real sandbox submission uses buyer `3730128493065`, HS code `7204.4910`, description `Ship breaking scrap`, UOM `MT`, rate `18%`, sale type `Ship breaking`, `valueSalesExcludingST` `206170`, `salesTaxApplicable` `37110.60`, and `totalValues` `243280.60`. Successful FBR invoice number: `3530176754447DIM9SVGF496571`.
- Current confirmed `SN005` real sandbox submission uses buyer `3730128493065`, HS code `0102.2930`, description `Cattle`, UOM `Numbers, pieces, units`, rate `10%`, sale type `Goods at Reduced Rate`, SRO `EIGHTH SCHEDULE Table 1`, item serial `1`, `extraTax` as an empty string, and `furtherTax` `0`. Successful FBR invoice number: `3530176754447DIM9ZK8Q549877`.
- Current confirmed `SN006` real sandbox submission uses registered buyer `2046004`, HS code `0102.2930`, description `Cattle`, UOM `Numbers, pieces, units`, rate `Exempt`, sale type `Exempt Goods`, SRO `EIGHTH SCHEDULE Table 1`, item serial `81`, and `extraTax` as an empty string. Successful FBR invoice number: `3530176754447DIMACRYC748925`.
- Current confirmed `SN007` real sandbox submission uses buyer `3730128493065`, HS code `0102.2930`, description `Cattle`, UOM `Numbers, pieces, units`, rate `0%`, sale type `Goods at zero-rate`, SRO `327(I)/2008`, item serial `1`, and `extraTax` as an empty string. Successful FBR invoice number: `3530176754447DIMAFT0F483498`.
- Current confirmed `SN009` real sandbox submission uses registered buyer `2046004`, HS code `5201.0090`, description `Other cotton, not carded or combed`, UOM `KG`, rate `18%`, sale type `Cotton Ginners`. Successful FBR invoice number: `3530176754447DIMAJ8KH058413`.
- Current confirmed `SN015` real sandbox submission uses buyer `3730128493065`, HS code `8517.6990`, description `Mobile phones`, UOM `Numbers, pieces, units`, rate `18%`, sale type `Mobile Phones`, SRO `NINTH SCHEDULE`, and item serial `1(A)`. Successful FBR invoice number: `3530176754447DIMASMUG816294`.
- Current confirmed `SN020` real sandbox submission uses buyer `3730128493065`, HS code `8703.8010`, description `Components for the assembly / manufacture of vehicles, in any kit form excluding those of heading 8703.8030`, UOM `Numbers, pieces, units`, rate `1%`, sale type `Electric Vehicle`, SRO `6th Schd Table III`, and item serial `20`. Successful FBR invoice number: `3530176754447DIMAYRUB090694`.
- Current confirmed `SN021` real sandbox submission uses buyer `3730128493065`, HS code `6810.1100`, description `Building blocks and bricks`, UOM `KG`, rate `Rs.2`, sale type `Cement /Concrete Block`, sales tax `2`, and total `1002`. Successful FBR invoice number: `3530176754447DIMB1X3U418616`.
- Current confirmed `SN022` real sandbox submission uses buyer `3730128493065`, HS code `2829.1910`, description `Potassium chlorate`, UOM `KG`, rate `18% along with rupees 60 per kilogram`, sale type `Potassium Chlorate`, SRO `EIGHTH SCHEDULE Table 1`, item serial `56`, sales tax `240`, and total `1240`. Successful FBR invoice number: `3530176754447DIMB69NG962617`.
- Current confirmed `SN023` real sandbox submission uses buyer `3730128493065`, HS code `2711.2100`, description `Natural gas`, UOM `KG`, rate `Rs.200`, sale type `CNG Sales`, SRO `581(1)/2024`, item serial `Region-I`, sales tax `200`, and total `1200`. Successful FBR invoice number: `3530176754447DIMBA612526775`.
- Current confirmed `SN024` real sandbox submission uses buyer `3730128493065`, HS code `0101.2100`, description `Pure-bred breeding animals`, UOM `Numbers, pieces, units`, rate `25%`, sale type `Goods as per SRO.297(|)/2023`, SRO `297(I)/2023-Table-I`, item serial `12`, sales tax `250`, and total `1250`. Successful FBR invoice number: `3530176754447DIMBCHU1711366`.
- Current confirmed `SN025` real sandbox submission uses buyer `3730128493065`, HS code `3004.9099`, description `Medicaments`, UOM `KG`, rate `0%`, sale type `Non-Adjustable Supplies`, SRO `EIGHTH SCHEDULE Table 1`, item serial `81`, and total `1000`. Successful FBR invoice number: `3530176754447DIMBEO8D188746`.
- Current confirmed `SN028` real sandbox submission uses buyer `3730128493065`, HS code `0101.2100`, description `Pure-bred breeding animals`, UOM `Numbers, pieces, units`, rate `5%`, sale type `Goods at Reduced Rate`, SRO `EIGHTH SCHEDULE Table 1`, item serial `77`, `extraTax` as an empty string, sales tax `50`, and total `1050`. Successful FBR invoice number: `3530176754447DIMBGPNJ171198`.
- Remaining portal pending scenarios after current confirmed sandbox submissions: none known from the latest portal pending list.
- Real FBR sandbox still rejects or still needs fitting payloads for scenarios not in the latest portal pending list: `SN011`, `SN014`, `SN016`, `SN019`. Main rejection groups are HS-code/sale-type mismatch, missing/invalid SRO/rate mapping, `SN011` not existing for this sandbox profile, and `SN016` sale type mismatch.
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

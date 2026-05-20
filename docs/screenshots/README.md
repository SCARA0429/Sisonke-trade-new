# Screenshot Capture Checklist

This folder is the home for every screenshot included in the Deliverable 2
submission. Each subfolder maps to one section of `docs/deliverable2.md`.
None of this folder ships to the live site - the FTP deploy workflow in
`.github/workflows/deploy.yml` excludes `docs/**`.

## Folder layout

```
docs/screenshots/
  README.md                       (this file)
  responsive/
    mobile/                       (390px viewport PNGs)
    tablet/                       (768px viewport PNGs)
    desktop/                      (1366px viewport PNGs)
  database/                       (phpMyAdmin table screenshots)
  tests/                          (one PNG per TC-XX in docs/deliverable2.md section 2.5)
  user_manual/
    buyer_register/
    buyer_join_campaign/
    buyer_confirm_delivery/
    seller_create_campaign/
    admin_verify_seller/
    admin_resolve_dispute/
```

## File naming convention

- Responsive shots: `<page_slug>__<width>.png`. Example: `buyers1__390.png`,
  `campaigns__1366.png`, `admin_dashboard__768.png`.
- Database shots: `db_<table>.png`. Example: `db_users.png`.
- Test evidence shots: `TC-<id>.png` matching the Evidence column in the
  test case table. Example: `TC-09.png`.
- User manual shots: `<step_index>_<short_label>.png` inside the matching
  flow folder. Example:
  `user_manual/buyer_join_campaign/01_open_campaign.png`.

## How to capture a clean screenshot

1. Open `http://sisonketrade.xo.je/` in Microsoft Edge or Google Chrome.
2. Open DevTools with F12 and click the **Toggle device toolbar** icon
   (or press Ctrl+Shift+M). Set the responsive width manually to
   **390 px**, **768 px**, or **1366 px** as required by the row you are
   capturing.
3. Press Ctrl+Shift+P (Cmd+Shift+P on macOS) to open the DevTools
   command palette and run **"Capture full size screenshot"** for a
   long-page capture, or **"Capture screenshot"** for the visible
   viewport only.
4. Save the PNG into the correct subfolder using the naming convention
   above.
5. (Optional) Use an image editor or Snip & Sketch to add red arrows or
   numbered callouts on the user manual screenshots, but never on
   responsive screenshots - those must be unedited proof of the layout.

## Responsive screenshots required by Deliverable 2 section 2.2

Capture each of the following at all three widths (mobile 390 px,
tablet 768 px, desktop 1366 px). That is 12 pages times 3 widths
= 36 PNGs total.

| Slug | URL on live site | Notes |
|---|---|---|
| `buyers1` | `/pages/buyers1.php` | Logged out, default language |
| `campaigns` | `/pages/campaigns.php` | Marketplace search + cards |
| `campaign_detail` | `/pages/campaign_detail.php?id=1` | Logged in as buyer so the Join form is visible |
| `payfast_checkout` | `/pages/payfast_checkout.php` | Reach by POSTing from a campaign detail page |
| `buyer_dashboard` | `/pages/dashboard.php` | Logged in as buyer with at least one order |
| `seller_dashboard` | `/seller/dashboard.php` | Logged in as seller |
| `seller_my_products` | `/seller/my_products.php` | Catalogue with at least one product |
| `seller_create_campaign` | `/seller/create_campaign.php` | Form for launching a campaign |
| `admin_dashboard` | `/admin/dashboard.php` | Logged in as admin |
| `admin_users` | `/admin/users.php` | RBAC matrix and accounts table |
| `admin_transactions` | `/admin/transactions.php` | Escrow ledger |
| `admin_disputes` | `/admin/disputes.php` | Dispute queue + resolution form |

## Database screenshots required by Deliverable 2

Open phpMyAdmin from the InfinityFree vPanel, then for each table below
open it, set "Show" to 25 rows, and capture the schema strip at the
top plus the first few rows. 10 PNGs total.

| File name | Table |
|---|---|
| `db_users.png` | `users` |
| `db_buyers.png` | `buyers` |
| `db_sellers.png` | `sellers` |
| `db_admins.png` | `admins` |
| `db_products.png` | `products` |
| `db_group_buy_campaigns.png` | `group_buy_campaigns` |
| `db_campaign_participants.png` | `campaign_participants` |
| `db_escrow_payments.png` | `escrow_payments` |
| `db_transactions.png` | `transactions` |
| `db_disputes.png` | `disputes` |

## Test evidence screenshots

For each row in `docs/deliverable2.md` section 2.5 ("2.5 Test Cases"),
capture one screenshot showing the expected outcome and save it as
`tests/TC-<id>.png`. There are 22 rows, so the target is 22 PNGs
(`TC-01.png` through `TC-22.png`). Update the test table's `Actual
result`, `Pass/Fail`, and `Evidence` columns at the same time.

## User manual flow screenshots

These are the annotated walkthroughs shown in the user manual section
of the final submission. Take 4-6 screenshots per flow, numbered in
order, with the smallest amount of red annotation needed to point at
the next click.

| Flow folder | Suggested steps |
|---|---|
| `buyer_register/` | 1 open register page, 2 fill form, 3 success login redirect, 4 first login, 5 buyer home |
| `buyer_join_campaign/` | 1 marketplace, 2 campaign detail, 3 quantity form, 4 PayFast sandbox form, 5 buyer dashboard with held escrow |
| `buyer_confirm_delivery/` | 1 buyer dashboard with held escrow, 2 click confirm, 3 confirmed badge, 4 admin transactions page showing release |
| `seller_create_campaign/` | 1 seller dashboard, 2 add product, 3 product saved, 4 create campaign form, 5 marketplace shows new campaign |
| `admin_verify_seller/` | 1 admin users page, 2 verification dropdown, 3 verified badge, 4 campaign detail with verified seller badge |
| `admin_resolve_dispute/` | 1 admin disputes queue, 2 open dispute, 3 set resolved with note, 4 dashboard queue empty |

## Sign-off checklist

- [ ] 36 responsive PNGs captured at 390, 768, and 1366 widths.
- [ ] 10 database PNGs captured from phpMyAdmin.
- [ ] 22 test evidence PNGs captured and referenced in the test table.
- [ ] At least 4 PNGs in each of the 6 user manual flows.
- [ ] All file names follow the conventions above.
- [ ] Final submission zip contains this entire `docs/screenshots/`
      folder.

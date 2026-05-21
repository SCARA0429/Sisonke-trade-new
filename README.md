# Sisonke Trade

A consumer-to-consumer (C2C) group-buying web prototype built for South
African informal traders, side-hustlers, and community buyers. Sisonke
Trade lets individuals register as buyers or sellers, list everyday
goods, join group-buy campaigns, and complete a secure PayFast
sandbox payment + escrow workflow. It includes a multilingual buyer
website, a seller portal, and an admin portal with Role-Based Access
Control (RBAC) covering user management, seller verification, escrow
transactions, and dispute resolution.

## Live demo

- **URL:** your Railway domain, e.g. `https://sisonke-trade.up.railway.app`
- **Host:** Railway (Railpack / FrankenPHP + MySQL)
- **Deploy:** Railway auto-deploy on push to `main`; GitHub Actions runs PHP lint + Docker build

### Demo logins

| Role | Email | Password |
|---|---|---|
| Admin | `admin@sisonke.test` | `Password123` |
| Seller | `seller@sisonke.test` | `Password123` |
| Buyer | `buyer@sisonke.test` | `Password123` |

## Tech stack

| Area | What was used |
|---|---|
| Backend language | PHP 8.x |
| Database | MySQL / MariaDB (PDO with prepared statements) |
| Frontend | HTML5, CSS3 (`assets/css/style.css`), vanilla JavaScript, Bootstrap 5 utility classes |
| Local development | XAMPP (Apache + PHP + MariaDB) |
| Live web server | Railway (Railpack / FrankenPHP) |
| Live database | Railway MySQL |
| Payment sandbox | PayFast sandbox (`sandbox.payfast.co.za/eng/process`) |
| Version control | Git + GitHub (this repository) |
| CI / CD | GitHub Actions lint + Docker build; Railway auto-deploy |

## Quick start (local development with XAMPP)

1. Clone this repository into your XAMPP `htdocs` folder, for example
   `C:\xampp\htdocs\sisonke-trade`.
2. Start Apache and MySQL from the XAMPP control panel.
3. Open phpMyAdmin (`http://localhost/phpmyadmin`) and run the schema:
   - Create database `sisonke_trade` (or your own name).
   - Import `setup/schema.sql`.
4. Copy `config/db.local.example.php` to `config/db.local.php` and
   fill in your local DB credentials. This file is gitignored.
5. (Optional) Run `php setup/seed_demo.php` from the project root to
   load the demo accounts and example campaigns.
6. Open <http://localhost/sisonke-trade/> in your browser.

## Repository layout

```
sisonke-trade/
  admin/        Admin portal pages (dashboard, users + RBAC, transactions, disputes)
  api/          POST endpoints (login, register, logout, join_campaign, confirm_delivery, payfast_notify)
  assets/       CSS, JS, and user-uploaded campaign images
  config/       db.php (PDO bootstrap) + db.local.example.php (template)
  docs/         Deliverable report, deployment guide, screenshots
  includes/     Shared services (auth, marketplace, payfast, header/footer, i18n)
  pages/        Public + buyer pages (home, marketplace, login, register, dashboard, payfast)
  seller/       Seller portal (dashboard, my_products, create_campaign)
  setup/        schema.sql, infinityfree.sql, seed_demo.php
  tools/        Hosting/health diagnostics
  docker/       Apache config, start script, DB init script
  .github/      GitHub Actions CI workflow
  Dockerfile    Optional local/CI image (Railway uses Railpack, not Docker)
  railway.toml  Railway Railpack deploy config
  composer.json PHP version + extensions for Railpack
```

## Documentation

- [docs/deliverable2.md](docs/deliverable2.md) - full Deliverable 2
  report: introduction, prototyping table, CRC cards, EERD, context
  diagram, DFD, use case diagram, requirements comparison, code
  samples, schema notes, and the 22-row test case table (section 2.5).
- [docs/deployment.md](docs/deployment.md) - Railway setup (MySQL + Docker web
  service), environment variables, trial/free usage tips, and smoke tests.
- [docs/screenshots/README.md](docs/screenshots/README.md) - capture
  checklist for responsive, database, test-evidence, and user-manual
  screenshots, plus the file naming convention used in the report.

## Feature coverage

| Capability | Where it lives |
|---|---|
| Register / login as buyer or seller | `pages/register.php`, `pages/login.php`, `includes/auth_service.php` |
| Browse and search the marketplace | `pages/campaigns.php` |
| Open a campaign and join it | `pages/campaign_detail.php`, `api/join_campaign.php` |
| PayFast sandbox checkout + escrow | `pages/payfast_checkout.php`, `pages/payfast_return.php`, `api/payfast_notify.php`, `includes/payfast_service.php` |
| Buyer dashboard and delivery confirmation | `pages/dashboard.php`, `api/confirm_delivery.php` |
| Seller catalogue and campaign creation | `seller/my_products.php`, `seller/create_campaign.php`, `seller/dashboard.php` |
| Admin dashboard, RBAC, user CRUD | `admin/dashboard.php`, `admin/users.php` |
| Admin transactions ledger | `admin/transactions.php` |
| Admin dispute moderation | `admin/disputes.php` |
| Multilingual UI (en, zu, xh, st, af) | `includes/i18n.php` + language switcher in header |
| Database connection | `config/db.php` (PDO + env-driven creds) |
| Schema and seed data | `setup/schema.sql`, `setup/infinityfree.sql`, `setup/seed_demo.php` |

## Submission contents

The final submission package contains:

- This source repository (zipped, excluding `config/db.local.php` and
  `assets/uploads/`).
- The PDF export of `docs/deliverable2.md`.
- A current MySQL dump exported from the live Railway MySQL instance.
- The `docs/screenshots/` folder with all captured PNGs.
- The cover sheet with student details, declaration, the live URL, and
  the demo logins above.

## Licence

Project prepared for academic Deliverable 2. PayFast sandbox usage
follows the PayFast sandbox terms. All other code in this repository
is owned by the student author(s).

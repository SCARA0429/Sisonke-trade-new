# Deployment Guide

Sisonke Trade is developed locally on XAMPP and deployed to InfinityFree.
Public URL: <http://sisonketrade.xo.je/>

## Tooling and environments

| Area | What is used |
|---|---|
| Local development | XAMPP (Apache, PHP 8, MariaDB) |
| Backend language | PHP 8.x |
| Frontend | HTML5, CSS3 (`assets/css/style.css`), vanilla JavaScript |
| Local database | MariaDB / MySQL via XAMPP |
| Live hosting platform | InfinityFree (free PHP + MySQL hosting) |
| Live database | InfinityFree MySQL, administered through phpMyAdmin |
| Domain | `sisonketrade.xo.je` (free InfinityFree subdomain) |
| Payment sandbox | PayFast sandbox (`sandbox.payfast.co.za/eng/process`) |
| Version control | Git + GitHub |
| CI/CD | GitHub Actions FTP deploy on push to `main` (`.github/workflows/deploy.yml`) |

## One-time setup on InfinityFree

1. Create an InfinityFree account and add the domain
   `sisonketrade.xo.je`.
2. In the vPanel:
   - Set the PHP version to **8.2** (or any 8.1+).
   - Create a MySQL database (record the host, database name, username,
     and password vPanel gives you).
3. Open phpMyAdmin from vPanel and import
   `setup/infinityfree.sql` into that database.
4. Using the InfinityFree File Manager, upload a single configuration
   file to `htdocs/config/db.local.php` based on
   `config/db.local.example.php`, filling in the real credentials.
   This file is gitignored and is never overwritten by the CI/CD job.

## One-time setup on GitHub

Add the following repository secrets under
**Settings → Secrets and variables → Actions → New repository secret**:

| Secret | Example value | Purpose |
|---|---|---|
| `FTP_SERVER` | `ftpupload.net` | InfinityFree FTP host (shown in vPanel under "FTP Accounts") |
| `FTP_USERNAME` | `if0_41954067` | InfinityFree FTP username |
| `FTP_PASSWORD` | *your FTP password* | InfinityFree FTP password |
| `FTP_SERVER_DIR` | `/htdocs/` | Remote directory to mirror into |

Optionally create a GitHub `production` environment (Settings →
Environments → New environment → `production`) so deploys show up on the
environment timeline with the live URL.

## Automated deploy flow

The workflow in `.github/workflows/deploy.yml` runs on every push to
`main` and can also be triggered manually from the Actions tab
(`workflow_dispatch`). It performs two jobs:

1. **PHP syntax check (`lint`).** Installs PHP 8.2 and runs `php -l`
   against every `.php` file in the repository. The deploy job is
   skipped if any file fails to parse, so a broken commit never reaches
   the live site.
2. **FTP deploy (`deploy`).** Uses
   [`SamKirkland/FTP-Deploy-Action`](https://github.com/SamKirkland/FTP-Deploy-Action)
   to mirror the repository into `htdocs/` over plain FTP on port 21.
   The action keeps a `.ftp-deploy-sync-state.json` checksum file on
   the server so subsequent runs only upload files that changed.

The workflow excludes anything that should not be on the public web
server:

- `.git*`, `.github/**`, `*.md`, `.gitignore`, log/temp/backup files
- `node_modules/**`, `vendor/**`
- `config/db.local.php` and `config/db.local.example.php` (so the
  manually-uploaded credentials are never clobbered)
- `setup/**` (raw SQL schema and seed scripts)
- `docs/**` (internal documentation)
- `tools/**` (hosting health probe and other admin scripts)
- `assets/uploads/**` (user-uploaded campaign images)

## Manual deploy fallback

If GitHub Actions is unavailable, the same deploy can be done by hand:

1. Pull `main` locally.
2. Drag the contents of `sisonke-trade/` (excluding the folders listed
   above) into `htdocs/` using the InfinityFree File Manager or any FTP
   client (FileZilla, WinSCP) pointed at `FTP_SERVER:21` with the
   InfinityFree credentials.
3. If the schema changed, import the relevant SQL through phpMyAdmin.

## Smoke test after a deploy

1. Visit <http://sisonketrade.xo.je/> — should redirect to
   `pages/buyers1.php` and render the buyer home page.
2. Visit `/pages/campaigns.php` — should list seeded campaigns from the
   live database.
3. Sign in with the demo accounts in `docs/deliverable2.md` and confirm
   the buyer, seller, and admin dashboards each load.
4. Submit the PayFast sandbox checkout for one campaign and confirm the
   reference number appears on the buyer dashboard.

## What is not yet automated

- Database migrations (currently re-import `setup/infinityfree.sql`
  manually through phpMyAdmin when the schema changes).
- TLS certificate provisioning — InfinityFree free SSL can be enabled
  from vPanel but is not part of the workflow.
- Smoke / end-to-end tests — the lint job only checks PHP syntax, it
  does not exercise the live site after deploy.

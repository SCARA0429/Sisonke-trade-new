# Deployment Guide

Railpack auto-detects this repo as PHP (root `index.php` + `composer.json`).
No Dockerfile is required on Railway.

## Tooling and environments

| Area | What is used |
|---|---|
| Local development | XAMPP (Apache, PHP 8, MariaDB) |
| Backend language | PHP 8.x |
| Frontend | HTML5, CSS3 (`assets/css/style.css`), vanilla JavaScript |
| Local database | MariaDB / MySQL via XAMPP |
| Live hosting platform | Railway (Railpack / FrankenPHP) |
| Live database | Railway MySQL template |
| TLS | Automatic HTTPS on `*.up.railway.app` |
| Payment sandbox | PayFast sandbox (`sandbox.payfast.co.za/eng/process`) |
| Version control | Git + GitHub |
| CI | GitHub Actions PHP lint + Docker build (`.github/workflows/deploy.yml`) |
| CD | Railway auto-deploy on push to `main` |

## Pricing note

Railway gives new accounts a **$5 trial credit** (about 30 days, no card
required). After that, the free plan includes **$1/month** of usage — enough
for light demos if you stop services when not needed. A small PHP + MySQL stack
typically fits the trial; monitor usage in the Railway dashboard.

## One-time setup on Railway

### 1. Create a project

1. Sign up at [railway.com](https://railway.com) with GitHub.
2. Click **New Project**.

### 2. Add MySQL

1. In the project canvas, click **+ New** (or press `Ctrl/Cmd + K`).
2. Choose **Database → MySQL**.
3. Wait until the MySQL service shows **Active**.
4. Railway exposes these variables automatically:
   `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`.

### 3. Add the web service from GitHub

1. Click **+ New → GitHub Repo**.
2. Select **`SCARA0429/Sisonke-trade-new`** (or your fork).
3. Set **Branch** to **`main`**.
4. Railway detects `index.php` and `composer.json` and builds with **Railpack**
   (FrankenPHP). Do **not** set a custom Dockerfile path in the dashboard —
   `railway.toml` uses `builder = "RAILPACK"`.

### 4. Connect MySQL to the web service

1. Open the **web service** → **Variables**.
2. Click **New Variable → Add Reference** and link the MySQL service variables,
   **or** add them manually:

   | Variable | Value |
   |---|---|
   | `MYSQLHOST` | `${{MySQL.MYSQLHOST}}` |
   | `MYSQLPORT` | `${{MySQL.MYSQLPORT}}` |
   | `MYSQLUSER` | `${{MySQL.MYSQLUSER}}` |
   | `MYSQLPASSWORD` | `${{MySQL.MYSQLPASSWORD}}` |
   | `MYSQLDATABASE` | `${{MySQL.MYSQLDATABASE}}` |

   Replace `MySQL` with your MySQL service name if you renamed it.

   `config/db.php` reads `MYSQL*` directly — no `SISONKE_DB_*` vars required
   when using references.

3. Add the public URL and PayFast settings:

   | Variable | Value |
   |---|---|
   | `SISONKE_PUBLIC_URL` | `https://${{RAILWAY_PUBLIC_DOMAIN}}` |

   For **live PayFast** (real card/EFT payments), also add:

   | Variable | Value |
   |---|---|
   | `SISONKE_PAYFAST_MODE` | `live` |
   | `SISONKE_PAYFAST_MERCHANT_ID` | your PayFast merchant ID |
   | `SISONKE_PAYFAST_MERCHANT_KEY` | your PayFast merchant key |
   | `SISONKE_PAYFAST_PASSPHRASE` | your PayFast passphrase |

   Leave `SISONKE_PAYFAST_MODE` unset (or set to `sandbox`) while testing with PayFast's sandbox credentials.

4. Deploy (or wait for the first build to finish).

### 5. Generate a public URL

1. Web service → **Settings → Networking**.
2. Click **Generate Domain** (gives you `something.up.railway.app`).
3. Redeploy if `SISONKE_PUBLIC_URL` was set before the domain existed.

### 6. Load the database schema (one time)

Open the web service **Shell** and run:

```bash
php setup/import_schema.php
```

Or, if the `mysql` client is available:

```bash
bash docker/init-db.sh
```

Confirm: visit `https://<your-domain>.up.railway.app/tools/hosting-check.php`.

## Environment variables reference

| Variable | Required | Purpose |
|---|---|---|
| `MYSQLHOST` | Yes | Injected via MySQL service reference |
| `MYSQLPORT` | Yes | Usually `3306` |
| `MYSQLUSER` | Yes | MySQL user |
| `MYSQLPASSWORD` | Yes | MySQL password |
| `MYSQLDATABASE` | Yes | Database name (`railway` by default) |
| `SISONKE_PUBLIC_URL` | Yes | `https://your-app.up.railway.app` (PayFast return/notify URLs) |
| `SISONKE_PAYFAST_MODE` | No | `live` for real payments, `sandbox` for testing (default: sandbox) |
| `SISONKE_PAYFAST_MERCHANT_ID` | Live only | From your PayFast merchant account |
| `SISONKE_PAYFAST_MERCHANT_KEY` | Live only | From your PayFast merchant account |
| `SISONKE_PAYFAST_PASSPHRASE` | Live only | PayFast security passphrase (required for live ITN verification) |
| `SISONKE_BASE_URL` | No | Leave empty at domain root |
| `SISONKE_DB_*` | No | Optional overrides; `MYSQL*` takes precedence via shared names |

Local development continues to use `config/db.local.php` (gitignored).

## Automated deploy flow

1. **GitHub Actions** lints PHP and verifies the Docker image builds.
2. **Railway** rebuilds and redeploys the web service on every push to `main`
   when the repo is connected.

## PayFast on Railway

With `SISONKE_PUBLIC_URL=https://your-app.up.railway.app`, callbacks resolve to:

- Return: `.../pages/payfast_return.php`
- Notify: `.../api/payfast_notify.php`

## Smoke test after a deploy

1. Visit `/` — redirects to `/pages/buyers1.php`.
2. Visit `/pages/campaigns.php` — lists seeded campaigns.
3. Log in with demo accounts from `docs/deliverable2.md`.
4. Run a PayFast sandbox checkout.

## Tips to stay within free/trial limits

- Delete or stop services when not demoing.
- Use one project with only MySQL + web service (no extras).
- Watch **Usage** in the Railway dashboard.

## What is not yet automated

- Database seed on first deploy (run `docker/init-db.sh` once manually).
- Persistent uploads (Railway disk optional; images may reset on redeploy without a volume).

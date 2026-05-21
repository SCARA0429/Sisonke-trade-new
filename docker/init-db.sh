#!/usr/bin/env bash
set -euo pipefail

host="${MYSQLHOST:-${SISONKE_DB_HOST:-}}"
user="${MYSQLUSER:-${SISONKE_DB_USER:-}}"
pass="${MYSQLPASSWORD:-${SISONKE_DB_PASS:-}}"
db="${MYSQLDATABASE:-${SISONKE_DB_NAME:-}}"
port="${MYSQLPORT:-${SISONKE_DB_PORT:-3306}}"

if [[ -z "$host" || -z "$user" || -z "$db" ]]; then
  echo "Database env vars not set — skip init."
  exit 0
fi

echo "Importing setup/infinityfree.sql into ${db}@${host}:${port}..."
mysql -h "$host" -P "$port" -u "$user" -p"$pass" "$db" < setup/infinityfree.sql
echo "Database init complete."

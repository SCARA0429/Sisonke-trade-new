<?php

declare(strict_types=1);

// Local XAMPP example — copy to db.local.php and adjust.
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sisonke_trade');

// Railway (production) — reference MySQL service variables in the dashboard:
//   MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE
//   SISONKE_PUBLIC_URL = https://${{RAILWAY_PUBLIC_DOMAIN}}
//
// Live PayFast (real payments):
//   SISONKE_PAYFAST_MODE = live
//   SISONKE_PAYFAST_MERCHANT_ID = your merchant id
//   SISONKE_PAYFAST_MERCHANT_KEY = your merchant key
//   SISONKE_PAYFAST_PASSPHRASE = your passphrase

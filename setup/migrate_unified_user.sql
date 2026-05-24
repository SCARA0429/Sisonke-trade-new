-- Sisonke Trade migration: unified C2C user role
-- Run this once against the live database (Railway MySQL or any existing DB
-- that was created from the original schema). Safe to re-run; the ALTER will
-- no-op if the ENUM already contains 'user'.
--
-- What it does:
--   1. Adds the value 'user' to users.role so new C2C accounts can buy and
--      sell from one login.
--   2. Leaves existing buyer/seller/admin rows untouched.
--   3. Does NOT delete or migrate any data.
--
-- Usage (Railway):
--   railway connect mysql
--   source setup/migrate_unified_user.sql;
-- or paste the ALTER statement into the Railway MySQL query console.

ALTER TABLE users
  MODIFY COLUMN role ENUM('user','buyer','seller','admin') NOT NULL;

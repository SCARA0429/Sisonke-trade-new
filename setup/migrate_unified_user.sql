ALTER TABLE users
  MODIFY COLUMN role ENUM('user','buyer','seller','admin','member') NOT NULL;

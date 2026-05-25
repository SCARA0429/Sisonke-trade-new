ALTER TABLE users
  MODIFY COLUMN role ENUM('user','buyer','seller','admin') NOT NULL;

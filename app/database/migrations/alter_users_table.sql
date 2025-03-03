ALTER TABLE users
ADD COLUMN role ENUM('admin', 'staff', 'customer') NOT NULL DEFAULT 'customer' AFTER email,
ADD COLUMN status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active' AFTER role,
ADD COLUMN last_login DATETIME NULL AFTER status;

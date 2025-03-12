-- SQLite schema for ISP Management System

-- Drop existing tables if they exist
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS billing;
DROP TABLE IF EXISTS lcp_maintenance;
DROP TABLE IF EXISTS lcp_ports;
DROP TABLE IF EXISTS lcp_devices;
DROP TABLE IF EXISTS olt_ports;
DROP TABLE IF EXISTS olt_devices;
DROP TABLE IF EXISTS plans;
DROP TABLE IF EXISTS client_subscriptions;

DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user',
    status TEXT NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);




-- OLT devices table
CREATE TABLE IF NOT EXISTS olt_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    model TEXT,
    ip_address TEXT,
    location TEXT,
    total_ports INTEGER NOT NULL DEFAULT 0,
    used_ports INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- OLT ports table
CREATE TABLE IF NOT EXISTS olt_ports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    olt_id INTEGER NOT NULL,
    port_number INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'available',
    assigned_to TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (olt_id) REFERENCES olt_devices(id) ON DELETE CASCADE
);

-- LCP devices table
CREATE TABLE IF NOT EXISTS lcp_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    model TEXT,
    location TEXT NOT NULL,
    latitude TEXT,
    longitude TEXT,
    total_ports INTEGER NOT NULL DEFAULT 8,
    parent_olt_id INTEGER,
    parent_port_id INTEGER,
    installation_date DATE,
    status TEXT NOT NULL DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_olt_id) REFERENCES olt_devices(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_port_id) REFERENCES olt_ports(id) ON DELETE SET NULL
);

-- LCP ports table
CREATE TABLE IF NOT EXISTS lcp_ports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lcp_id INTEGER NOT NULL,
    port_number INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'available',
    assigned_to TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lcp_id) REFERENCES lcp_devices(id) ON DELETE CASCADE
);



-- Billing table
CREATE TABLE IF NOT EXISTS billing (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_number TEXT NOT NULL UNIQUE,
    billing_date DATE NOT NULL,
    due_date DATE NOT NULL,
    amount REAL NOT NULL,
    total_amount REAL NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payment_number TEXT NOT NULL UNIQUE,
    billing_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    payment_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    payment_method TEXT NOT NULL,
    transaction_id TEXT,
    status TEXT NOT NULL DEFAULT 'completed',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (billing_id) REFERENCES billing(id) ON DELETE CASCADE
);

-- LCP maintenance records
CREATE TABLE IF NOT EXISTS lcp_maintenance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lcp_id INTEGER NOT NULL,
    maintenance_date DATE NOT NULL,
    description TEXT NOT NULL,
    performed_by TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'completed',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lcp_id) REFERENCES lcp_devices(id) ON DELETE CASCADE
);

-- Plans table
CREATE TABLE IF NOT EXISTS plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT,
    speed_mbps INTEGER NOT NULL,
    price REAL NOT NULL,
    setup_fee REAL DEFAULT 0,
    billing_cycle TEXT NOT NULL DEFAULT 'monthly',
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add index for faster lookups
CREATE INDEX IF NOT EXISTS idx_plans_name ON plans(name);
CREATE INDEX IF NOT EXISTS idx_plans_is_active ON plans(is_active);

-- Client subscriptions table
CREATE TABLE IF NOT EXISTS client_subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER,
    plan_name TEXT NOT NULL,
    speed_mbps INTEGER NOT NULL,
    price REAL NOT NULL,
    subscription_number TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT 'active',
    start_date DATE NOT NULL,
    billing_cycle TEXT NOT NULL DEFAULT 'monthly',
    identifier TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add indexes for faster lookups
CREATE INDEX IF NOT EXISTS idx_subscriptions_client_id ON client_subscriptions(client_id);
CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON client_subscriptions(status);
CREATE INDEX IF NOT EXISTS idx_subscriptions_number ON client_subscriptions(subscription_number);

-- Insert default admin user
INSERT INTO users (username, password, email, first_name, last_name, role, status)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'Admin', 'User', 'admin', 'active');

-- Insert sample plans
INSERT OR IGNORE INTO plans (name, description, speed_mbps, price, billing_cycle, is_active) VALUES 
('Basic', 'Basic internet plan for everyday browsing', 10, 29.99, 'monthly', 1),
('Standard', 'Standard internet plan for families', 50, 49.99, 'monthly', 1),
('Premium', 'Premium high-speed internet for gamers and streamers', 100, 79.99, 'monthly', 1),
('Business', 'Business-grade internet with priority support', 200, 129.99, 'monthly', 1);
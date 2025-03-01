-- OLT table
CREATE TABLE IF NOT EXISTS olt_devices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    pon_type ENUM('GPON', 'EPON', 'XGS-PON') NOT NULL,
    tx_power DECIMAL(4,1) NOT NULL,
    number_of_pons INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (name)
);

-- OLT ports table
CREATE TABLE IF NOT EXISTS olt_ports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    olt_device_id INT NOT NULL,
    port_no INT NOT NULL,
    status ENUM('available', 'in_use') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (olt_device_id) REFERENCES olt_devices(id) ON DELETE CASCADE,
    UNIQUE KEY unique_olt_port (olt_device_id, port_no)
);

-- Splitter types table
CREATE TABLE IF NOT EXISTS olt_splitter_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    ports INT NOT NULL,
    loss DECIMAL(4,1) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (name)
);

-- LCP table
CREATE TABLE IF NOT EXISTS olt_lcps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    mother_nap_type ENUM('OLT', 'LCP') NOT NULL,
    mother_nap_id INT NOT NULL,
    pon_port INT NOT NULL,
    splitter_type INT NOT NULL,
    total_ports INT NOT NULL,
    used_ports INT NOT NULL DEFAULT 0,
    splitter_loss DECIMAL(4,1) NOT NULL,
    meters_lcp INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (name),
    FOREIGN KEY (splitter_type) REFERENCES olt_splitter_types(id)
);

-- NAP table
CREATE TABLE IF NOT EXISTS olt_naps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    lcp_id INT NOT NULL,
    port_no INT NOT NULL,
    port_count INT NOT NULL,
    client_count INT NOT NULL DEFAULT 0,
    meters_nap INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (name),
    UNIQUE KEY unique_lcp_port (lcp_id, port_no),
    FOREIGN KEY (lcp_id) REFERENCES olt_lcps(id) ON DELETE CASCADE
);

-- Insert some default splitter types
INSERT IGNORE INTO olt_splitter_types (name, ports, loss) VALUES
('1:4', 4, 7.0),
('1:8', 8, 10.5),
('1:16', 16, 13.5),
('1:32', 32, 17.0),
('1:64', 64, 21.0);

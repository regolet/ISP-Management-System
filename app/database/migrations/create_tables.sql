-- Create plans table
CREATE TABLE IF NOT EXISTS `plans` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `bandwidth` int(11) NOT NULL COMMENT 'Bandwidth in Mbps',
    `amount` decimal(10,2) NOT NULL,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create routers table
CREATE TABLE IF NOT EXISTS `routers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `model` varchar(100) NOT NULL,
    `manufacturer` varchar(100) NOT NULL,
    `serial_number` varchar(100) UNIQUE,
    `status` enum('active','inactive','maintenance') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create ONTs (Optical Network Terminals) table
CREATE TABLE IF NOT EXISTS `onts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `model` varchar(100) NOT NULL,
    `manufacturer` varchar(100) NOT NULL,
    `serial_number` varchar(100) UNIQUE,
    `status` enum('active','inactive','maintenance') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create customers table
CREATE TABLE IF NOT EXISTS `customers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `account_number` varchar(20) NOT NULL UNIQUE,
    `first_name` varchar(100) NOT NULL,
    `last_name` varchar(100) NOT NULL,
    `email` varchar(255) NOT NULL UNIQUE,
    `phone` varchar(20) NOT NULL,
    `address` text NOT NULL,
    `installation_address` text NOT NULL,
    `plan_id` int(11),
    `router_id` int(11),
    `ont_id` int(11),
    `installation_date` date NOT NULL,
    `contract_period` int(11) NOT NULL COMMENT 'Contract period in months',
    `contract_end_date` date NOT NULL,
    `status` enum('active','suspended','terminated','pending') NOT NULL DEFAULT 'pending',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `status` (`status`),
    KEY `plan_id` (`plan_id`),
    KEY `router_id` (`router_id`),
    KEY `ont_id` (`ont_id`),
    CONSTRAINT `fk_customer_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_customer_router` FOREIGN KEY (`router_id`) REFERENCES `routers` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_customer_ont` FOREIGN KEY (`ont_id`) REFERENCES `onts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some sample data
INSERT INTO `plans` (`name`, `description`, `bandwidth`, `amount`, `status`) VALUES
('Basic', '10 Mbps Internet Plan', 10, 29.99, 'active'),
('Standard', '50 Mbps Internet Plan', 50, 49.99, 'active'),
('Premium', '100 Mbps Internet Plan', 100, 79.99, 'active'),
('Ultimate', '1 Gbps Internet Plan', 1000, 129.99, 'active');

INSERT INTO `routers` (`model`, `manufacturer`, `serial_number`, `status`) VALUES
('RT-AC68U', 'ASUS', 'AS123456789', 'active'),
('RT-AX88U', 'ASUS', 'AS987654321', 'active'),
('R7000', 'NETGEAR', 'NG123456789', 'active'),
('RAX200', 'NETGEAR', 'NG987654321', 'active');

INSERT INTO `onts` (`model`, `manufacturer`, `serial_number`, `status`) VALUES
('HG8245H', 'Huawei', 'HW123456789', 'active'),
('HG8245Q', 'Huawei', 'HW987654321', 'active'),
('I-240W', 'ZTE', 'ZT123456789', 'active'),
('F670L', 'ZTE', 'ZT987654321', 'active');

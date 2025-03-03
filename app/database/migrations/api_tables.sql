-- API Keys table
CREATE TABLE IF NOT EXISTS `api_keys` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL COMMENT 'Name/description of the API key',
    `api_key` varchar(64) NOT NULL COMMENT 'The actual API key',
    `client_id` int(11) DEFAULT NULL COMMENT 'Reference to client/customer if applicable',
    `status` enum('active','inactive','revoked') NOT NULL DEFAULT 'active',
    `requests_per_minute` int(11) NOT NULL DEFAULT 60 COMMENT 'Rate limit per minute',
    `last_used` datetime DEFAULT NULL,
    `expires_at` datetime DEFAULT NULL COMMENT 'Optional expiration date',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `api_key` (`api_key`),
    KEY `client_id` (`client_id`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Requests Log table
CREATE TABLE IF NOT EXISTS `api_requests` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `api_key` varchar(64) NOT NULL,
    `method` varchar(10) NOT NULL COMMENT 'HTTP method',
    `path` varchar(255) NOT NULL COMMENT 'Request path/endpoint',
    `ip_address` varchar(45) NOT NULL COMMENT 'IPv4 or IPv6 address',
    `user_agent` varchar(255) DEFAULT NULL,
    `request_data` json DEFAULT NULL COMMENT 'Request parameters and body',
    `response_code` int(11) DEFAULT NULL COMMENT 'HTTP response code',
    `response_time` float DEFAULT NULL COMMENT 'Response time in seconds',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `api_key` (`api_key`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Error Log table
CREATE TABLE IF NOT EXISTS `api_errors` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `api_key` varchar(64) NOT NULL,
    `request_id` bigint(20) NOT NULL COMMENT 'Reference to api_requests.id',
    `error_code` varchar(50) NOT NULL COMMENT 'Application error code',
    `error_message` text NOT NULL,
    `stack_trace` text DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `api_key` (`api_key`),
    KEY `request_id` (`request_id`),
    KEY `created_at` (`created_at`),
    CONSTRAINT `fk_api_errors_request` FOREIGN KEY (`request_id`) 
        REFERENCES `api_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default API key for testing
INSERT INTO `api_keys` 
    (`name`, `api_key`, `requests_per_minute`, `status`) 
VALUES 
    ('Default Test Key', 'test_key_' || SUBSTRING(MD5(RAND()), 1, 32), 60, 'active');

-- Add indexes for rate limiting queries
CREATE INDEX idx_api_requests_rate_limit 
    ON api_requests (api_key, created_at);

-- Add foreign key constraint for api_keys.client_id if customers table exists
ALTER TABLE `api_keys`
ADD CONSTRAINT `fk_api_keys_client` 
FOREIGN KEY (`client_id`) REFERENCES `customers` (`id`) 
ON DELETE SET NULL;

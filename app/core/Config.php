<?php
namespace App\Core;

class Config
{
    private static ?self $instance = null;
    private array $config = [];
    private string $environment;

    private function __construct()
    {
        $this->loadEnvironmentVariables();
        $this->environment = getenv('APP_ENV') ?: 'production';
        $this->loadConfigFiles();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function loadEnvironmentVariables(): void
    {
        $envFile = dirname(__DIR__, 2) . '/.env';

        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                $value = trim($value, '"\'');
                
                // Handle variable interpolation
                $value = preg_replace_callback('/\${([^}]+)}/', function($matches) {
                    if ($matches[1] === 'random_bytes(32)') {
                        return base64_encode(random_bytes(32));
                    }
                    return getenv($matches[1]) ?: '';
                }, $value);

                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }

    private function loadConfigFiles(): void
    {
        // Load base config files
        $configPath = dirname(__DIR__) . '/config';
        $configFiles = glob($configPath . '/*.php');

        foreach ($configFiles as $file) {
            $key = basename($file, '.php');
            $this->config[$key] = require $file;
        }

        // Load environment specific config
        $envConfigPath = $configPath . '/environments/' . $this->environment . '.php';
        if (file_exists($envConfigPath)) {
            $envConfig = require $envConfigPath;
            $this->config = array_replace_recursive($this->config, $envConfig);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        // First check environment variables
        $envValue = getenv(strtoupper(str_replace('.', '_', $key)));
        if ($envValue !== false) {
            return $this->castValue($envValue);
        }

        // Then check config array
        $parts = explode('.', $key);
        $config = $this->config;

        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return $default;
            }
            $config = $config[$part];
        }

        return $config;
    }

    private function castValue(string $value): mixed
    {
        // Convert string values to appropriate types
        if (strtolower($value) === 'true') {
            return true;
        }
        if (strtolower($value) === 'false') {
            return false;
        }
        if (strtolower($value) === 'null') {
            return null;
        }
        if (is_numeric($value)) {
            return $value + 0; // Convert to int or float
        }
        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $parts = explode('.', $key);
        $config = &$this->config;

        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $config[$part] = $value;
            } else {
                if (!isset($config[$part]) || !is_array($config[$part])) {
                    $config[$part] = [];
                }
                $config = &$config[$part];
            }
        }
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function all(): array
    {
        return $this->config;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function isDevelopment(): bool
    {
        return $this->environment === 'development';
    }

    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    public function isTesting(): bool
    {
        return $this->environment === 'testing';
    }

    public function __clone()
    {
        throw new \RuntimeException('Config instance cannot be cloned.');
    }

    public function __wakeup()
    {
        throw new \RuntimeException('Config instance cannot be unserialized.');
    }
}

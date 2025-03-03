<?php
namespace App\Core\Cache;

class FileCache implements CacheInterface
{
    private string $path;
    private int $defaultTtl;
    private string $prefix = '';

    public function __construct(string $path = null, int $defaultTtl = 3600)
    {
        $this->path = $path ?? dirname(__DIR__, 2) . '/storage/cache';
        $this->defaultTtl = $defaultTtl;

        // Create cache directory if it doesn't exist
        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $filename = $this->getFilename($key);

        if (!file_exists($filename)) {
            return $default;
        }

        $data = unserialize(file_get_contents($filename));

        if (!is_array($data)) {
            return $default;
        }

        if (time() > $data['expiry']) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $filename = $this->getFilename($key);
        $ttl = $ttl ?? $this->defaultTtl;

        $data = [
            'value' => $value,
            'expiry' => time() + $ttl
        ];

        return file_put_contents($filename, serialize($data)) !== false;
    }

    public function delete(string $key): bool
    {
        $filename = $this->getFilename($key);

        if (file_exists($filename)) {
            return unlink($filename);
        }

        return true;
    }

    public function clear(): bool
    {
        $files = glob($this->path . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    public function getMultiple(array $keys, mixed $default = null): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    public function deleteMultiple(array $keys): bool
    {
        $success = true;

        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }

        return $success;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    private function getFilename(string $key): string
    {
        return $this->path . '/' . md5($this->prefix . $key) . '.cache';
    }

    public function getDriver(): string
    {
        return 'file';
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }
}

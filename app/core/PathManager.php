<?php
namespace App\Core;

class PathManager 
{
    private static $instance = null;
    private $paths = [];

    private function __construct() 
    {
        $this->initializePaths();
    }

    public static function getInstance(): PathManager 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializePaths() 
    {
        // Base paths
        $this->paths['root'] = dirname(__DIR__, 2);
        $this->paths['app'] = dirname(__DIR__);
        $this->paths['public'] = $this->paths['root'] . '/public';
        $this->paths['storage'] = $this->paths['app'] . '/storage';
        
        // Configuration paths
        $this->paths['config'] = $this->paths['app'] . '/config';
        $this->paths['env_config'] = $this->paths['config'] . '/environments';
        
        // Application paths
        $this->paths['controllers'] = $this->paths['app'] . '/controllers';
        $this->paths['models'] = $this->paths['app'] . '/models';
        $this->paths['views'] = $this->paths['app'] . '/views';
        $this->paths['services'] = $this->paths['app'] . '/services';
        $this->paths['middleware'] = $this->paths['app'] . '/middleware';
        
        // Storage paths
        $this->paths['logs'] = $this->paths['storage'] . '/logs';
        $this->paths['cache'] = $this->paths['storage'] . '/cache';
        $this->paths['uploads'] = $this->paths['storage'] . '/uploads';
        $this->paths['backups'] = $this->paths['storage'] . '/backups';
        
        // Asset paths
        $this->paths['assets'] = $this->paths['public'] . '/assets';
        $this->paths['css'] = $this->paths['public'] . '/css';
        $this->paths['js'] = $this->paths['public'] . '/js';
        $this->paths['images'] = $this->paths['public'] . '/img';

        // Ensure required directories exist
        $this->ensureDirectoriesExist([
            'storage',
            'logs',
            'cache',
            'uploads',
            'backups'
        ]);
    }

    private function ensureDirectoriesExist(array $directories) 
    {
        foreach ($directories as $dir) {
            if (!isset($this->paths[$dir])) {
                continue;
            }

            $path = $this->paths[$dir];
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }

            // Create .gitkeep to track empty directories
            $gitkeep = $path . '/.gitkeep';
            if (!file_exists($gitkeep)) {
                touch($gitkeep);
            }
        }
    }

    public function get(string $key): string 
    {
        if (!isset($this->paths[$key])) {
            throw new \Exception("Path not found: $key");
        }
        return $this->paths[$key];
    }

    public function set(string $key, string $path) 
    {
        $this->paths[$key] = rtrim($path, '/\\');
    }

    public function exists(string $key): bool 
    {
        return isset($this->paths[$key]);
    }

    public function all(): array 
    {
        return $this->paths;
    }

    public function getRelativePath(string $path, string $basePath = 'root'): string 
    {
        $base = $this->get($basePath);
        return str_replace($base . '/', '', $path);
    }

    public function join(string ...$parts): string 
    {
        return implode(DIRECTORY_SEPARATOR, array_map(function($part) {
            return rtrim($part, '/\\');
        }, $parts));
    }

    public function normalize(string $path): string 
    {
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    }

    public function isWritable(string $key): bool 
    {
        if (!$this->exists($key)) {
            return false;
        }
        return is_writable($this->get($key));
    }

    public function ensureWritable(string $key) 
    {
        if (!$this->exists($key)) {
            throw new \Exception("Path not found: $key");
        }

        $path = $this->get($key);
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        if (!is_writable($path)) {
            throw new \Exception("Path is not writable: $path");
        }
    }

    public function getStoragePath(string $type, string $filename = ''): string 
    {
        $validTypes = ['logs', 'cache', 'uploads', 'backups'];
        if (!in_array($type, $validTypes)) {
            throw new \Exception("Invalid storage type: $type");
        }

        $path = $this->get($type);
        return $filename ? $this->join($path, $filename) : $path;
    }

    public function getPublicPath(string $type, string $filename = ''): string 
    {
        $validTypes = ['css', 'js', 'images', 'assets'];
        if (!in_array($type, $validTypes)) {
            throw new \Exception("Invalid public asset type: $type");
        }

        $path = $this->get($type);
        return $filename ? $this->join($path, $filename) : $path;
    }

    public function getViewPath(string $view): string 
    {
        $viewPath = str_replace('.', '/', $view) . '.php';
        return $this->join($this->get('views'), $viewPath);
    }
}

<?php
namespace App\Core;

class Application 
{
    private static $instance = null;
    private $router;
    private $request;
    private $response;
    private $db;
    private $config;

    private function __construct() 
    {
        $this->router = new Router();
        $this->request = new Request();
        $this->response = new Response();
        $this->db = Database::getInstance();
        $this->loadConfig();
    }

    /**
     * Get application instance (singleton)
     */
    public static function getInstance() 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load configuration
     */
    private function loadConfig() 
    {
        $this->config = [
            'session' => [
                'lifetime' => 1800, // 30 minutes
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true
            ],
            'database' => require APP_ROOT . '/config/database.php',
            'app' => require APP_ROOT . '/config/app.php'
        ];
    }

    /**
     * Get configuration value
     */
    public function getConfig($key, $default = null) 
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Get router instance
     */
    public function getRouter() 
    {
        return $this->router;
    }

    /**
     * Get request instance
     */
    public function getRequest() 
    {
        return $this->request;
    }

    /**
     * Get response instance
     */
    public function getResponse() 
    {
        return $this->response;
    }

    /**
     * Get database instance
     */
    public function getDB() 
    {
        return $this->db;
    }

    /**
     * Run application
     */
    public function run() 
    {
        // Get request path and method
        $path = $this->request->getPath();
        $method = $this->request->getMethod();

        // Try to handle as static file first
        if ($method === 'GET' && StaticFileHandler::handle($path)) {
            return;
        }

        // Otherwise resolve as route
        $this->router->resolve($path, $method);
    }
}

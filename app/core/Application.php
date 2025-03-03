<?php
namespace App\Core;

use App\Core\Container\Container;
use App\Core\Router;
use App\Core\Database; // Ensure the Database class is imported

class Application
{
    private static ?self $instance = null;
    private Container $container;
    private Router $router;
    private Config $config;
    private Request $request;
    private Response $response;
    private ?Database $database = null;

    private function __construct()
    {
        $this->container = new Container();
        $this->config = Config::getInstance();
        $this->router = new Router();
        $this->request = new Request();
        $this->response = new Response();
        
        $this->registerBaseBindings();
        $this->registerCoreServices();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function registerBaseBindings(): void
    {
        $this->container->singleton(Container::class, fn() => $this->container);
        $this->container->singleton(Application::class, fn() => $this);
        $this->container->singleton(Config::class, fn() => $this->config);
        $this->container->singleton(Router::class, fn() => $this->router);
        $this->container->singleton(Request::class, fn() => $this->request);
        $this->container->singleton(Response::class, fn() => $this->response);
    }

    private function registerCoreServices(): void
    {
        // Register core services
        $this->container->singleton(Database::class, function() {
            if ($this->database === null) {
                try {
                    $dbConfig = $this->config->get('database');
                    $dbConfig['environment'] = $this->config->getEnvironment();
                    $dbConfig['timezone'] = $this->config->get('app.timezone', 'UTC');
                    $this->database = new Database($dbConfig);
                } catch (\Exception $e) {
                    // Log the error but don't fail
                    error_log("Database initialization failed: " . $e->getMessage());
                    return null;
                }
            }
            return $this->database;
        });

        // Register error handler
        $this->registerErrorHandler();
    }

    private function registerErrorHandler(): void
    {
        set_error_handler(function($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getDB(): ?Database
    {
        return $this->container->make(Database::class);
    }

    public function registerMiddleware(string $middleware): void
    {
        $this->router->registerMiddleware($middleware);
    }

    public function run(): void
    {
        try {
            // Get the response from router
            $response = $this->router->resolve(
                $this->request->getPath(),
                $this->request->getMethod()
            );

            // Handle different types of responses
            if (is_string($response)) {
                // If it's a string, it's probably HTML
                $this->response->html($response);
            } elseif (is_array($response)) {
                // If it's an array, send as JSON
                $this->response->json($response);
            } elseif ($response === null) {
                // If no response, send 204 No Content
                $this->response->noContent();
            } else {
                // For any other type, convert to string
                $this->response->text((string) $response);
            }

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    private function handleException(\Exception $e): void
    {
        if ($this->config->get('app.debug', false)) {
            throw $e;
        }

        error_log($e->getMessage());
        $this->response->error('Internal Server Error', 500);
    }

    public function __clone()
    {
        throw new \RuntimeException('Application instance cannot be cloned.');
    }

    public function __wakeup()
    {
        throw new \RuntimeException('Application instance cannot be unserialized.');
    }
}

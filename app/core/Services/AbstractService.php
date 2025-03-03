<?php
namespace App\Core\Services;

use App\Core\Config;
use App\Core\Container\ContainerInterface;

abstract class AbstractService implements ServiceInterface
{
    /**
     * Service container instance
     *
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * Configuration instance
     *
     * @var Config
     */
    protected Config $config;

    /**
     * Service configuration
     *
     * @var array
     */
    protected array $serviceConfig = [];

    /**
     * Initialization status
     *
     * @var bool
     */
    protected bool $initialized = false;

    /**
     * Create a new service instance
     *
     * @param ContainerInterface $container
     * @param Config $config
     */
    public function __construct(ContainerInterface $container, Config $config)
    {
        $this->container = $container;
        $this->config = $config;
        $this->loadServiceConfig();
    }

    /**
     * Load service configuration
     *
     * @return void
     */
    protected function loadServiceConfig(): void
    {
        $serviceName = $this->getName();
        $this->serviceConfig = $this->config->get("services.{$serviceName}", []);
    }

    /**
     * Initialize the service
     *
     * @return void
     */
    public function initialize(): void
    {
        if (!$this->initialized) {
            $this->boot();
            $this->initialized = true;
        }
    }

    /**
     * Boot the service
     * Override this method in concrete services to add initialization logic
     *
     * @return void
     */
    protected function boot(): void
    {
        // Default implementation does nothing
    }

    /**
     * Check if the service is initialized
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Get service configuration
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfig(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->serviceConfig;

        foreach ($keys as $segment) {
            if (!isset($value[$segment])) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Set service configuration
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setConfig(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->serviceConfig;

        foreach ($keys as $i => $segment) {
            if ($i === count($keys) - 1) {
                $config[$segment] = $value;
                break;
            }

            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }

            $config = &$config[$segment];
        }
    }

    /**
     * Get the container instance
     *
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Get a service from the container
     *
     * @param string $id Service identifier
     * @return mixed
     */
    protected function getService(string $id)
    {
        return $this->container->make($id);
    }

    /**
     * Handle service errors
     *
     * @param \Exception $e
     * @throws \Exception
     */
    protected function handleError(\Exception $e): void
    {
        if ($this->config->isDevelopment()) {
            throw $e;
        }

        // Log error in production
        error_log(sprintf(
            '[%s] Service Error: %s in %s:%d',
            $this->getName(),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));

        // Rethrow as generic service exception
        throw new \Exception('A service error occurred', 0, $e);
    }

    /**
     * Get service dependencies
     * Override this method in concrete services to define dependencies
     *
     * @return array
     */
    protected function getDependencies(): array
    {
        return [];
    }

    /**
     * Validate service dependencies
     *
     * @throws \Exception
     */
    protected function validateDependencies(): void
    {
        foreach ($this->getDependencies() as $dependency) {
            if (!$this->container->has($dependency)) {
                throw new \Exception("Service dependency not found: {$dependency}");
            }
        }
    }
}

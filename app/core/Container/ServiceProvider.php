<?php
namespace App\Core\Container;

abstract class ServiceProvider
{
    /**
     * The container instance.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Create a new service provider instance.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Register bindings with the container.
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     * Boot any application services.
     * This method is called after all services are registered.
     *
     * @return void
     */
    public function boot(): void
    {
        // Default implementation does nothing
    }

    /**
     * Get the container instance.
     *
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Set the container instance.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }
}

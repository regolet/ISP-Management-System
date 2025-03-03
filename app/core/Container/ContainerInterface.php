<?php
namespace App\Core\Container;

interface ContainerInterface
{
    /**
     * Register a binding with the container.
     *
     * @param string $abstract The abstract type/interface
     * @param mixed $concrete The concrete implementation
     * @param bool $shared Whether the binding should be a singleton
     * @return void
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void;

    /**
     * Register a shared binding (singleton).
     *
     * @param string $abstract
     * @param mixed $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void;

    /**
     * Resolve an abstract type from the container.
     *
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     */
    public function make(string $abstract, array $parameters = []);

    /**
     * Determine if an abstract type has been bound.
     *
     * @param string $abstract
     * @return bool
     */
    public function has(string $abstract): bool;
}

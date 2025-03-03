<?php
namespace App\Core\Services;

interface ServiceInterface
{
    /**
     * Get the service name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Initialize the service
     *
     * @return void
     */
    public function initialize(): void;

    /**
     * Check if the service is initialized
     *
     * @return bool
     */
    public function isInitialized(): bool;

    /**
     * Get service configuration
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfig(string $key, $default = null);

    /**
     * Set service configuration
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setConfig(string $key, $value): void;
}

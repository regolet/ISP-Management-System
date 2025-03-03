<?php
namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use App\Core\Application;
use App\Core\Config;
use App\Core\Container\Container;

class TestCase extends BaseTestCase
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * The container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->createApplication();
    }

    /**
     * Creates the application.
     *
     * @return Application
     */
    protected function createApplication(): Application
    {
        // Force testing environment
        putenv('APP_ENV=testing');

        // Create the application
        $this->app = Application::getInstance();
        $this->container = $this->app->getContainer();

        // Register testing services
        $this->registerTestingServices();

        return $this->app;
    }

    /**
     * Register testing services.
     *
     * @return void
     */
    protected function registerTestingServices(): void
    {
        // Override services for testing if needed
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Reset application state
        $this->app = null;
        $this->container = null;

        parent::tearDown();
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object $object    Object to call method on
     * @param string $method    Method name to call
     * @param array  $parameters Array of parameters to pass into method
     * @return mixed
     */
    protected function invokeMethod(object $object, string $method, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Get protected/private property of a class.
     *
     * @param object $object Object to get property from
     * @param string $property Property name
     * @return mixed
     */
    protected function getProperty(object $object, string $property)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * Set protected/private property of a class.
     *
     * @param object $object Object to set property on
     * @param string $property Property name
     * @param mixed $value Value to set
     * @return void
     */
    protected function setProperty(object $object, string $property, $value): void
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    /**
     * Assert that a string matches a route pattern.
     *
     * @param string $pattern
     * @param string $path
     * @return void
     */
    protected function assertRouteMatches(string $pattern, string $path): void
    {
        $this->assertTrue((bool) preg_match("#^{$pattern}$#", $path));
    }
}

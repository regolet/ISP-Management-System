<?php
namespace Tests\Unit\Container;

use Tests\TestCase;
use App\Core\Container\Container;
use App\Core\Container\ContainerInterface;

// Test interfaces and classes
interface TestInterface
{
    public function getValue(): string;
}

class TestImplementation implements TestInterface
{
    public function getValue(): string
    {
        return 'test';
    }
}

class TestService
{
    private TestInterface $dependency;

    public function __construct(TestInterface $dependency)
    {
        $this->dependency = $dependency;
    }

    public function getDependency(): TestInterface
    {
        return $this->dependency;
    }
}

interface NonInstantiableInterface {}

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
    }

    public function test_container_implements_container_interface(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->container);
    }

    public function test_bind_and_make_basic_binding(): void
    {
        $this->container->bind('foo', fn() => 'bar');
        $this->assertSame('bar', $this->container->make('foo'));
    }

    public function test_singleton_binding(): void
    {
        $this->container->singleton('api', fn() => new \stdClass());

        $first = $this->container->make('api');
        $second = $this->container->make('api');

        $this->assertSame($first, $second);
    }

    public function test_bindings_are_not_shared_by_default(): void
    {
        $this->container->bind('api', fn() => new \stdClass());

        $first = $this->container->make('api');
        $second = $this->container->make('api');

        $this->assertNotSame($first, $second);
    }

    public function test_automatic_resolution(): void
    {
        $this->container->bind(TestInterface::class, TestImplementation::class);
        
        /** @var TestService */
        $resolved = $this->container->make(TestService::class);
        
        $this->assertInstanceOf(TestService::class, $resolved);
        $this->assertInstanceOf(TestImplementation::class, $resolved->getDependency());
        $this->assertEquals('test', $resolved->getDependency()->getValue());
    }

    public function test_has_checks_for_binding(): void
    {
        $this->container->bind('exists', fn() => true);

        $this->assertTrue($this->container->has('exists'));
        $this->assertFalse($this->container->has('does-not-exist'));
    }

    public function test_binding_with_parameters(): void
    {
        $this->container->bind('config', fn($container, $parameters) => $parameters['value']);

        $value = $this->container->make('config', ['value' => 'test']);
        
        $this->assertSame('test', $value);
    }

    public function test_exception_on_non_existent_binding(): void
    {
        $this->expectException(\Exception::class);
        $this->container->make('non-existent-binding');
    }

    public function test_exception_on_non_instantiable_binding(): void
    {
        $this->expectException(\Exception::class);
        
        $this->container->bind(NonInstantiableInterface::class, NonInstantiableInterface::class);
        $this->container->make(NonInstantiableInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}

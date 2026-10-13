<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core;

use App\Core\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

#[CoversClass(Container::class)]
final class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testGetReturnsObjectCreatedByFactory(): void
    {
        $this->container->set('service', fn () => new stdClass());

        $service = $this->container->get('service');

        self::assertInstanceOf(stdClass::class, $service);
    }

    public function testGetReturnsSameInstanceOnRepeatedCalls(): void
    {
        $this->container->set('service', fn () => new stdClass());

        $first = $this->container->get('service');
        $second = $this->container->get('service');

        self::assertSame($first, $second);
    }

    public function testFactoryIsCalledLazilyAndOnlyOnce(): void
    {
        $calls = 0;
        $this->container->set('service', function () use (&$calls): stdClass {
            $calls++;

            return new stdClass();
        });

        self::assertSame(0, $calls, 'set() must not call the factory');

        $this->container->get('service');
        $this->container->get('service');

        self::assertSame(1, $calls, 'get() must call the factory only once');
    }

    public function testFactoryReceivesContainer(): void
    {
        $received = null;
        $this->container->set('service', function (Container $container) use (&$received): stdClass {
            $received = $container;

            return new stdClass();
        });

        $this->container->get('service');

        self::assertSame($this->container, $received);
    }

    public function testFactoryCanResolveDependencies(): void
    {
        $this->container->set('dependency', fn () => new stdClass());
        $this->container->set('service', function (Container $container): stdClass {
            $service = new stdClass();
            $service->dependency = $container->get('dependency');

            return $service;
        });

        $service = $this->container->get('service');

        self::assertSame($this->container->get('dependency'), $service->dependency);
    }

    public function testGetThrowsForUnknownId(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('No container entry for "missing"');

        $this->container->get('missing');
    }
}

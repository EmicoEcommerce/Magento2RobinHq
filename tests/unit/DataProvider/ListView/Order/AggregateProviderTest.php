<?php

namespace Emico\RobinHqTest\DataProvider\ListView\Order;

use Codeception\Test\Unit;
use Emico\RobinHq\DataProvider\ListView\Order\ListViewProviderInterface;
use Emico\RobinHq\DataProvider\ListView\Order\AggregateProvider;
use Mockery;
use UnitTester;

class AggregateProviderTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testInnerProvidersAreCalled(): void
    {
        $orderFixture = $this->tester->createOrderFixture();

        $innerProvider = Mockery::mock(ListViewProviderInterface::class);
        $innerProvider
            ->shouldReceive('getData')
            ->once()
            ->with($orderFixture)
            ->andReturn(['foo' => 'bar']);

        $provider = new AggregateProvider(
            [$innerProvider]
        );

        $data = $provider->getData($orderFixture);

        $this->assertEquals(['foo' => 'bar'], $data);
    }
}
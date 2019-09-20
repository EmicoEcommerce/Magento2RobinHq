<?php

namespace Emico\RobinHqTest\DataProvider\DetailView;

use Codeception\Test\Unit;
use Emico\RobinHq\DataProvider\DetailView\DetailViewProviderInterface;
use Emico\RobinHq\DataProvider\DetailView\AggregateProvider;
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

        $innerProvider = Mockery::mock(DetailViewProviderInterface::class);
        $innerProvider
            ->shouldReceive('getItems')
            ->once()
            ->with($orderFixture)
            ->andReturn(['foo' => 'bar']);

        $provider = new AggregateProvider(
            [$innerProvider]
        );

        $data = $provider->getItems($orderFixture);

        $this->assertEquals(['foo' => 'bar'], $data);
    }
}
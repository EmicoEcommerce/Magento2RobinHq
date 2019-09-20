<?php

namespace Emico\RobinHqTest\DataProvider\PanelView\Customer;

use Codeception\Test\Unit;
use Emico\RobinHq\DataProvider\PanelView\Customer\AggregateProvider;
use Emico\RobinHq\DataProvider\PanelView\Customer\PanelViewProviderInterface;
use Mockery;
use UnitTester;

class AggregateProviderTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testInnerProvidersAreCalled()
    {
        $customerFixture = $this->tester->createCustomerFixture();

        $innerProvider = Mockery::mock(PanelViewProviderInterface::class);
        $innerProvider
            ->shouldReceive('getData')
            ->once()
            ->with($customerFixture)
            ->andReturn(['foo' => 'bar']);

        $provider = new AggregateProvider(
            [$innerProvider]
        );

        $data = $provider->getData($customerFixture);

        $this->assertEquals(['foo' => 'bar'], $data);
    }
}
<?php

namespace Emico\RobinHqTest\DataProvider\PanelView\Customer;

use Codeception\Test\Unit;
use Emico\RobinHq\DataProvider\PanelView\Customer\NullProvider;
use UnitTester;

class NullProviderTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testProviderReturnsEmptyData()
    {
        $provider = new NullProvider();

        $this->assertEmpty($provider->getData($this->tester->createCustomerFixture()));
    }
}
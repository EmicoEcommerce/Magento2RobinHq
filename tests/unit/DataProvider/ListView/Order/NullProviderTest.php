<?php

namespace Emico\RobinHqTest\DataProvider\ListView\Order;

use Emico\RobinHq\DataProvider\ListView\Order\BaseDataProvider;
use Emico\RobinHq\DataProvider\ListView\Order\NullProvider;
use Helper\Unit;
use UnitTester;

class NullProviderTest extends \Codeception\Test\Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testNullProviderReturnsNoData(): void
    {
        $dataProvider = new NullProvider();

        $result = $dataProvider->getData($this->tester->createOrderFixture());

        $this->assertEmpty($result);
    }
}
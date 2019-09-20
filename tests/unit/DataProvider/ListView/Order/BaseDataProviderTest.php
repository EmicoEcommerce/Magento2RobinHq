<?php

namespace Emico\RobinHqTest\DataProvider\ListView\Order;

use Emico\RobinHq\DataProvider\ListView\Order\BaseDataProvider;
use Helper\Unit;
use UnitTester;

class BaseDataProviderTest extends \Codeception\Test\Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testGetOrderData(): void
    {
        $dataProvider = new BaseDataProvider();

        $result = $dataProvider->getData($this->tester->createOrderFixture());

        $this->assertEquals(
            [
                'order_number' => Unit::ORDER_INCREMENT_ID,
                'date' => '10-01-2020',
                'status' => Unit::ORDER_STATE
            ],
            $result
        );
    }
}
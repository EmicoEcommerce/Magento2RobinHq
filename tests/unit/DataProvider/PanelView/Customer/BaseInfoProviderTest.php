<?php

namespace Emico\RobinHqTest\DataProvider\PanelView\Customer;

use Emico\RobinHq\DataProvider\PanelView\Customer\BaseInfoProvider;
use Helper\Unit;
use UnitTester;

class BaseInfoProviderTest extends \Codeception\Test\Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testCanRetrieveBasicCustomerInformation()
    {
        $dataProvider = new BaseInfoProvider();

        $data = $dataProvider->getData($this->tester->createCustomerFixture());

        $this->assertEquals(
            [
                'customerId' => Unit::CUSTOMER_ID,
                'firstname' => Unit::CUSTOMER_FIRSTNAME,
                'surname' => Unit::CUSTOMER_LASTNAME
            ],
            $data
        );
    }
}
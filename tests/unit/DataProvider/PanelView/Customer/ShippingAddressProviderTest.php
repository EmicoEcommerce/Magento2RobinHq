<?php

namespace Emico\RobinHqTest\DataProvider\PanelView\Customer;

use Emico\RobinHq\DataProvider\PanelView\Customer\ShippingAddressProvider;
use Emico\RobinHq\Service\CustomerService;
use Helper\Unit;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use UnitTester;

class ShippingAddressProviderTest extends \Codeception\Test\Unit
{
    /**
     * @var ShippingAddressProvider
     */
    protected $dataProvider;

    /**
     * @var UnitTester
     */
    protected $tester;

    public function _before()
    {
        $objectManager = new ObjectManager($this);

        $this->dataProvider = $objectManager->getObject(ShippingAddressProvider::class, [
            'customerService' => new CustomerService()
        ]);
    }

    public function testRetrieveCustomerShippingAddressData()
    {
        $data = $this->dataProvider->getData($this->tester->createCustomerFixture());

        $this->assertEquals(
            [
                'street' => implode(PHP_EOL, Unit::ADDRESS_STREET),
                'city' => Unit::ADDRESS_CITY,
                'postalCode' => Unit::ADDRESS_POSTCODE,
            ],
            $data
        );
    }

    public function testNoDataIsReturnedWhenCustomerHasNoShippingAddress()
    {
        $data = $this->dataProvider->getData(
            $this->tester->createCustomerFixture(['getAddresses' => []])
        );

        $this->assertEquals([], $data);
    }
}
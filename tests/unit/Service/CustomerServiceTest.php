<?php

namespace Emico\RobinHqTest\Service;

use Codeception\Test\Unit;
use Emico\RobinHq\Service\CustomerService;
use UnitTester;

class CustomerServiceTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var CustomerService
     */
    private $customerService;

    public function _before()
    {
        $this->customerService = new CustomerService();
    }

    public function testNoAddress()
    {
        $customer = $this->tester->createCustomerFixture(['getAddresses' => []]);

        $this->assertNull($this->customerService->getDefaultAddress($customer));
    }

    public function testBillingAddressIsLeading()
    {
        $defaultShippingAddress = $this->tester->createAddressFixture(
            [
                'isDefaultBilling' => false,
                'isDefaultShipping' => true
            ]
        );

        $defaultBillingAddress = $this->tester->createAddressFixture(
            [
                'isDefaultBilling' => true,
                'isDefaultShipping' => false
            ]
        );

        $customer = $this->tester->createCustomerFixture(
            [
                'getAddresses' => [
                    $defaultBillingAddress,
                    $defaultShippingAddress
                ]
            ]
        );

        $this->assertEquals($defaultBillingAddress, $this->customerService->getDefaultAddress($customer));
    }

    public function testShippingAddressIsReturnedWhenNoBillingAddressAvailable()
    {
        $defaultShippingAddress = $this->tester->createAddressFixture(
            [
                'isDefaultBilling' => false,
                'isDefaultShipping' => true
            ]
        );

        $customer = $this->tester->createCustomerFixture(
            [
                'getAddresses' => [
                    $defaultShippingAddress
                ]
            ]
        );

        $this->assertEquals($defaultShippingAddress, $this->customerService->getDefaultAddress($customer));
    }

    public function testFirstAddressIsReturnedWhenNoDefaultAddressesSet()
    {
        $address = $this->tester->createAddressFixture(
            [
                'isDefaultBilling' => false,
                'isDefaultShipping' => false
            ]
        );

        $customer = $this->tester->createCustomerFixture(
            [
                'getAddresses' => [
                    $address
                ]
            ]
        );

        $this->assertEquals($address, $this->customerService->getDefaultAddress($customer));
    }
}
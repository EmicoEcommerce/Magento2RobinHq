<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Api\Data\StoreInterface;
use Mockery;

class Unit extends \Codeception\Module
{
    public const CUSTOMER_EMAIL = 'foo@bar.nl';
    public const CUSTOMER_FIRSTNAME = 'John';
    public const CUSTOMER_MIDDLENAME = '';
    public const CUSTOMER_LASTNAME = 'Doe';
    public const CUSTOMER_ID = 1;

    public const ADDRESS_PHONE = '06123456789';

    public const ORDER_ENTITY_ID = 1;
    public const ORDER_BASE_CURRENCY = 'EUR';
    public const ORDER_GRAND_TOTAL = 20;
    public const ORDER_TOTAL_REFUNDED = 10;
    public const ORDER_CREATED_AT = '2020-01-10 14:05:27';
    public const ORDER_INCREMENT_ID = '12345678';
    public const ORDER_STORE_ID = 1;
    public const ORDER_STATE = Order::STATE_COMPLETE;
    public const ORDER_PAYMENT_METHOD = 'checkmo';

    public const STORE_CODE = 'my_store';

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface|Mockery\MockInterface
     */
    public function createCustomerFixture(): CustomerInterface
    {
        return Mockery::mock(CustomerInterface::class, [
            'getEmail' => self::CUSTOMER_EMAIL,
            'getFirstname' => self::CUSTOMER_FIRSTNAME,
            'getMiddlename' => self::CUSTOMER_MIDDLENAME,
            'getLastname' => self::CUSTOMER_LASTNAME,
            'getId' => self::CUSTOMER_ID,
            'getAddresses' => [$this->createAddressFixture()],
        ]);
    }

    /**
     * @return \Magento\Customer\Api\Data\AddressInterface|Mockery\MockInterface
     */
    public function createAddressFixture(): AddressInterface
    {
        return Mockery::mock(AddressInterface::class, [
            'getTelephone' => self::ADDRESS_PHONE,
            'isDefaultBilling' => true
        ]);
    }

    /**
     * @param array $expectations
     * @return \Magento\Sales\Api\Data\OrderInterface|Mockery\MockInterface
     */
    public function createOrderFixture(array $expectations = []): OrderInterface
    {
        $payment = Mockery::mock(OrderPaymentInterface::class, [
            'getMethod' => self::ORDER_PAYMENT_METHOD
        ]);

        $store = Mockery::mock(StoreInterface::class, [
            'getCode' => self::STORE_CODE
        ]);

        $expectations = array_merge(
            [
                'getEntityId' => self::ORDER_ENTITY_ID,
                'getId' => self::ORDER_ENTITY_ID,
                'getBaseCurrencyCode' => self::ORDER_BASE_CURRENCY,
                'getGrandTotal' => self::ORDER_GRAND_TOTAL,
                'getTotalRefunded' => self::ORDER_TOTAL_REFUNDED,
                'getCreatedAt' => self::ORDER_CREATED_AT,
                'getIncrementId' => self::ORDER_INCREMENT_ID,
                'getCustomerFirstname' => self::CUSTOMER_FIRSTNAME,
                'getCustomerMiddlename' => self::CUSTOMER_MIDDLENAME,
                'getCustomerLastname' => self::CUSTOMER_LASTNAME,
                'getCustomerEmail' => self::CUSTOMER_EMAIL,
                'getStoreId' => self::ORDER_STORE_ID,
                'getCustomerId' => self::CUSTOMER_ID,
                'getState' => self::ORDER_STATE,
                'getStatus' => self::ORDER_STATE,
                'getPayment' => $payment,
                'getStore' => $store
            ],
            $expectations
        );

        return Mockery::mock(OrderInterface::class, $expectations);
    }
}

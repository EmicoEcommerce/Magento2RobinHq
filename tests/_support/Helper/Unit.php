<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Module;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Data\Customer;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Store\Model\Store;
use Mockery;

class Unit extends Module
{
    public const CUSTOMER_EMAIL = 'foo@bar.nl';
    public const CUSTOMER_FIRSTNAME = 'John';
    public const CUSTOMER_MIDDLENAME = 'van';
    public const CUSTOMER_LASTNAME = 'Doe';
    public const CUSTOMER_FULLNAME = 'John van Doe';
    public const CUSTOMER_CREATED_AT = '2020-01-10 14:05:27';
    public const CUSTOMER_ID = 1;

    public const ADDRESS_PHONE = '06123456789';
    public const ADDRESS_COUNTRY_ID = 'NL';
    public const ADDRESS_STREET = ['Street line 1', 'Street line 2'];
    public const ADDRESS_POSTCODE = '6000AA';
    public const ADDRESS_CITY = 'Rotterdam';

    public const ORDER_ENTITY_ID = 1;
    public const ORDER_BASE_CURRENCY = 'EUR';
    public const ORDER_GRAND_TOTAL = 20;
    public const ORDER_SUBTOTAL_INCL_TAX = 18;
    public const ORDER_SHIPPING_INCL_TAX = 2;
    public const ORDER_TOTAL_REFUNDED = 10;
    public const ORDER_DISCOUNT_AMOUNT = 1;
    public const ORDER_TAX_AMOUNT = 1;
    public const ORDER_CREATED_AT = '2020-01-10 14:05:27';
    public const ORDER_INCREMENT_ID = '12345678';
    public const ORDER_STORE_ID = 1;
    public const ORDER_STATE = Order::STATE_COMPLETE;
    public const ORDER_PAYMENT_METHOD = 'checkmo';
    public const ORDER_PAYMENT_AMOUNT_PAID = 20;

    public const ORDERITEM_PRICE_INCL_TAX = 9;
    public const ORDERITEM_QUANTITY = 2;

    public const INVOICE_ENTITY_ID = 1;
    public const INVOICE_CREATED_AT = '2020-01-10 16:05:27';

    public const PRODUCT_SKU = 'xx';
    public const PRODUCT_NAME = 'xx';

    public const STORE_CODE = 'my_store';
    public const STORE_BASE_URL = 'http://www.mystore.com';

    /**
     * @param array $expectations
     * @param string $type
     * @return \Magento\Customer\Api\Data\CustomerInterface|Mockery\MockInterface
     */
    public function createCustomerFixture(array $expectations = [], $type = Customer::class): CustomerInterface
    {
        return Mockery::mock($type, array_merge([
            'getEmail' => self::CUSTOMER_EMAIL,
            'getFirstname' => self::CUSTOMER_FIRSTNAME,
            'getMiddlename' => self::CUSTOMER_MIDDLENAME,
            'getLastname' => self::CUSTOMER_LASTNAME,
            'getId' => self::CUSTOMER_ID,
            'getCreatedAt' => self::CUSTOMER_CREATED_AT,
            'getAddresses' => [$this->createAddressFixture()],
        ], $expectations));
    }

    /**
     * @param array $expectations
     * @param string $type
     * @return \Magento\Customer\Api\Data\AddressInterface|Mockery\MockInterface
     */
    public function createAddressFixture(array $expectations = [], $type = AddressInterface::class): AddressInterface
    {
        return Mockery::mock($type, array_merge([
            'getTelephone' => self::ADDRESS_PHONE,
            'getName' => self::CUSTOMER_FULLNAME,
            'isDefaultBilling' => true,
            'isDefaultShipping' => true,
            'getCountryId' => self::ADDRESS_COUNTRY_ID,
            'getStreet' => self::ADDRESS_STREET,
            'getPostcode' => self::ADDRESS_POSTCODE,
            'getCity' => self::ADDRESS_CITY,
        ], $expectations));
    }

    /**
     * @param array $expectations
     * @param string $type
     * @return \Magento\Sales\Api\Data\OrderInterface|Mockery\MockInterface
     */
    public function createOrderFixture(array $expectations = [], $type = Order::class): OrderInterface
    {
        $payment = Mockery::mock(OrderPaymentInterface::class, [
            'getMethod' => self::ORDER_PAYMENT_METHOD,
            'getAmountPaid' => self::ORDER_PAYMENT_AMOUNT_PAID
        ]);

        $store = Mockery::mock(StoreInterface::class, [
            'getCode' => self::STORE_CODE
        ]);

        $invoice = Mockery::mock(InvoiceInterface::class, [
            'getEntityId' => self::INVOICE_ENTITY_ID,
            'getCreatedAt' => self::INVOICE_CREATED_AT
        ]);
        $invoiceCollection = Mockery::mock(InvoiceCollection::class, [
            'getLastItem' => $invoice
        ]);

        $expectations = array_merge(
            [
                'getEntityId' => self::ORDER_ENTITY_ID,
                'getId' => self::ORDER_ENTITY_ID,
                'getBaseCurrencyCode' => self::ORDER_BASE_CURRENCY,
                'getGrandTotal' => self::ORDER_GRAND_TOTAL,
                'getTotalRefunded' => self::ORDER_TOTAL_REFUNDED,
                'getSubtotalInclTax' => self::ORDER_SUBTOTAL_INCL_TAX,
                'getShippingInclTax' => self::ORDER_SHIPPING_INCL_TAX,
                'getDiscountAmount' => self::ORDER_DISCOUNT_AMOUNT,
                'getTaxAmount' => self::ORDER_TAX_AMOUNT,
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
                'getStore' => $store,
                'getItems' => [$this->createOrderItemFixture()],
                'getShippingAddress' => $this->createAddressFixture(),
                'getInvoiceCollection' => $invoiceCollection
            ],
            $expectations
        );

        return Mockery::mock($type, $expectations);
    }

    /**
     * @param array $expectations
     * @param string $type
     * @return OrderItemInterface
     */
    public function createOrderItemFixture(array $expectations = [], $type = Item::class): OrderItemInterface
    {
        return Mockery::mock($type, array_merge([
            'getSku' => self::PRODUCT_SKU,
            'getName' => self::PRODUCT_NAME,
            'getQtyOrdered' => self::ORDERITEM_QUANTITY,
            'getPriceInclTax' => self::ORDERITEM_PRICE_INCL_TAX,
            'getRowTotalInclTax' => self::ORDERITEM_PRICE_INCL_TAX * self::ORDERITEM_QUANTITY,
            'getProduct' => $this->createProductFixture()
        ], $expectations));
    }

    /**
     * @param string $type
     * @return ProductInterface
     */
    public function createProductFixture($type = Product::class): ProductInterface
    {
        return Mockery::mock($type);
    }

    /**
     * @param array $expectations
     * @param string $type
     * @return StoreInterface
     */
    public function createStoreFixture(array $expectations = [], $type = Store::class): StoreInterface
    {
        return Mockery::mock($type, array_merge([
            'getCode' => self::STORE_CODE,
            'getBaseUrl' => self::STORE_BASE_URL,
        ], $expectations));
    }
}

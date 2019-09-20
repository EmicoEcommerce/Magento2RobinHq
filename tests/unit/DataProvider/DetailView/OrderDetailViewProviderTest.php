<?php

namespace Emico\RobinHqTest\DataProvider\DetailView;

use Emico\RobinHq\DataProvider\DetailView\OrderDetailViewProvider;
use Emico\RobinHq\DataProvider\EavAttribute\AttributeRetriever;
use Emico\RobinHq\DataProvider\EavAttribute\AttributeValue;
use Emico\RobinHq\Model\Config;
use Emico\RobinHqLib\Model\Order\DetailsView;
use Helper\Unit;
use Magento\Catalog\Model\Product;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Mockery;
use UnitTester;

class OrderDetailViewProviderTest extends \Codeception\Test\Unit
{
    /**
     * @var OrderDetailViewProvider
     */
    protected $dataProvider;

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var Config|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    protected $moduleConfig;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    protected $attributeRetriever;

    public function _before()
    {
        $objectManager = new ObjectManager($this);

        $this->moduleConfig = Mockery::mock(Config::class);
        $this->moduleConfig
            ->shouldReceive('getOrderAttributes')
            ->andReturn([])
            ->byDefault();

        $this->attributeRetriever = Mockery::mock(AttributeRetriever::class)
            ->shouldReceive('getAttributeValue')
            ->andReturnNull()
            ->byDefault()
            ->getMock();

        $this->dataProvider = $objectManager->getObject(OrderDetailViewProvider::class, [
            'moduleConfig' => $this->moduleConfig,
            'attributeRetriever' => $this->attributeRetriever
        ]);
    }

    public function testGetOrderData(): void
    {
        $items = $this->dataProvider->getItems($this->tester->createOrderFixture());

        $this->assertCount(1, $items);

        /** @var DetailsView $detailView */
        $detailView = current($items);
        $this->assertInstanceOf(DetailsView::class, $detailView);
        $this->assertEquals(DetailsView::DISPLAY_MODE_DETAILS, $detailView->getDisplayAs());
        $this->assertEquals('details', $detailView->getCaption());

        $this->assertEquals(
            [
                'orderdate' => '10-01-2020',
                'ordernumber' => Unit::ORDER_INCREMENT_ID,
                'payment method' => Unit::ORDER_PAYMENT_METHOD,
                'store' => Unit::STORE_CODE,
                'status' => Unit::ORDER_STATE,
                'invoicedate' => '10-01-2020'
            ],
            $detailView->getData()
        );
    }

    public function testCanExtractCustomAttributes()
    {
        $customAttributeCode = 'my_custom_attr';
        $customAttributeValue = 'my_custom_attr_value';

        $this->moduleConfig
            ->shouldReceive('getOrderAttributes')
            ->andReturn([$customAttributeCode]);

        $this->attributeRetriever
            ->shouldReceive('getAttributeValue')
            ->once()
            ->with(Order::ENTITY, Mockery::any(), $customAttributeCode)
            ->andReturn(new AttributeValue($customAttributeCode, $customAttributeValue));

        $items = $this->dataProvider->getItems($this->tester->createOrderFixture());

        /** @var DetailsView $detailView */
        $detailView = current($items);

        $orderData = $detailView->getData();
        $this->assertArrayHasKey($customAttributeCode, $orderData);
        $this->assertEquals($customAttributeValue, $orderData[$customAttributeCode]);
    }
}
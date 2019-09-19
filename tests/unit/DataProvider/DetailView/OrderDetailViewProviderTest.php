<?php

namespace Emico\RobinHqTest\DataProvider;

use Emico\RobinHq\DataProvider\CustomerDataProvider;
use Emico\RobinHq\DataProvider\DetailView\OrderDetailViewProvider;
use Emico\RobinHq\Mapper\CustomerFactory;
use Emico\RobinHqLib\DataProvider\DataProviderInterface;
use Emico\RobinHqLib\Model\Order\DetailsView;
use Helper\Unit;
use InvalidArgumentException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Emico\RobinHqLib\Model\Customer as RobinHqCustomerModel;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Mockery;
use Mockery\MockInterface;
use UnitTester;
use Zend\Diactoros\ServerRequest;

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

    public function _before()
    {
        $objectManager = new ObjectManager($this);
        $this->dataProvider = $objectManager->getObject(OrderDetailViewProvider::class);
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
        $data = $detailView->getData();
        $this->assertEquals('10-01-2020', $data['orderdate']);
        $this->assertEquals(Unit::ORDER_INCREMENT_ID, $data['ordernumber']);
        $this->assertEquals(Unit::ORDER_PAYMENT_METHOD, $data['payment method']);
        $this->assertEquals(Unit::ORDER_STATE, $data)
        exit('henk');
    }
}
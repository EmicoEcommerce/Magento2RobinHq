<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHqTest\Mapper;

use Emico\RobinHq\DataProvider\DetailView\DetailViewProviderInterface;
use Emico\RobinHq\DataProvider\ListView\Order\ListViewProviderInterface;
use Emico\RobinHq\Mapper\OrderFactory;
use Emico\RobinHqLib\Model\Order\DetailsView;
use Helper\Unit;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use UnitTester;

class OrderFactoryTest extends \Codeception\Test\Unit
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var OrderRepositoryInterface|Mockery\MockInterface
     */
    private $orderSearchResultMock;

    /**
     * @var DetailViewProviderInterface|Mockery\MockInterface
     */
    private $detailsViewProviderMock;

    /**
     * @var ListViewProviderInterface|Mockery\MockInterface
     */
    private $listViewProviderMock;

    /**
     * @var StoreManagerInterface|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $storeManagerMock;

    /**
     * @var UnitTester
     */
    protected $tester;

    public function _before()
    {
        $this->orderSearchResultMock = Mockery::mock(OrderSearchResultInterface::class, [
            'getItems' => [$this->tester->createOrderFixture()]
        ]);

        $orderRepositoryMock = Mockery::mock(OrderRepositoryInterface::class, [
            'getList' => $this->orderSearchResultMock
        ]);

        $searchCriteriaBuilderMock = Mockery::mock(SearchCriteriaBuilder::class);
        $searchCriteriaBuilderMock->allows([
            'setPageSize' => $searchCriteriaBuilderMock,
            'addFilter' => $searchCriteriaBuilderMock,
            'addSortOrder' => $searchCriteriaBuilderMock,
            'create' => Mockery::mock(SearchCriteria::class)
        ]);

        $sortOrderBuilderMock = Mockery::mock(SortOrderBuilder::class);
        $sortOrderBuilderMock->allows([
            'setField' => $sortOrderBuilderMock,
            'setAscendingDirection' => $sortOrderBuilderMock,
            'create' => Mockery::mock(SortOrder::class)
        ]);

        $this->objectManager = new ObjectManager($this);

        $this->listViewProviderMock = Mockery::mock(ListViewProviderInterface::class);
        $this->listViewProviderMock->allows([
            'getData' => []
        ])->byDefault();

        $this->detailsViewProviderMock = Mockery::mock(DetailViewProviderInterface::class);
        $this->detailsViewProviderMock->allows([
            'getItems' => []
        ])->byDefault();

        $this->storeManagerMock = Mockery::mock(StoreManagerInterface::class);
        $this->storeManagerMock
            ->shouldReceive('getStore')
            ->andThrow(new NoSuchEntityException(__()))
            ->byDefault();

        $this->orderFactory = $this->objectManager->getObject(
            OrderFactory::class,
            [
                'orderRepository' => $orderRepositoryMock,
                'searchCriteriaBuilder' => $searchCriteriaBuilderMock,
                'sortOrderBuilder' => $sortOrderBuilderMock,
                'listViewProvider' => $this->listViewProviderMock,
                'detailViewProvider' => $this->detailsViewProviderMock,
                'storeManager' => $this->storeManagerMock
            ]
        );
    }

    public function testMapSimpleOrderData(): void
    {
        // Setup fixtures
        $orderFixture = $this->tester->createOrderFixture();

        // Map data
        $robinOrder = $this->orderFactory->createRobinOrder($orderFixture);

        // Assert
        $this->assertEquals(Unit::ORDER_INCREMENT_ID, $robinOrder->getOrderNumber());
        $this->assertEquals(Unit::ORDER_CREATED_AT, $robinOrder->getOrderDate()->format('Y-m-d H:i:s'));
        $this->assertEquals(Unit::ORDER_GRAND_TOTAL - Unit::ORDER_TOTAL_REFUNDED, $robinOrder->getRevenue());
        $this->assertEquals(Unit::ORDER_GRAND_TOTAL, $robinOrder->getOldRevenue());
        $this->assertEquals(Unit::CUSTOMER_EMAIL, $robinOrder->getEmailAddress());
        $this->assertTrue($robinOrder->isFirstOrder());
    }

    public function testCanAddListViewItems(): void
    {
        // Setup fixtures
        $orderFixture = $this->tester->createOrderFixture();

        $this->listViewProviderMock
            ->shouldReceive('getData')
            ->once()
            ->andReturn(['foo' => 'bar']);

        // Map data
        $robinOrder = $this->orderFactory->createRobinOrder($orderFixture);

        // Assert
        $this->assertEquals(['foo' => 'bar'], $robinOrder->getListView());
    }

    public function testCanAddDetailViewItems(): void
    {
        // Setup fixtures
        $orderFixture = $this->tester->createOrderFixture();

        $detailViews = [
            new DetailsView(DetailsView::DISPLAY_MODE_DETAILS, ['foo' => 'bar']),
            new DetailsView(DetailsView::DISPLAY_MODE_ROWS, ['foo' => 'bar']),
        ];
        $this->detailsViewProviderMock
            ->shouldReceive('getItems')
            ->once()
            ->andReturn($detailViews);

        // Map data
        $robinOrder = $this->orderFactory->createRobinOrder($orderFixture);

        // Assert
        $this->assertEquals($detailViews, $robinOrder->getDetailsView());
    }

    public function testCanExtractStoreUrl()
    {
        // Setup fixtures/mocks
        $orderFixture = $this->tester->createOrderFixture();

        $this->storeManagerMock->allows(['getStore' => $this->tester->createStoreFixture()]);

        // Map data
        $robinOrder = $this->orderFactory->createRobinOrder($orderFixture);

        // Assert
        $this->assertEquals(Unit::STORE_BASE_URL, $robinOrder->getWebstoreUrl());
    }

    public function testNoWebstoreUrlIsReturnedWhenNotInstanceOfStore()
    {
        // Setup fixtures/mocks
        $orderFixture = $this->tester->createOrderFixture();

        $this->storeManagerMock->allows(['getStore' => $this->tester->createStoreFixture([], StoreInterface::class)]);

        // Map data
        $robinOrder = $this->orderFactory->createRobinOrder($orderFixture);

        // Assert
        $this->assertNull($robinOrder->getWebstoreUrl());
    }
}
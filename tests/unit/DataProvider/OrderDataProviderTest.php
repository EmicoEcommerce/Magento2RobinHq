<?php

namespace Emico\RobinHqTest\DataProvider;

use Emico\RobinHq\DataProvider\OrderDataProvider;
use Emico\RobinHq\Mapper\OrderFactory;
use Emico\RobinHqLib\DataProvider\DataProviderInterface;
use Helper\Unit;
use InvalidArgumentException;
use Emico\RobinHqLib\Model\Order as RobinHqOrderModel;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use UnitTester;
use Zend\Diactoros\ServerRequest;

class OrderDataProviderTest extends \Codeception\Test\Unit
{
    /**
     * @var DataProviderInterface
     */
    protected $dataProvider;

    /**
     * @var UnitTester
     */
    protected $tester;

    public function _before()
    {
        $searchCriteria = Mockery::mock(SearchCriteria::class);

        $searchCriteriaBuilder = Mockery::mock(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder
            ->shouldReceive('addFilter')
            ->andReturnSelf();
        $searchCriteriaBuilder
            ->shouldReceive('create')
            ->andReturn($searchCriteria);

        /** @var OrderRepositoryInterface|MockInterface $orderRepositoryMock */
        $orderRepositoryMock = Mockery::mock(OrderRepositoryInterface::class);
        $order = $this->tester->createOrderFixture();

        $orderRepositoryMock->shouldReceive('getList')
            ->with($searchCriteria)
            ->andReturn(Mockery::mock(OrderSearchResultInterface::class, ['getItems' => [$order]]));

        /** @var OrderFactory|MockInterface $orderFactoryMock */
        $orderFactoryMock = Mockery::mock(OrderFactory::class);
        $orderFactoryMock
            ->shouldReceive('createRobinOrder')
            ->with($order)
            ->andReturn(new RobinHqOrderModel(Unit::ORDER_INCREMENT_ID));

        $this->dataProvider = new OrderDataProvider(
            $orderRepositoryMock,
            $searchCriteriaBuilder,
            $orderFactoryMock
        );
    }

    public function testFetchDataReturnsOrderData(): void
    {
        $request = new ServerRequest();
        $request = $request->withQueryParams(['orderNumber' => Unit::ORDER_INCREMENT_ID]);

        $result = $this->dataProvider->fetchData($request);
        $this->assertInstanceOf(RobinHqOrderModel::class, $result);
    }

    public function testFetchDataThrowsExceptionWhenOmittingOrderNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $request = new ServerRequest();

        $this->dataProvider->fetchData($request);
    }
}
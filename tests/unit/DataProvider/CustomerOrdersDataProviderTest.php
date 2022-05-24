<?php

namespace Emico\RobinHqTest\DataProvider;

use Codeception\Test\Unit;
use Emico\RobinHq\DataProvider\CustomerOrdersDataProvider;
use Emico\RobinHq\Mapper\OrderFactory;
use Emico\RobinHqLib\DataProvider\DataProviderInterface;
use Emico\RobinHqLib\Model\Collection;
use InvalidArgumentException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Emico\RobinHqLib\Model\Order as RobinHqOrderModel;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use UnitTester;
use Laminas\Diactoros\ServerRequest;

class CustomerOrdersDataProviderTest extends Unit
{
    /**
     * @var DataProviderInterface
     */
    protected $dataProvider;

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var CustomerRepositoryInterface|MockInterface
     */
    protected $customerRepositoryMock;

    /**
     * @var OrderRepositoryInterface|MockInterface
     */
    protected $orderRepositoryMock;

    /**
     * @var SearchCriteriaBuilder|Mockery\LegacyMockInterface|MockInterface
     */
    protected $searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder|Mockery\LegacyMockInterface|MockInterface
     */
    protected $sortOrderBuilder;

    public function _before()
    {
        $this->orderRepositoryMock = Mockery::mock(OrderRepositoryInterface::class);
        $this->customerRepositoryMock = Mockery::mock(CustomerRepositoryInterface::class);

        $orderFactoryMock = Mockery::mock(
            OrderFactory::class,
            ['createRobinOrder' => Mockery::mock(RobinHqOrderModel::class)]
        );

        $this->sortOrderBuilder = Mockery::mock(SortOrderBuilder::class);
        $this->sortOrderBuilder
            ->shouldReceive('create')
            ->andReturn(Mockery::mock('sortOrder'));

        $this->searchCriteriaBuilder = Mockery::mock(SearchCriteriaBuilder::class);
        $this->searchCriteriaBuilder
            ->shouldReceive('addSortOrder')
            ->andReturnSelf();
        $this->searchCriteriaBuilder
            ->shouldReceive('create')
            ->andReturn(Mockery::mock(SearchCriteria::class));

        $this->dataProvider = new CustomerOrdersDataProvider(
            $this->orderRepositoryMock,
            $this->searchCriteriaBuilder,
            $this->sortOrderBuilder,
            $orderFactoryMock
        );
    }

    public function testFetchDataReturnsOrders(): void
    {
        $this->sortOrderBuilder
            ->shouldReceive('setField')
            ->once()
            ->with(OrderInterface::CREATED_AT)
            ->andReturnSelf();
        $this->sortOrderBuilder
            ->shouldReceive('setDescendingDirection')
            ->once()
            ->andReturnSelf();

        $this->searchCriteriaBuilder
            ->shouldReceive('addFilter')
            ->once()
            ->with(OrderInterface::CUSTOMER_EMAIL, \Helper\Unit::CUSTOMER_EMAIL)
            ->andReturnSelf();

        $this->orderRepositoryMock
            ->shouldReceive('getList')
            ->andReturn(
                Mockery::mock(
                    OrderSearchResultInterface::class,
                    ['getItems' => [
                        $this->tester->createOrderFixture(),
                        $this->tester->createOrderFixture()
                    ]]
                )
            );

        $request = new ServerRequest();
        $request = $request->withQueryParams(['email' => \Helper\Unit::CUSTOMER_EMAIL]);

        /** @var Collection $result */
        $result = $this->dataProvider->fetchData($request);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);

        $jsonResult = $result->jsonSerialize();
        $this->assertArrayHasKey('orders', $jsonResult);

        foreach ($jsonResult['orders'] as $element) {
            $this->assertInstanceOf(RobinHqOrderModel::class, $element);
        }
    }

    public function testFetchDataThrowsExceptionWhenOmittingEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $request = new ServerRequest();

        $this->dataProvider->fetchData($request);
    }
}
<?php

namespace Emico\RobinHqTest\DataProvider;

use Emico\RobinHq\DataProvider\SearchDataProvider;
use Emico\RobinHq\Mapper\CustomerFactory;
use Emico\RobinHq\Mapper\OrderFactory;
use Emico\RobinHqLib\DataProvider\DataProviderInterface;
use Emico\RobinHqLib\Model\Collection;
use Emico\RobinHqLib\Model\SearchResult;
use InvalidArgumentException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Emico\RobinHqLib\Model\Order as RobinHqOrderModel;
use Emico\RobinHqLib\Model\Customer as RobinHqCustomerModel;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerSearchResultsInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use UnitTester;
use Zend\Diactoros\ServerRequest;

class SearchDataProviderTest extends \Codeception\Test\Unit
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
     * @var array|Filter[]
     */
    protected $appliedFilters = [];

    public function _before()
    {
        $this->orderRepositoryMock = Mockery::mock(OrderRepositoryInterface::class);
        $this->customerRepositoryMock = Mockery::mock(CustomerRepositoryInterface::class);

        $orderFactoryMock = Mockery::mock(
            OrderFactory::class,
            ['createRobinOrder' => Mockery::mock(RobinHqOrderModel::class)]
        );

        $customerFactoryMock = Mockery::mock(
            CustomerFactory::class,
            ['createRobinCustomer' => Mockery::mock(RobinHqCustomerModel::class)]
        );

        $searchCriteria = Mockery::mock(SearchCriteria::class);
        $searchCriteriaBuilder = Mockery::mock(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder
            ->shouldReceive('addFilters')
            ->with(Mockery::on(function(array $filters) {
                $this->appliedFilters = array_merge($this->appliedFilters, $filters);
                return true;
            }))
            ->andReturnSelf();
        $searchCriteriaBuilder
            ->shouldReceive('setPageSize')
            ->with(10)
            ->andReturnSelf();
        $searchCriteriaBuilder
            ->shouldReceive('create')
            ->andReturn($searchCriteria);

        $this->dataProvider = new SearchDataProvider(
            $this->orderRepositoryMock,
            $this->customerRepositoryMock,
            $searchCriteriaBuilder,
            $customerFactoryMock,
            $orderFactoryMock
        );
    }

    public function testFetchDataReturnsCustomersAndOrders(): void
    {
        // Setup repository mocks
        $this->customerRepositoryMock
            ->shouldReceive('getList')
            ->andReturn(
                Mockery::mock(
                    CustomerSearchResultsInterface::class,
                    ['getItems' => [
                        $this->tester->createCustomerFixture(),
                        $this->tester->createCustomerFixture()
                    ]]
                )
            );

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

        // Retrieve results from dataprovider
        $request = new ServerRequest();
        $request = $request->withQueryParams(['searchTerm' => 'foo']);

        $results = $this->dataProvider->fetchData($request);

        // Assert rights filters are applied when searching in DB
        $this->assertFilterApplied(OrderInterface::CUSTOMER_EMAIL, 'foo%');
        $this->assertFilterApplied(OrderInterface::INCREMENT_ID, 'foo%');
        $this->assertFilterApplied('billing_telephone', 'foo%');
        $this->assertFilterApplied(CustomerInterface::EMAIL, 'foo%');

        // Assert response matches expectations
        $this->assertInstanceOf(SearchResult::class, $results);

        $jsonResult = $results->jsonSerialize();
        $this->assertArrayHasKey('customers', $jsonResult);
        $this->assertArrayHasKey('orders', $jsonResult);
        $customerCollection = $jsonResult['customers'];
        $this->assertInstanceOf(Collection::class, $customerCollection);
        $this->assertCount(2, $customerCollection);

        $orderCollection = $jsonResult['orders'];
        $this->assertInstanceOf(Collection::class, $orderCollection);
        $this->assertCount(2, $orderCollection);
    }

    public function testFetchDataThrowsExceptionWhenOmittingSearchTerm(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $request = new ServerRequest();

        $this->dataProvider->fetchData($request);
    }

    /**
     * @param string $field
     * @param string $value
     * @param string $conditionType
     */
    protected function assertFilterApplied(string $field, string $value, string $conditionType = 'like')
    {
        foreach ($this->appliedFilters as $appliedFilter) {
            if ($appliedFilter->getField() !== $field) {
                continue;
            }
            if ($appliedFilter->getValue() !== $value) {
                continue;
            }
            if ($appliedFilter->getConditionType() !== $conditionType) {
                continue;
            }
            return;
        }
        $this->fail(sprintf('Expecting filter to be applied (%s = %s)', $field, $value));
    }
}
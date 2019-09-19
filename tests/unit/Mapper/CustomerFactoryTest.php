<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHqTest\Mapper;

use Emico\RobinHq\DataProvider\PanelView\Customer\PanelViewProviderInterface;
use Emico\RobinHq\Mapper\CustomerFactory;
use Emico\RobinHq\Service\CustomerService;
use Helper\Unit;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mockery;
use UnitTester;

class CustomerFactoryTest extends \Codeception\Test\Unit
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var CustomerFactory
     */
    private $customerMapper;

    /**
     * @var OrderRepositoryInterface|Mockery\MockInterface
     */
    private $orderSearchResultMock;

    /**
     * @var PanelViewProviderInterface|Mockery\MockInterface
     */
    private $panelViewProviderMock;

    /**
     * @var UnitTester
     */
    protected $tester;

    public function _before()
    {
        $this->orderSearchResultMock = Mockery::mock(OrderSearchResultInterface::class);
        $this->orderSearchResultMock
            ->shouldReceive('getItems')
            ->andReturn([])
            ->byDefault();

        $orderRepositoryMock = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepositoryMock
            ->shouldReceive('getList')
            ->andReturn($this->orderSearchResultMock);

        $searchCriteriaBuilderMock = Mockery::mock(SearchCriteriaBuilder::class)
            ->shouldReceive('addFilter')
            ->andReturnSelf()
            ->getMock();
        $searchCriteriaBuilderMock
            ->shouldReceive('create')
            ->andReturn(Mockery::mock(SearchCriteria::class));

        $this->panelViewProviderMock = Mockery::mock(PanelViewProviderInterface::class);
        $this->panelViewProviderMock
            ->shouldReceive('getData')
            ->andReturn([])
            ->byDefault();

        $this->objectManager = new ObjectManager($this);

        $this->customerMapper = $this->objectManager->getObject(
            CustomerFactory::class,
            [
                'orderRepository' => $orderRepositoryMock,
                'searchCriteriaBuilder' => $searchCriteriaBuilderMock,
                'panelViewProvider' => $this->panelViewProviderMock,
                'customerService' => $this->objectManager->getObject(CustomerService::class)
            ]
        );
    }

    public function testMapSimpleCustomerData(): void
    {
        // Setup fixtures
        $customerFixture = $this->tester->createCustomerFixture();

        // Map data
        $robinCustomer = $this->customerMapper->createRobinCustomer($customerFixture);

        // Assert
        $this->assertEquals(Unit::CUSTOMER_EMAIL, $robinCustomer->getEmailAddress());
        $this->assertEquals(Unit::CUSTOMER_FIRSTNAME . ' ' . Unit::CUSTOMER_LASTNAME, $robinCustomer->getName());
        $this->assertEquals(Unit::ADDRESS_PHONE, $robinCustomer->getPhoneNumber());
        $this->assertEquals(0, $robinCustomer->getOrderCount());
    }

    public function testMapCustomerOrderData(): void
    {
        // Setup fixtures
        $customerFixture = $this->tester->createCustomerFixture();
        $this->orderSearchResultMock
            ->shouldReceive('getItems')
            ->andReturn([$this->tester->createOrderFixture()]);

        // Map data
        $robinCustomer = $this->customerMapper->createRobinCustomer($customerFixture);

        // Assert
        $this->assertEquals(1, $robinCustomer->getOrderCount());
        $this->assertEquals(Unit::ORDER_GRAND_TOTAL - Unit::ORDER_TOTAL_REFUNDED, $robinCustomer->getTotalRevenue());
        $this->assertEquals(Unit::ORDER_BASE_CURRENCY, $robinCustomer->getCurrency());
        $this->assertEquals(Unit::ORDER_CREATED_AT, $robinCustomer->getLastOrderDate()->format('Y-m-d H:i:s'));
    }

    public function testAddPanelViewData(): void
    {
        // Setup fixtures
        $customerFixture = $this->tester->createCustomerFixture();
        $this->panelViewProviderMock
            ->shouldReceive('getData')
            ->once()
            ->andReturn(['foo' => 'bar']);

        // Map data
        $robinCustomer = $this->customerMapper->createRobinCustomer($customerFixture);

        // Assert
        $this->assertCount(1, $robinCustomer->getPanelView());
    }
}
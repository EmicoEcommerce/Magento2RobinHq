<?php

namespace Emico\RobinHqTest\Observer;

use Emico\RobinHq\Model\Config;
use Emico\RobinHq\Observer\OrderSaveAfterObserver;
use Emico\RobinHqLib\Service\CustomerService;
use Emico\RobinHqLib\Service\OrderService;
use Helper\Unit;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use UnitTester;

class OrderSaveAfterObserverTest extends \Codeception\Test\Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var OrderService|MockInterface
     */
    protected $orderService;

    /**
     * @var CustomerService|MockInterface
     */
    protected $customerService;

    /**
     * @var Config|MockInterface
     */
    protected $moduleConfig;

    /**
     * @var OrderSaveAfterObserver
     */
    protected $observer;

    /**
     * @var CustomerRepositoryInterface|Mockery\LegacyMockInterface|MockInterface
     */
    private $customerRepository;

    /**
     * @var Mockery\LegacyMockInterface|MockInterface|LoggerInterface
     */
    private $logger;

    public function _before()
    {
        $objectManager = new ObjectManager($this);

        $this->orderService = Mockery::spy(OrderService::class);
        $this->customerService = Mockery::spy(CustomerService::class);
        $this->moduleConfig = Mockery::mock(Config::class, ['isPostApiEnabled' => true]);
        $this->logger = Mockery::spy(LoggerInterface::class);

        $this->customerRepository = Mockery::mock(CustomerRepositoryInterface::class);
        $this->customerRepository
            ->shouldReceive('getById')
            ->with(Unit::CUSTOMER_ID)
            ->andReturn($this->tester->createCustomerFixture())
            ->byDefault();

        $this->observer = $objectManager->getObject(OrderSaveAfterObserver::class, [
            'orderService' => $this->orderService,
            'customerService' => $this->customerService,
            'moduleConfig' => $this->moduleConfig,
            'customerRepository' => $this->customerRepository,
            'logger' => $this->logger
        ]);
    }

    public function testOrderIsNotPostedWhenDisabledInConfiguration(): void
    {
        $this->setPostApiDisabled();

        $this->observer->execute(new Observer(['order' => $this->tester->createOrderFixture()]));

        $this->orderService->shouldNotHaveReceived('postOrder');
        $this->customerService->shouldNotHaveReceived('postCustomer');
    }

    public function testOrderIsNotPostedWhenStateIsNotComplete(): void
    {
        $order = $this->tester->createOrderFixture(['getState' => Order::STATE_CANCELED]);
        $this->observer->execute(new Observer(['order' => $order]));

        $this->orderService->shouldNotHaveReceived('postOrder');
        $this->customerService->shouldNotHaveReceived('postCustomer');
    }

    public function testCustomerIsNotPostedWhenOrderHasNoCustomer()
    {
        $order = $this->tester->createOrderFixture(['getCustomerId' => null]);
        $this->observer->execute(new Observer(['order' => $order]));

        $this->orderService->shouldHaveReceived('postOrder');
        $this->customerService->shouldNotHaveReceived('postCustomer');
    }

    public function testOrderIsPostedToRobinHq()
    {
        $this->observer->execute(new Observer(['order' => $this->tester->createOrderFixture()]));

        $this->orderService->shouldHaveReceived('postOrder');
        $this->customerService->shouldHaveReceived('postCustomer');
    }

    public function testCriticalErrorIsLoggedWhenCustomerRetrievalFails()
    {
        $this->customerRepository
            ->shouldReceive('getById')
            ->andThrow(new LocalizedException(__()));

        $this->observer->execute(new Observer(['order' => $this->tester->createOrderFixture()]));

        $this->logger->shouldHaveReceived('critical');
        $this->customerService->shouldNotHaveReceived('postCustomer');
    }

    protected function setPostApiDisabled(): void
    {
        $this->moduleConfig
            ->shouldReceive('isPostApiEnabled')
            ->andReturnFalse();
    }
}
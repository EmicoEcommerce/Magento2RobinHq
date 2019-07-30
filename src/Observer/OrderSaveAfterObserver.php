<?php

namespace Emico\RobinHq\Observer;

use Emico\RobinHq\Mapper\CustomerFactory;
use Emico\RobinHq\Mapper\OrderFactory;
use Emico\RobinHq\Model\Config;
use Emico\RobinHqLib\Service\CustomerService;
use Emico\RobinHqLib\Service\OrderService;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

class OrderSaveAfterObserver implements ObserverInterface
{
    /**
     * @var CustomerService
     */
    private $customerService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var OrderFactory
     */
    private $orderFactory;
    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var Config
     */
    private $moduleConfig;

    /**
     * CustomerSaveAfterObserver constructor.
     * @param Config $moduleConfig
     * @param CustomerService $customerService
     * @param OrderService $orderService
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerFactory $customerFactory
     * @param OrderFactory $orderFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $moduleConfig,
        CustomerService $customerService,
        OrderService $orderService,
        CustomerRepositoryInterface $customerRepository,
        CustomerFactory $customerFactory,
        OrderFactory $orderFactory,
        LoggerInterface $logger
    ) {
        $this->customerService = $customerService;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
        $this->orderService = $orderService;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        if (!$this->moduleConfig->isPostApiEnabled()) {
            return;
        }

        /** @var Order $order */
        $order = $observer->getData('order');
        $statesToProcess = [Order::STATE_PROCESSING, Order::STATE_COMPLETE, Order::STATE_CLOSED];
        if (!$order || !\in_array($order->getState(), $statesToProcess, true)) {
            return;
        }

        $this->postOrderData($order);

        $customerId = $order->getCustomerId();
        if (!$customerId) {
            return;
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (Throwable $ex) {
            $this->logger->critical($ex->getMessage());
            return;
        }

        $this->postCustomerData($customer);
    }

    /**
     * @param CustomerInterface $customer
     * @throws \Exception
     */
    protected function postCustomerData(CustomerInterface $customer): void
    {
        $robinCustomer = $this->customerFactory->createRobinCustomer($customer);

        $this->logger->debug(sprintf('Publishing RobinHQ POST for customer lifetime'), [
            'customerId' => $customer->getId(),
        ]);

        $this->customerService->postCustomer($robinCustomer);
    }

    /**
     * @param OrderInterface $order
     * @throws \Exception
     */
    protected function postOrderData(OrderInterface $order): void
    {
        $robinOrder = $this->orderFactory->createRobinOrder($order);

        $this->logger->debug(sprintf('Publishing RobinHQ POST for order'), [
            'orderId' => $order->getId(),
        ]);

        $this->orderService->postOrder($robinOrder);
    }
}
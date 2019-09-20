<?php

namespace Emico\RobinHq\Mapper;

use DateTimeImmutable;
use Emico\RobinHq\DataProvider\PanelView\Customer\PanelViewProviderInterface;
use Emico\RobinHq\Service\CustomerService;
use Emico\RobinHqLib\Model\Customer;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */
class CustomerFactory
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var PanelViewProviderInterface
     */
    private $panelViewProvider;

    /**
     * @var CustomerService
     */
    private $customerService;

    /**
     * CustomerFactory constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param PanelViewProviderInterface $panelViewProvider
     * @param CustomerService $customerService
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PanelViewProviderInterface $panelViewProvider,
        CustomerService $customerService
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->panelViewProvider = $panelViewProvider;
        $this->customerService = $customerService;
    }

    /**
     * @param CustomerInterface $customer
     * @return Customer
     * @throws \Exception
     */
    public function createRobinCustomer(CustomerInterface $customer): Customer
    {
        $robinCustomer = new Customer($customer->getEmail());
        $robinCustomer->setCustomerSince(new DateTimeImmutable($customer->getCreatedAt()));
        $robinCustomer->setName($this->getFullName($customer));

        foreach ($this->panelViewProvider->getData($customer) as $label => $value) {
            $robinCustomer->addPanelViewItem(__($label), $value);
        }

        $address = $this->customerService->getDefaultAddress($customer);
        if ($address !== null) {
            $robinCustomer->setPhoneNumber($address->getTelephone());
        }

        $this->addOrderInformation($customer, $robinCustomer);

        return $robinCustomer;
    }

    /**
     * @param CustomerInterface $customer
     * @return string
     */
    protected function getFullName(CustomerInterface $customer): string
    {
        $fullName = $customer->getFirstname();
        if ($customer->getMiddlename()) {
            $fullName .= ' ' . $customer->getMiddlename();
        }
        $fullName .= ' ' . $customer->getLastname();
        return $fullName;
    }

    /**
     * @param CustomerInterface $customer
     * @param Customer $robinCustomer
     * @throws \Exception
     */
    protected function addOrderInformation(CustomerInterface $customer, Customer $robinCustomer): void
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OrderInterface::CUSTOMER_ID, $customer->getId())
            ->addFilter(OrderInterface::STATE, [Order::STATE_COMPLETE, Order::STATE_PROCESSING], 'in')
            ->create();

        $customerOrders = $this->orderRepository->getList($searchCriteria)->getItems();
        $orderCount = count($customerOrders);
        if ($orderCount === 0) {
            return;
        }

        $robinCustomer->setOrderCount($orderCount);

        /** @var OrderInterface $lastOrder */
        $lastOrder = end($customerOrders);
        $robinCustomer->setCurrency($lastOrder->getBaseCurrencyCode());
        $robinCustomer->setLastOrderDate(new DateTimeImmutable($lastOrder->getCreatedAt()));

        $totalSpent = 0;
        foreach ($customerOrders as $order) {
            $totalSpent += $order->getGrandTotal() - $order->getTotalRefunded();
        }

        $robinCustomer->setTotalRevenue($totalSpent);
    }
}
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
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */
class CustomerFactory
{
    /**
     * CustomerFactory constructor.
     * @param CollectionFactory $orderCollectionFactory
     * @param PanelViewProviderInterface $panelViewProvider
     * @param CustomerService $customerService
     */
    public function __construct(
        private CollectionFactory $orderCollectionFactory,
        private PanelViewProviderInterface $panelViewProvider,
        private CustomerService $customerService
    ) {
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
        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection
            ->addFieldToFilter(OrderInterface::CUSTOMER_ID, $customer->getId())
            ->addFieldToFilter(OrderInterface::STATE, ['in' => [Order::STATE_COMPLETE, Order::STATE_PROCESSING]]);

        $customerOrders = $orderCollection->getItems();
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
<?php

namespace Emico\RobinHq\Mapper;

use DateTimeImmutable;
use DateTimeZone;
use Emico\RobinHq\DataProvider\PanelView\Customer\PanelViewProviderInterface;
use Emico\RobinHq\Service\CustomerService;
use Emico\RobinHqLib\Model\Customer;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;

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
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        private CollectionFactory $orderCollectionFactory,
        private PanelViewProviderInterface $panelViewProvider,
        private CustomerService $customerService,
        private TimezoneInterface $timezone
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
        $robinCustomer->setCustomerSince($this->toStoreTimezone($customer->getCreatedAt(), $customer->getStoreId()));
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
        $robinCustomer->setLastOrderDate($this->toStoreTimezone($lastOrder->getCreatedAt(), $lastOrder->getStoreId()));

        $totalSpent = 0;
        foreach ($customerOrders as $order) {
            $totalSpent += $order->getGrandTotal() - $order->getTotalRefunded();
        }

        $robinCustomer->setTotalRevenue($totalSpent);
    }

    /**
     * @param string|null $utcDate
     * @param int|string|null $storeId
     * @return DateTimeImmutable
     */
    private function toStoreTimezone(?string $utcDate, int|string|null $storeId): DateTimeImmutable
    {
        $timezone = new DateTimeZone($this->timezone->getConfigTimezone(ScopeInterface::SCOPE_STORE, $storeId));

        return (new DateTimeImmutable($utcDate, new DateTimeZone('UTC')))->setTimezone($timezone);
    }
}

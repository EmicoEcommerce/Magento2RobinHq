<?php

namespace Emico\RobinHq\Mapper;

use Emico\RobinHq\DataProvider\DetailView\DetailViewProviderInterface;
use Emico\RobinHq\DataProvider\ListView\Order\ListViewProviderInterface;
use Emico\RobinHqLib\Model\Order;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

class OrderFactory
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
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var DetailViewProviderInterface
     */
    private $detailViewProvider;
    /**
     * @var ListViewProviderInterface
     */
    private $listViewProvider;

    /**
     * OrderFactory constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param DetailViewProviderInterface $detailViewProvider
     * @param ListViewProviderInterface $listViewProvider
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        DetailViewProviderInterface $detailViewProvider,
        ListViewProviderInterface $listViewProvider
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->detailViewProvider = $detailViewProvider;
        $this->listViewProvider = $listViewProvider;
    }

    /**
     * @param OrderInterface $order
     * @return Order
     * @throws \Exception
     */
    public function createRobinOrder(OrderInterface $order): Order
    {
        $robinOrder = new Order($order->getIncrementId());
        $robinOrder->setOrderDate(new \DateTimeImmutable($order->getCreatedAt()));
        $robinOrder->setRevenue($order->getGrandTotal() - $order->getTotalRefunded());
        $robinOrder->setOldRevenue($order->getGrandTotal());
        $robinOrder->setName($this->getCustomerFullName($order));
        $robinOrder->setEmailAddress($order->getCustomerEmail());
        $robinOrder->setFirstOrder($this->isFirstOrder($order));

        foreach ($this->detailViewProvider->getItems($order) as $item) {
            $robinOrder->addDetailsView($item);
        }

        foreach ($this->listViewProvider->getData($order) as $key => $value) {
            $robinOrder->addListViewItem($key, $value);
        }

        return $robinOrder;
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    protected function getCustomerFullName(OrderInterface $order): string
    {
        $fullName = $order->getCustomerFirstname();
        if ($order->getCustomerMiddlename()) {
            $fullName .= ' ' . $order->getCustomerMiddlename();
        }
        $fullName .= ' ' . $order->getCustomerLastname();
        return $fullName;
    }

    /**
     * @param OrderInterface $order
     * @return bool
     */
    protected function isFirstOrder(OrderInterface $order): bool
    {
        $customerId = $order->getCustomerId();

        $sortOrder = $this->sortOrderBuilder
            ->setField(OrderInterface::ENTITY_ID)
            ->setAscendingDirection()
            ->create();
        
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OrderInterface::CUSTOMER_ID, $customerId)
            ->addSortOrder($sortOrder)
            ->setPageSize(1)
            ->create();

        $customerOrders = $this->orderRepository->getList($searchCriteria)->getItems();
        /** @var OrderInterface $firstOrder */
        $firstOrder = current($customerOrders);

        return $firstOrder->getEntityId() === $order->getEntityId();
    }
}
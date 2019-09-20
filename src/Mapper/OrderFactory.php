<?php

namespace Emico\RobinHq\Mapper;

use Emico\RobinHq\DataProvider\DetailView\DetailViewProviderInterface;
use Emico\RobinHq\DataProvider\ListView\Order\ListViewProviderInterface;
use Emico\RobinHqLib\Model\Order;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Keep track of first order entityID's per customer for performance
     * @var array|OrderInterface[]
     */
    private $firstOrders = [];

    /**
     * OrderFactory constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param DetailViewProviderInterface $detailViewProvider
     * @param ListViewProviderInterface $listViewProvider
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        DetailViewProviderInterface $detailViewProvider,
        ListViewProviderInterface $listViewProvider,
        StoreManagerInterface $storeManager
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->detailViewProvider = $detailViewProvider;
        $this->listViewProvider = $listViewProvider;
        $this->storeManager = $storeManager;
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
        $storeUrl = $this->getStoreUrl($order);
        if ($storeUrl) {
            $robinOrder->setWebstoreUrl($storeUrl);
        }

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
        $customerEmail = $order->getCustomerEmail();

        $firstOrder = $this->firstOrders[$customerEmail] ?? null;
        if (!$firstOrder) {
            $sortOrder = $this->sortOrderBuilder
                ->setField(OrderInterface::ENTITY_ID)
                ->setAscendingDirection()
                ->create();

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(OrderInterface::CUSTOMER_EMAIL, $customerEmail)
                ->addSortOrder($sortOrder)
                ->setPageSize(1)
                ->create();

            $customerOrders = $this->orderRepository->getList($searchCriteria)->getItems();
            /** @var OrderInterface $firstOrder */
            $firstOrder = current($customerOrders);
            $this->firstOrders[$customerEmail] = $firstOrder;
        }

        return $firstOrder->getEntityId() === $order->getEntityId();
    }

    /**
     * @param OrderInterface $order
     * @return string|null
     */
    private function getStoreUrl(OrderInterface $order): ?string
    {
        try {
            $store = $this->storeManager->getStore($order->getStoreId());
        } catch (NoSuchEntityException $e) {
            return null;
        }

        if (!$store instanceof Store) {
            return null;
        }
        return $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);
    }
}
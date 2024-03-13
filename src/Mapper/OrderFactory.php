<?php

namespace Emico\RobinHq\Mapper;

use Emico\RobinHq\DataProvider\DetailView\DetailViewProviderInterface;
use Emico\RobinHq\DataProvider\ListView\Order\ListViewProviderInterface;
use Emico\RobinHqLib\Model\Order;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

class OrderFactory
{
    /**
     * Keep track of first order entityID's per customer for performance
     * @var array|OrderInterface[]
     */
    private array $firstOrders = [];

    /**
     * OrderFactory constructor.
     * @param DetailViewProviderInterface $detailViewProvider
     * @param ListViewProviderInterface   $listViewProvider
     * @param StoreManagerInterface       $storeManager
     * @param BackendUrlInterface         $backendUrl
     * @param CollectionFactory           $orderCollectionFactory
     */
    public function __construct(
        private DetailViewProviderInterface $detailViewProvider,
        private ListViewProviderInterface $listViewProvider,
        private StoreManagerInterface $storeManager,
        private BackendUrlInterface $backendUrl,
        private CollectionFactory $orderCollectionFactory
    ) {
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
        $robinOrder->setUrl($this->getOrderBackendUrl($order));

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

            $orderCollection = $this->orderCollectionFactory->create();
            $orderCollection
                ->addFieldToFilter(OrderInterface::CUSTOMER_EMAIL, $customerEmail)
                ->addAttributeToSort(OrderInterface::ENTITY_ID);

            /** @var OrderInterface $firstOrder */
            $firstOrder = $orderCollection->getFirstItem();

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

    /**
     * @param OrderInterface $order
     * @return string
     */
    private function getOrderBackendUrl(OrderInterface $order): string
    {
        $orderUrl = $this->backendUrl->getUrl('sales/order/view', ['order_id' => $order->getEntityId()]);
        return preg_replace('/(.*\/)key\/.*/', '$1', $orderUrl);
    }
}

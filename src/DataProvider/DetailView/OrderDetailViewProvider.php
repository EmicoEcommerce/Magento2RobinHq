<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\DetailView;


use DateTimeImmutable;
use Emico\RobinHq\DataProvider\EavAttribute\AttributeRetriever;
use Emico\RobinHq\Model\Config;
use Emico\RobinHqLib\Model\Order\DetailsView;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

class OrderDetailViewProvider implements DetailViewProviderInterface
{
    /**
     * @var AttributeRetriever
     */
    private $attributeRetriever;
    /**
     * @var Config
     */
    private $moduleConfig;

    /**
     * OrderDetailViewProvider constructor.
     * @param Config $moduleConfig
     * @param AttributeRetriever $attributeRetriever
     */
    public function __construct(Config $moduleConfig, AttributeRetriever $attributeRetriever)
    {
        $this->attributeRetriever = $attributeRetriever;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * @param OrderInterface $order
     * @return array
     * @throws \Exception
     */
    public function getItems(OrderInterface $order): array
    {
        /** @var Order $order */
        $data = [
            __('ordernumber')->render() => $order->getIncrementId(),
            __('store')->render() => $order->getStore()->getCode(),
            __('orderdate')->render() => (new DateTimeImmutable($order->getCreatedAt()))->format('d-m-Y'),
            __('status')->render() => $order->getStatus(),
            __('payment method')->render() => $order->getPayment() ? $order->getPayment()->getMethod() : 'Unknown'
        ];

        if ($order instanceof Order) {
            $data = array_merge($data, $this->getCustomOrderAttributes($order));

            /** @var InvoiceInterface $lastInvoice */
            $lastInvoice = $order->getInvoiceCollection()->getLastItem();
            if ($lastInvoice->getEntityId()) {
                $data['invoicedate'] = (new DateTimeImmutable($lastInvoice->getCreatedAt()))->format('d-m-Y');
            }
        }

        $detailView = new DetailsView(DetailsView::DISPLAY_MODE_DETAILS, $data);
        $detailView->setCaption(__('details'));
        return [$detailView];
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    protected function getCustomOrderAttributes(OrderInterface $order): array
    {
        $attributeCodes = $this->moduleConfig->getOrderAttributes();

        $result = [];
        foreach ($attributeCodes as $code) {
            $attributeValue = $this->attributeRetriever->getAttributeValue(Order::ENTITY, $order, $code);
            if ($attributeValue) {
                $result[$attributeValue->getLabel()] = $attributeValue->getValue();
            }
        }
        return $result;
    }
}
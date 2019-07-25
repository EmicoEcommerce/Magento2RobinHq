<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\DetailView;


use Emico\RobinHqLib\Model\Order\DetailsView;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\Data\OrderInterface;

class TotalDetailViewProvider implements DetailViewProviderInterface
{
    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * TotalDetailViewProvider constructor.
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(PriceCurrencyInterface $priceCurrency)
    {
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    public function getItems(OrderInterface $order): array
    {
        $detailViewData = [
            'subtotal_(incl_VAT)' => $order->getSubtotalInclTax(),
            'shippingcost' => $order->getShippingInclTax(),
            'discounts_(incl_VAT)' => $order->getDiscountAmount(),
            'VAT' => $order->getTaxAmount(),
            'total_(incl_VAT)' => $order->getGrandTotal(),
            'payed' => $order->getPayment() ? ($order->getPayment()->getAmountPaid() ?? 0) : 0,
            'refunded' => $order->getTotalRefunded() ?? 0,
            'revenue' => $order->getGrandTotal() - $order->getTotalRefunded()
        ];

        // Translate labels and format price
        array_map(
            function($label, $value) {
                return [__($label)->render() => $this->priceCurrency->format($value, false) ];
            },
            array_keys($detailViewData), $detailViewData
        );

        $detailView = new DetailsView(DetailsView::DISPLAY_MODE_COLUMNS, [$detailViewData]);
        $detailView->setCaption(__('totals'));
        return [$detailView];
    }
}
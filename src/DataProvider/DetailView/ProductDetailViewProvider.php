<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\DetailView;


use Emico\RobinHq\DataProvider\EavAttribute\AttributeRetriever;
use Emico\RobinHq\Model\Config;
use Emico\RobinHqLib\Model\Order\DetailsView;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Item;
use Psr\Log\LoggerInterface;

class ProductDetailViewProvider implements DetailViewProviderInterface
{
    /**
     * ProductDetailViewProvider constructor.
     * @param PriceCurrencyInterface $priceCurrency
     * @param Config $moduleConfig
     * @param AttributeRetriever $attributeRetriever
     * @param ProductDataProviderInterface $productDataProvider
     */
    public function __construct(
        private PriceCurrencyInterface $priceCurrency,
        private Config $moduleConfig,
        private AttributeRetriever $attributeRetriever,
        private ProductDataProviderInterface $productDataProvider
    ) {
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    public function getItems(OrderInterface $order): array
    {
        $orderItemsData = [];
        foreach ($order->getItems() as $item) {
            $itemData = [
                __('artikelnr_(SKU)')->render() => $item->getSku(),
                __('article name')->render() => $item->getName(),
                __('quantity')->render() => $item->getQtyOrdered(),
                __('price')->render() => $this->priceCurrency->format($item->getPriceInclTax(), false),
                __('totalIncludingTax')->render() => $this->priceCurrency->format($item->getRowTotalInclTax(), false)
            ];

            // Add custom configured product attributes
            if ($item instanceof Item) {
                $product = $item->getProduct();
                if ($product) {
                    $itemData = array_merge($itemData, $this->getCustomProductAttributes($product));
                    $itemData = array_merge($itemData, $this->productDataProvider->getAdditionalProductData($product));
                }
            }
            $orderItemsData[] = $itemData;
        }
        $detailView = new DetailsView(DetailsView::DISPLAY_MODE_ROWS, $orderItemsData);
        $detailView->setCaption(__('products'));
        return [$detailView];
    }

    /**
     * @param ProductInterface $product
     * @return array
     */
    protected function getCustomProductAttributes(ProductInterface $product): array
    {
        if (!$product instanceof Product) {
            return [];
        }
        $attributeCodes = $this->moduleConfig->getProductAttributes();

        $result = [];
        foreach ($attributeCodes as $code) {
            $attributeValue = $this->attributeRetriever->getAttributeValue(Product::ENTITY, $product, $code);
            if ($attributeValue) {
                $result[$attributeValue->getLabel()] = $attributeValue->getValue();
            }
        }
        return $result;
    }
}
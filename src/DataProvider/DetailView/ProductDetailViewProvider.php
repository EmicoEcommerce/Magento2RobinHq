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
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var Config
     */
    private $moduleConfig;

    /**
     * @var array
     */
    private $products;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AttributeRetriever
     */
    private $attributeRetriever;

    /**
     * @var ProductDataProviderInterface
     */
    private $productDataProvider;

    /**
     * ProductDetailViewProvider constructor.
     * @param PriceCurrencyInterface $priceCurrency
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param EavConfig $eavConfig
     * @param Config $moduleConfig
     * @param AttributeRetriever $attributeRetriever
     * @param LoggerInterface $logger
     * @param ProductDataProviderInterface $productDataProvider
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        EavConfig $eavConfig,
        Config $moduleConfig,
        AttributeRetriever $attributeRetriever,
        LoggerInterface $logger,
        ProductDataProviderInterface $productDataProvider
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->moduleConfig = $moduleConfig;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->eavConfig = $eavConfig;
        $this->logger = $logger;
        $this->attributeRetriever = $attributeRetriever;
        $this->productDataProvider = $productDataProvider;
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
     * @param OrderInterface $order
     * @param OrderItemInterface $orderItem
     * @return ProductInterface|null
     */
    protected function getProductFromOrderItem(OrderInterface $order, OrderItemInterface $orderItem): ?ProductInterface
    {
        if ($this->products === null) {
            $productIds = array_map(
                function (OrderItemInterface $item) {
                    return $item->getProductId();
                },
                $order->getItems()
            );
            $productIds = array_unique($productIds);

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('entity_id', $productIds, 'in')
                ->create();

            $products = $this->productRepository->getList($searchCriteria)->getItems();
            foreach ($products as $product) {
                $this->products[$product->getId()] = $product;
            }
        }

        return $this->products[$orderItem->getProductId()] ?? null;
    }

    /**
     * @param ProductInterface $product
     * @return array
     */
    protected function getCustomProductAttributes(ProductInterface $product): array
    {
        $attributeCodes = $this->moduleConfig->getProductAttributes();
        if (!$product instanceof Product) {
            return [];
        }

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
<?php

declare(strict_types=1);

namespace Emico\RobinHq\ViewModel;

use Emico\RobinHq\Model\Config;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManager;

class ViewedProduct implements ArgumentInterface
{
    public const AVAILABILITY_AVAILABLE = 'Available';
    public const AVAILABILITY_NOT_AVAILABLE = 'Not available';

    public function __construct(
        private Config $config,
        private Registry $registry,
        private StoreManager $storeManager,
        private Image $imageHelper
    ) {
    }

    public function shouldRender(): bool
    {
        if (!$this->getCurrentProduct()) {
            return false;
        }

        if (!$this->config->isViewedProductsEnabled()) {
            return false;
        }

        return true;
    }

    protected function getCurrentProduct(): ?Product
    {
        return $this->registry->registry('current_product');
    }

    public function getViewedProductJson(): string
    {
        $product = $this->getCurrentProduct();

        $store = $this->storeManager->getStore();

        $productData = [
            'id' => $product->getSku(),
            'name' => $product->getName(),
            'description' => addslashes(preg_replace('/\s+/', ' ', trim($product->getDescription() ?? ''))),
            'url' => $product->getProductUrl(),
            'imageUrl' => $this->getImageUrl($product),
            'price' => $product->getFinalPrice(),
            'currency' => $store->getCurrentCurrencyCode(),
            'availability' => ($product->isSalable() ? self::AVAILABILITY_AVAILABLE : self::AVAILABILITY_NOT_AVAILABLE)
        ];

        return json_encode($productData);
    }

    protected function getImageUrl(Product $product): string
    {
        $productImage = $this->imageHelper
            ->init($product, 'small_image')
            ->setImageFile($product->getImage());

        return $productImage->getUrl() ?? '';
    }
}

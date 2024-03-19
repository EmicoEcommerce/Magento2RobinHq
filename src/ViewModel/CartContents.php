<?php

declare(strict_types=1);

namespace Emico\RobinHq\ViewModel;

use Emico\RobinHq\Model\Config;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Checkout\Model\Session;

class CartContents implements ArgumentInterface
{
    /**
     * @param Config  $config
     * @param Session $checkoutSession
     */
    public function __construct(private Config $config, private Session $checkoutSession)
    {
    }

    /**
     * @return bool
     */
    public function shouldRender(): bool
    {
        if (!$this->config->isViewedProductsEnabled()) {
            return false;
        }

        if ($this->checkoutSession->getQuote()->getItemsCount() === 0) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getCartContentsJson(): string
    {
        $quote = $this->checkoutSession->getQuote();
        $quoteItems = $quote->getAllVisibleItems();

        $itemData = array_map(function ($quoteItem) {
            return [
                'sku' => $quoteItem->getSku(),
                'name' => $quoteItem->getName(),
                'qty' => $quoteItem->getQty(),
                'price' => $quoteItem->getPrice(),
            ];
        }, $quoteItems);

        return json_encode($itemData);
    }
}

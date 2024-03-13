<?php

declare(strict_types=1);

namespace Emico\RobinHq\Plugin\Frontend\Magento\Framework\View\Result;

use Magento\Checkout\Helper\Cart;
use Magento\Customer\Model\Session;

class Layout
{
    /**
     * Layout constructor.
     * @param Session $customerSession
     * @param Cart    $cartHelper
     */
    public function __construct(private Session $customerSession, private Cart $cartHelper)
    {
    }

    /**
     * Add handles according to case.
     * @param \Magento\Framework\View\Result\Layout $subject
     * @param mixed                                 $result
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return mixed
     */
    public function afterAddDefaultHandle(
        \Magento\Framework\View\Result\Layout $subject,
        mixed $result
    ) {
        if ($this->customerSession->getCustomerId()) {
            $result->addHandle('robinhq_customer_logged_in');
        }

        if ($this->cartHelper->getItemsCount() !== 0) {
            $result->addHandle('robinhq_cart_contents');
        }

        return $result;
    }
}

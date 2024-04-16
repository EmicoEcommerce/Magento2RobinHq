<?php

declare(strict_types=1);

namespace Emico\RobinHq\ViewModel;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class LoggedInCustomer implements ArgumentInterface
{
    /**
     * @param Session         $customerSession
     */
    public function __construct(
        private Session $customerSession
    ) {
    }

    /**
     * @return bool
     */
    public function shouldRender(): bool
    {
        return $this->customerSession->getCustomerId() !== null;
    }
}

<?php

declare(strict_types=1);

namespace Emico\RobinHq\ViewModel;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;

class LoggedInCustomer implements ArgumentInterface
{
    /**
     * @param LoggerInterface $logger
     * @param Session         $customerSession
     */
    public function __construct(
        private LoggerInterface $logger,
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

    /**
     * @return string
     */
    public function getName(): string
    {
        try {
            return $this->customerSession->getCustomer()->getName() ?? 'Unknown';
        } catch (LocalizedException $e) {
            $this->logger->error('Could not get customer name', ['exception' => $e]);
            return 'Unknown';
        }
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->customerSession->getCustomer()->getEmail();
    }
}

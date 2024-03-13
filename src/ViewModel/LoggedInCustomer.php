<?php

declare(strict_types=1);

namespace Emico\RobinHq\ViewModel;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;

class LoggedInCustomer implements ArgumentInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private Session $customerSession
    ) {
    }

    public function shouldRender(): bool
    {
        return $this->customerSession->getCustomerId() !== null;
    }

    public function getName(): string
    {
        try {
            return $this->customerSession->getCustomer()->getName();
        } catch (LocalizedException $e) {
            $this->logger->error('Could not get customer name', ['exception' => $e]);
            return '';
        }
    }

    public function getEmail(): string
    {
        return $this->customerSession->getCustomer()->getEmail();
    }
}

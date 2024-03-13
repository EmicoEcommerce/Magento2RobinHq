<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\PanelView\Customer;


use Emico\RobinHq\Model\Config as ModuleConfig;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Data\Customer;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class CustomAttributesProvider implements PanelViewProviderInterface
{
    /**
     * CustomerAttributesProvider constructor.
     * @param ModuleConfig $moduleConfig
     * @param EavConfig $eavConfig
     * @param LoggerInterface $logger
     */
    public function __construct(private ModuleConfig $moduleConfig, private EavConfig $eavConfig, private LoggerInterface $logger)
    {
    }

    /**
     * @param CustomerInterface $customer
     * @return array
     */
    public function getData(CustomerInterface $customer): array
    {
        if (!$customer instanceof Customer) {
            return [];
        }

        $customAttributesToRetrieve = $this->moduleConfig->getCustomerAttributes();
        if (!$customAttributesToRetrieve) {
            return [];
        }

        $panelData = [];

        $customerData = $customer->__toArray();
        foreach ($customAttributesToRetrieve as $attributeCode) {
            try {
                $attributeConfig = $this->eavConfig->getAttribute(\Magento\Customer\Model\Customer::ENTITY, $attributeCode);
            } catch (LocalizedException $e) {
                $this->logger->critical($e);
                continue;
            }
            $attributeValue = $customerData[$attributeCode] ?? null;
            if ($attributeValue === null && isset($customerData['custom_attributes'][$attributeCode])) {
                $attributeValue = $customerData['custom_attributes'][$attributeCode]['value'];
            }

            if ($attributeValue !== null) {
                $panelData[$attributeConfig->getDefaultFrontendLabel()] = $attributeValue;
            }
        }
        return $panelData;
    }
}
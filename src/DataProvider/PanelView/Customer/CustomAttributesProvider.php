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
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CustomerAttributesProvider constructor.
     * @param ModuleConfig $moduleConfig
     * @param EavConfig $eavConfig
     * @param LoggerInterface $logger
     */
    public function __construct(ModuleConfig $moduleConfig, EavConfig $eavConfig, LoggerInterface $logger)
    {
        $this->moduleConfig = $moduleConfig;
        $this->eavConfig = $eavConfig;
        $this->logger = $logger;
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

        $panelData = [];
        //@todo move to AttributeRetriever. Even met stijn overlegen hoe dit generiek te maken.
        $customerData = $customer->__toArray();
        foreach ($this->moduleConfig->getCustomerAttributes() as $attributeCode) {
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
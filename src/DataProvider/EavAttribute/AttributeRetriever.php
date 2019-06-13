<?php
/**
 * @author Bram Gerritsen <bgerritsen@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\RobinHq\DataProvider\EavAttribute;

use Magento\Eav\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractExtensibleModel;

class AttributeRetriever
{
    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * AttributeRetriever constructor.
     * @param Config $eavConfig
     */
    public function __construct(Config $eavConfig)
    {
        $this->eavConfig = $eavConfig;
    }

    /**
     * @param string $entityType
     * @param AbstractExtensibleModel $model
     * @param string $attributeCode
     * @return mixed
     */
    public function getAttributeValue(string $entityType, AbstractExtensibleModel $model, string $attributeCode): ?AttributeValue
    {
        try {
            $attributeConfig = $this->eavConfig->getAttribute($entityType, $attributeCode);
        } catch (LocalizedException $e) {
            return null;
        }

        $label = $attributeConfig->getDefaultFrontendLabel();

        if (method_exists($model, 'getAttributeText')) {
            $value = $model->getAttributeText($attributeCode);
        } else {
            $value = $model->getData($attributeCode);
        }

        return new AttributeValue($label, $value);
    }
}